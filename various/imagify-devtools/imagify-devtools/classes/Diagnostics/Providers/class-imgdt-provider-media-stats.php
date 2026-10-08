<?php
/**
 * "Media Library Stats" diagnostics section.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Media Library totals (optimized/unoptimized/error counts, space saved) and
 * site-wide next-gen coverage. Purely informational, no row here is ever
 * flagged — reuses Imagify's own official counting functions (the same ones
 * its own dashboard already calls) rather than re-deriving anything, so this
 * costs nothing beyond what Imagify's own admin screens already pay.
 *
 * @since 0.2.0
 * @author Saransh Jain
 */
class IMGDT_Provider_Media_Stats implements IMGDT_Diagnostics_Provider {

	/**
	 * {@inheritDoc}
	 */
	public function get_section(): IMGDT_Diagnostic_Section {
		if ( ! function_exists( 'imagify_count_attachments' ) ) {
			return new IMGDT_Diagnostic_Section(
				'media-stats',
				__( 'Media Library Stats', 'imagify-devtools' ),
				array(
					new IMGDT_Diagnostic_Row( __( 'Imagify plugin', 'imagify-devtools' ), __( 'Needs Imagify to be active.', 'imagify-devtools' ) ),
				)
			);
		}

		$rows = array(
			new IMGDT_Diagnostic_Row( __( 'Total attachments', 'imagify-devtools' ), imagify_count_attachments() ),
			new IMGDT_Diagnostic_Row( __( 'Optimized', 'imagify-devtools' ), imagify_count_optimized_attachments() ),
			new IMGDT_Diagnostic_Row( __( 'Unoptimized', 'imagify-devtools' ), imagify_count_unoptimized_attachments() ),
			new IMGDT_Diagnostic_Row( __( 'With an optimization error', 'imagify-devtools' ), imagify_count_error_attachments() ),
		);

		$saving = $this->get_saving_data();

		if ( $saving ) {
			$rows[] = new IMGDT_Diagnostic_Row(
				__( 'Space saved', 'imagify-devtools' ),
				sprintf(
					/* translators: 1 is a size, 2 is a size, 3 is a percentage. */
					__( '%1$s of %2$s (%3$d%%)', 'imagify-devtools' ),
					size_format( $saving['optimized_size'] ),
					size_format( $saving['original_size'] ),
					(int) $saving['percent']
				)
			);
		}

		$rows[] = $this->get_nextgen_row();

		return new IMGDT_Diagnostic_Section( 'media-stats', __( 'Media Library Stats', 'imagify-devtools' ), $rows );
	}

	/**
	 * Get (and cache) the space-saving totals. This scans every optimized
	 * attachment's serialized data — the same work Imagify's own dashboard
	 * widget does — so it's cached here to avoid repeating it every time
	 * this tab is opened.
	 *
	 * @since 0.2.0
	 *
	 * @return array{original_size: int, optimized_size: int, percent: int}|null
	 */
	private function get_saving_data(): ?array {
		$cached = IMGDT_Cache::get( 'media_saving_data' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		if ( ! function_exists( 'imagify_count_saving_data' ) ) {
			return null;
		}

		$data = imagify_count_saving_data();

		if ( ! is_array( $data ) || ! isset( $data['original_size'], $data['optimized_size'], $data['percent'] ) ) {
			return null;
		}

		IMGDT_Cache::set( 'media_saving_data', $data );

		return $data;
	}

	/**
	 * Get the "optimized images still missing a next-gen version" row —
	 * Imagify's own actionable number (transient-cached by Imagify itself,
	 * auto-invalidated on optimize/restore/delete/settings-change), rather
	 * than a full sitewide has-nextgen-or-not census, which doesn't exist
	 * pre-built and would be expensive to compute from scratch.
	 *
	 * @since 0.2.0
	 *
	 * @return IMGDT_Diagnostic_Row
	 */
	private function get_nextgen_row(): IMGDT_Diagnostic_Row {
		if ( ! class_exists( '\Imagify\Stats\OptimizedMediaWithoutNextGen' ) ) {
			return new IMGDT_Diagnostic_Row( __( 'Optimized images missing next-gen', 'imagify-devtools' ), __( 'Unavailable', 'imagify-devtools' ) );
		}

		$missing = (int) \Imagify\Stats\OptimizedMediaWithoutNextGen::get_instance()->get_cached_stat();
		$format  = function_exists( 'get_imagify_option' ) ? get_imagify_option( 'optimization_format' ) : '';

		return new IMGDT_Diagnostic_Row(
			__( 'Optimized images missing next-gen', 'imagify-devtools' ),
			$missing,
			false,
			$format
				? sprintf(
					/* translators: %s is a format name, e.g. "webp". */
					__( 'Configured next-gen format: %s', 'imagify-devtools' ),
					esc_html( $format )
				)
				: ''
		);
	}
}
