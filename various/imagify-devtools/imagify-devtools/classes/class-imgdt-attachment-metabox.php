<?php
/**
 * Media edit-screen integration: a "File info" meta box.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Four sections in one meta box: file info, formats (original + WebP/AVIF
 * per size, with mime-type checked against what's on disk), optimization
 * results, and any other post meta not covered by WordPress or Imagify.
 *
 * @since 0.1.0
 */
class IMGDT_Attachment_Metabox {

	/**
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Meta box id.
	 *
	 * @var string
	 */
	private const METABOX_ID = 'imgdt-file-info';

	/**
	 * Post meta keys handled elsewhere in this box, or noise not worth
	 * showing ("Other metadata" is meant for the unexpected, not for
	 * WordPress's own internal bookkeeping).
	 *
	 * @var string[]
	 */
	private const KNOWN_META_KEYS = array(
		'_wp_attached_file',
		'_wp_attachment_metadata',
		'_imagify_status',
		'_imagify_optimization_level',
		'_imagify_data',
		'_edit_lock',
		'_edit_last',
	);

	/**
	 * Constructor (private, singleton).
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.1.0
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Init: register hooks.
	 *
	 * @since 0.1.0
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), -10 );
		add_action( 'admin_print_styles-post.php', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue the plugin's admin styles on the attachment edit screen.
	 *
	 * @since 0.2.0
	 */
	public function enqueue_assets(): void {
		if ( ! imgdt_current_user_can() ) {
			return;
		}

		wp_enqueue_style( 'imgdt-admin', IMGDT_URL . 'assets/css/admin.css', array(), imgdt_asset_version( 'assets/css/admin.css' ) );
	}

