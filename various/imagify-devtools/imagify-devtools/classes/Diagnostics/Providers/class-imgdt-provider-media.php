<?php
/**
 * "Media" diagnostics section.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Provider_Media implements IMGDT_Diagnostics_Provider {

	/**
	 * {@inheritDoc}
	 */
	public function get_section(): IMGDT_Diagnostic_Section {
		$invalid_metas_count = $this->count_medias_with_invalid_wp_metas();

		$rows = array(
			new IMGDT_Diagnostic_Row(
				__( 'Attachments with invalid or missing WP metas', 'imagify-devtools' ),
				$invalid_metas_count,
				false,
				'',
				IMGDT_Admin_Post::clear_cache_button( 'invalid_wp_metas' )
			),
			new IMGDT_Diagnostic_Row(
				__( 'Number of thumbnail sizes', 'imagify-devtools' ),
				count( IMGDT_Tools::get_thumbnail_sizes() )
			),
		);

		if ( class_exists( 'Imagify_Folders_DB' ) ) {
			$rows[] = IMGDT_Diagnostic_Row::compare(
				__( 'Folders table is ready', 'imagify-devtools' ),
				Imagify_Folders_DB::get_instance()->can_operate(),
				true
			);
		}

		if ( class_exists( 'Imagify_Files_DB' ) ) {
			$rows[] = IMGDT_Diagnostic_Row::compare(
				__( 'Files table is ready', 'imagify-devtools' ),
				Imagify_Files_DB::get_instance()->can_operate(),
				true
			);
		}

		$orphans = $this->count_orphan_files();

		if ( false !== $orphans ) {
			$rows[] = new IMGDT_Diagnostic_Row(
				__( 'Orphan custom files (no folder)', 'imagify-devtools' ),
				$orphans,
				$orphans > 0,
				'',
				IMGDT_Admin_Post::clear_cache_button( 'orphan_files' )
			);
		}

		if ( function_exists( 'wp_create_image_subsizes' ) ) {
			/** This filter is documented in wp-admin/includes/image.php. */
			$threshold = (int) apply_filters( 'big_image_size_threshold', 2560, array( 0, 0 ), '', 0 );

			$rows[] = new IMGDT_Diagnostic_Row( __( 'Resizing threshold', 'imagify-devtools' ), $threshold );
		}

		$parallel = apply_filters( 'imagify_bulk_buffer_sizes', array( 'wp' => 4, 'File' => 4 ) );

		$rows[] = new IMGDT_Diagnostic_Row( __( 'Number of parallel optimizations in bulk optimizer', 'imagify-devtools' ), $parallel );

		return new IMGDT_Diagnostic_Section( 'media', __( 'Media', 'imagify-devtools' ), $rows );
	}

	/**
	 * Count attachments whose `_wp_attached_file` / `_wp_attachment_metadata` are missing or broken.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	private function count_medias_with_invalid_wp_metas(): int {
		$cached = IMGDT_Cache::get( 'invalid_wp_metas' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		global $wpdb;

		if ( class_exists( 'Imagify_DB' ) && method_exists( 'Imagify_DB', 'get_required_wp_metadata_where_clause' ) ) {
			$mime_types  = Imagify_DB::get_mime_types();
			$statuses    = Imagify_DB::get_post_statuses();
			$nodata_join = Imagify_DB::get_required_wp_metadata_join_clause( 'p.ID', false, false );
			$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause(
				array(
					'matching' => false,
					'test'     => false,
				)
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- values come from Imagify's own trusted DB helper, same pattern as Imagify core.
			$count = (int) $wpdb->get_var(
				"SELECT COUNT( p.ID ) FROM {$wpdb->posts} AS p {$nodata_join}
				WHERE p.post_mime_type IN ( {$mime_types} ) AND p.post_type = 'attachment' AND p.post_status IN ( {$statuses} ) {$nodata_where}"
			);
		} else {
			$statuses = "'" . implode( "','", esc_sql( IMGDT_Tools::get_post_statuses() ) ) . "'";

			// phpcs:ignore WordPress.DB.PreparedSQL.PlaceholderReplacement -- $statuses is a hard-coded, esc_sql()'d list, not user input.
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT( p.ID ) FROM {$wpdb->posts} AS p
					LEFT JOIN {$wpdb->postmeta} AS m1 ON ( p.ID = m1.post_id AND m1.meta_key = '_wp_attached_file' )
					LEFT JOIN {$wpdb->postmeta} AS m2 ON ( p.ID = m2.post_id AND m2.meta_key = '_wp_attachment_metadata' )
					WHERE p.post_mime_type LIKE %s AND p.post_type = 'attachment' AND p.post_status IN ( {$statuses} )
					AND ( m1.meta_value IS NULL OR m2.meta_value IS NULL )",
					'image/%'
				)
			);
		}

		IMGDT_Cache::set( 'invalid_wp_metas', $count );

		return $count;
	}

	/**
	 * Count "custom files" (Imagify's own Files table) that reference a non-existent folder.
	 *
	 * @since 0.1.0
	 *
	 * @return int|false False if the folders/files tables aren't ready.
	 */
	private function count_orphan_files() {
		$folders_ready = class_exists( 'Imagify_Folders_DB' ) && Imagify_Folders_DB::get_instance()->can_operate();
		$files_ready   = class_exists( 'Imagify_Files_DB' ) && Imagify_Files_DB::get_instance()->can_operate();

		if ( ! $folders_ready || ! $files_ready ) {
			return false;
		}

		$cached = IMGDT_Cache::get( 'orphan_files' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		global $wpdb;

		$folders_db  = Imagify_Folders_DB::get_instance();
		$files_db    = Imagify_Files_DB::get_instance();
		$folder_ids  = $wpdb->get_col( 'SELECT ' . esc_sql( $folders_db->get_primary_key() ) . ' FROM ' . esc_sql( $folders_db->get_table_name() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- identifiers only, esc_sql()'d.
		$files_table = esc_sql( $files_db->get_table_name() );
		$files_key   = esc_sql( $files_db->get_primary_key() );

		if ( $folder_ids ) {
			$folder_ids = Imagify_DB::prepare_values_list( $folders_db->cast_col( $folder_ids, $folders_db->get_primary_key() ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $folder_ids is built from cast+escaped IDs, $files_table/$files_key are esc_sql()'d identifiers.
			$count = (int) $wpdb->get_var( "SELECT COUNT( {$files_key} ) FROM {$files_table} WHERE folder_id NOT IN ( {$folder_ids} )" );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- identifiers only, esc_sql()'d.
			$count = (int) $wpdb->get_var( "SELECT COUNT( {$files_key} ) FROM {$files_table}" );
		}

		IMGDT_Cache::set( 'orphan_files', $count );

		return $count;
	}
}