	/**
	 * Register the meta box, only for supported image attachments.
	 *
	 * @since 0.1.0
	 */
	public function add_meta_box(): void {
		imgdt_safe(
			function () {
				global $post;

				if ( ! imgdt_current_user_can() ) {
					return;
				}

				if ( ! $post || 'attachment' !== $post->post_type || ! wp_attachment_is_image( $post->ID ) ) {
					return;
				}

				add_meta_box(
					self::METABOX_ID,
					__( 'Imagify DevTools', 'imagify-devtools' ),
					array( $this, 'render' ),
					'attachment',
					'normal',
					'high'
				);
			}
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $post The attachment.
	 */
	public function render( WP_Post $post ): void {
		$ok = imgdt_safe(
			function () use ( $post ) {
				$this->render_file_info( $post );
				$this->render_formats( $post );
				$this->render_optimization_results( $post );
				$this->render_other_meta( $post );

				return true;
			}
		);

		if ( ! $ok ) {
			echo '<p>' . esc_html__( 'This information could not be loaded.', 'imagify-devtools' ) . '</p>';
		}
	}

	/**
	 * Render the "File info" rows.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $post The attachment.
	 */
	private function render_file_info( WP_Post $post ): void {
		echo '<h4>' . esc_html__( 'File info', 'imagify-devtools' ) . '</h4>';
		echo '<table class="imgdt-table imgdt-metabox-table"><tbody>';

		foreach ( $this->build_file_info_rows( $post ) as $row ) {
			echo IMGDT_View::row( $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- IMGDT_View::row() escapes internally.
		}

		echo '</tbody></table>';
	}

	/**
	 * Build the "File info" rows.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $post The attachment.
	 * @return IMGDT_Diagnostic_Row[]
	 */
	private function build_file_info_rows( WP_Post $post ): array {
		$path = get_attached_file( $post->ID );

		if ( ! $path ) {
			return array( new IMGDT_Diagnostic_Row( __( 'Path', 'imagify-devtools' ), __( 'Could not retrieve the file path.', 'imagify-devtools' ), true ) );
		}

		$path   = wp_normalize_path( $path );
		$exists = file_exists( $path );

		$rows = array(
			new IMGDT_Diagnostic_Row( __( 'Path', 'imagify-devtools' ), $path ),
			new IMGDT_Diagnostic_Row( __( 'File exists', 'imagify-devtools' ), $exists, ! $exists ),
		);

		if ( ! $exists ) {
			return $rows;
		}

		$writable = wp_is_writable( $path );
		$rows[]   = new IMGDT_Diagnostic_Row( __( 'File is writable', 'imagify-devtools' ), $writable, ! $writable );

		$stats = @stat( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- stat() legitimately warns on some filesystems, we handle false below.
		$bytes = $stats && $stats['size'] ? size_format( $stats['size'], 2 ) : '0';

		$rows[] = new IMGDT_Diagnostic_Row( __( 'File size', 'imagify-devtools' ), $bytes, ! $stats || ! $stats['size'] );

		$image_size = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- same as above.

		$rows[] = new IMGDT_Diagnostic_Row(
			__( 'Dimensions', 'imagify-devtools' ),
			$image_size ? $image_size[0] . '&nbsp;&times;&nbsp;' . $image_size[1] : __( 'Unavailable', 'imagify-devtools' ),
			! $image_size
		);

		$rows[] = $this->backup_row( $post, $path );

		return $rows;
	}

	/**
	 * Check whether Imagify's backup of the original file exists.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $post Attachment.
	 * @param string  $path Absolute path to the full-size file.
	 * @return IMGDT_Diagnostic_Row
	 */
	private function backup_row( WP_Post $post, string $path ): IMGDT_Diagnostic_Row {
		$upload_dir = wp_upload_dir();
		$base_dir   = trailingslashit( wp_normalize_path( $upload_dir['basedir'] ) );

		$backup_dir = apply_filters( 'imagify_backup_directory', $base_dir . 'backup/' );
		$backup_dir = trailingslashit( wp_normalize_path( $backup_dir ) );

		$original_path = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $post->ID ) : $path;
		$original_path = $original_path ? wp_normalize_path( $original_path ) : $path;
		$backup_path   = str_replace( $base_dir, $backup_dir, $original_path );
		$has_backup    = file_exists( $backup_path );

		$imagify_settings = get_site_option( 'imagify_settings' );
		$backup_enabled   = ! empty( $imagify_settings['backup'] );

		return new IMGDT_Diagnostic_Row(
			__( 'Has backup', 'imagify-devtools' ),
			$has_backup,
			$backup_enabled && ! $has_backup,
			$backup_enabled ? '' : __( 'No need, backup is disabled.', 'imagify-devtools' )
		);
	}

	/**
	 * Render the "Formats" table: original + WebP/AVIF, per size, with the
	 * recorded mime-type checked against the actual file.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $post The attachment.
	 */
	private function render_formats( WP_Post $post ): void {
		$path = get_attached_file( $post->ID );

		if ( ! $path || ! file_exists( $path ) ) {
			return;
		}

		$path     = wp_normalize_path( $path );
		$dir      = trailingslashit( dirname( $path ) );
		$metadata = wp_get_attachment_metadata( $post->ID );
		$sizes    = ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ? $metadata['sizes'] : array();

		$next_gen = get_post_meta( $post->ID, '_imagify_data', true );
		$next_gen = is_array( $next_gen ) && ! empty( $next_gen['sizes'] ) ? $next_gen['sizes'] : array();

		$entries   = array( 'full' => array( 'file' => wp_basename( $path ) ) ) + $sizes;
		$mime_type = get_post_mime_type( $post->ID );

		echo '<h4>' . esc_html__( 'Formats', 'imagify-devtools' ) . '</h4>';
		echo '<table class="widefat striped imgdt-table"><thead><tr>';
		echo '<th>' . esc_html__( 'Size', 'imagify-devtools' ) . '</th>';
		echo '<th>' . esc_html__( 'Mime type', 'imagify-devtools' ) . '</th>';
		echo '<th>WebP</th><th>AVIF</th>';
		echo '</tr></thead><tbody>';

		foreach ( $entries as $size_name => $size_data ) {
			$file = $dir . ( $size_data['file'] ?? '' );

			$recorded_mime = 'full' === $size_name ? $mime_type : ( $size_data['mime-type'] ?? '' );
			$actual_mime   = file_exists( $file ) ? wp_check_filetype( $file )['type'] : '';
			$mime_mismatch = $actual_mime && $recorded_mime && $actual_mime !== $recorded_mime;

			$webp = $this->format_status( $file . '.webp', $next_gen[ $size_name . '@imagify-webp' ] ?? null );
			$avif = $this->format_status( $file . '.avif', $next_gen[ $size_name . '@imagify-avif' ] ?? null );

			echo '<tr' . ( $mime_mismatch ? ' class="imgdt-row-error"' : '' ) . '>';
			echo '<th scope="row">' . esc_html( $size_name ) . '</th>';
			echo '<td>' . esc_html( $actual_mime ?: __( 'Unknown', 'imagify-devtools' ) );
			if ( $mime_mismatch ) {
				echo '<br /><small>' . esc_html(
					sprintf(
						/* translators: 1: the file's actual mime type, 2: the mime type recorded in the database. */
						__( 'Mismatch — file is %1$s, database says %2$s', 'imagify-devtools' ),
						$actual_mime,
						$recorded_mime
					)
				) . '</small>';
			}
			echo '</td>';
			echo '<td>' . esc_html( $webp ) . '</td>';
			echo '<td>' . esc_html( $avif ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Determine a next-gen format's status for one size.
	 *
	 * @since 0.2.0
	 *
	 * @param string     $file      Expected path to the next-gen file.
	 * @param array|null $imagify_data Imagify's own record for this size/format, if any.
	 * @return string
	 */
	private function format_status( string $file, ?array $imagify_data ): string {
		if ( null === $imagify_data ) {
			return __( 'Not generated', 'imagify-devtools' );
		}

		return file_exists( $file ) ? __( 'Exists', 'imagify-devtools' ) : __( 'Missing on disk', 'imagify-devtools' );
	}

	/**
	 * Render the optimization results: status, level, total saved, and a
	 * per-size breakdown.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $post The attachment.
	 */
	private function render_optimization_results( WP_Post $post ): void {
		$status = get_post_meta( $post->ID, '_imagify_status', true );

		if ( ! $status ) {
			return;
		}

		$level = get_post_meta( $post->ID, '_imagify_optimization_level', true );
		$data  = get_post_meta( $post->ID, '_imagify_data', true );
		$data  = is_array( $data ) ? $data : array();
		$sizes = ! empty( $data['sizes'] ) && is_array( $data['sizes'] ) ? $data['sizes'] : array();
		$stats = ! empty( $data['stats'] ) && is_array( $data['stats'] ) ? $data['stats'] : array();

		echo '<h4>' . esc_html__( 'Optimization results', 'imagify-devtools' ) . '</h4>';
		echo '<table class="imgdt-table imgdt-metabox-table"><tbody>';

		echo IMGDT_View::row( new IMGDT_Diagnostic_Row( __( 'Status', 'imagify-devtools' ), (string) $status, 'success' !== $status ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( '' !== $level && function_exists( 'imagify_get_optimization_level_label' ) ) {
			echo IMGDT_View::row( new IMGDT_Diagnostic_Row( __( 'Level', 'imagify-devtools' ), imagify_get_optimization_level_label( (int) $level ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( isset( $stats['original_size'], $stats['optimized_size'] ) ) {
			echo IMGDT_View::row(
				new IMGDT_Diagnostic_Row(
					__( 'Total saved', 'imagify-devtools' ),
					sprintf(
						'%1$s → %2$s (%3$s%%)',
						size_format( (int) $stats['original_size'], 2 ),
						size_format( (int) $stats['optimized_size'], 2 ),
						isset( $stats['percent'] ) ? round( (float) $stats['percent'], 2 ) : '?'
					)
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</tbody></table>';

		if ( ! $sizes ) {
			return;
		}

		echo '<table class="widefat striped imgdt-table"><thead><tr>';
		echo '<th>' . esc_html__( 'Size', 'imagify-devtools' ) . '</th>';
		echo '<th>' . esc_html__( 'Original', 'imagify-devtools' ) . '</th>';
		echo '<th>' . esc_html__( 'Optimized', 'imagify-devtools' ) . '</th>';
		echo '<th>' . esc_html__( 'Saved', 'imagify-devtools' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $sizes as $size_name => $size_result ) {
			$success = ! empty( $size_result['success'] );

			echo '<tr' . ( $success ? '' : ' class="imgdt-row-error"' ) . '>';
			echo '<th scope="row">' . esc_html( $size_name ) . '</th>';

			if ( $success && isset( $size_result['original_size'], $size_result['optimized_size'] ) ) {
				echo '<td>' . esc_html( size_format( (int) $size_result['original_size'], 2 ) ) . '</td>';
				echo '<td>' . esc_html( size_format( (int) $size_result['optimized_size'], 2 ) ) . '</td>';
				echo '<td>' . esc_html( isset( $size_result['percent'] ) ? round( (float) $size_result['percent'], 2 ) . '%' : '?' ) . '</td>';
			} else {
				echo '<td colspan="3">' . esc_html__( 'Not optimized', 'imagify-devtools' ) . '</td>';
			}

			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render any post meta not covered by the sections above.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $post The attachment.
	 */
	private function render_other_meta( WP_Post $post ): void {
		$metas = get_post_meta( $post->ID );
		$metas = is_array( $metas ) ? array_diff_key( $metas, array_flip( self::KNOWN_META_KEYS ) ) : array();

		if ( ! $metas ) {
			return;
		}

		echo '<h4>' . esc_html__( 'Other metadata', 'imagify-devtools' ) . '</h4>';
		echo '<table class="imgdt-table imgdt-metabox-table"><tbody>';

		foreach ( $metas as $key => $values ) {
			$value = count( $values ) > 1 ? $values : maybe_unserialize( $values[0] ?? '' );

			echo '<tr><th scope="row"><code>' . esc_html( $key ) . '</code></th><td>' . IMGDT_View::value( $value ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- IMGDT_View::value() escapes internally.
		}

		echo '</tbody></table>';
	}
}
