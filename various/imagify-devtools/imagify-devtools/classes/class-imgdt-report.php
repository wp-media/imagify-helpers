<?php
/**
 * Builds the plain-text "Copy report" output.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Facts only: status and value, no remediation text. This can end up
 * pasted somewhere further from the original ticket than intended, so it
 * stays consistent with the rest of the plugin's customer-facing surfaces.
 *
 * @since 0.2.0
 */
class IMGDT_Report {

	/**
	 * @var IMGDT_Diagnostics_Registry
	 */
	private IMGDT_Diagnostics_Registry $registry;

	/**
	 * @param IMGDT_Diagnostics_Registry $registry Full diagnostics registry.
	 */
	public function __construct( IMGDT_Diagnostics_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Build the plain-text triage summary.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function get_text(): string {
		global $wp_version;

		$builder = new IMGDT_Triage_Builder( $this->registry );
		$media   = $this->get_media_counts();
		$lines   = array(
			'Imagify DevTools report',
			home_url( '/' ),
			gmdate( 'Y-m-d H:i:s' ) . ' UTC',
			'',
			sprintf( 'Imagify: %s', defined( 'IMAGIFY_VERSION' ) ? IMAGIFY_VERSION : 'not active' ),
			sprintf( 'Imagify plan: %s', $this->get_imagify_plan() ),
			sprintf( 'WordPress: %s', $wp_version ),
			sprintf( 'PHP: %s', PHP_VERSION ),
			sprintf( 'Attachments: %s', $media ? $media['total'] : 'unavailable' ),
			sprintf( 'Optimized: %s', $media ? sprintf( '%d (%d%%)', $media['optimized'], $media['percent'] ) : 'unavailable' ),
			'',
		);

		foreach ( $builder->get_cards() as $card ) {
			$lines[] = sprintf( '%s: %s', $card->label, $card->value );

			if ( $card->detail ) {
				foreach ( explode( "\n", $card->detail ) as $detail_line ) {
					$lines[] = '  ' . $detail_line;
				}
			}
		}

		$other = $builder->get_other_count();
		$lines[] = '';
		$lines[] = sprintf( 'Other checks flagged: %d', $other );

		return implode( "\n", $lines );
	}

	/**
	 * Get the total and optimized attachment counts for the report header,
	 * via Imagify's own count functions (the same ones the Media Library
	 * Stats section uses).
	 *
	 * @since 0.2.0
	 *
	 * @return array{total: int, optimized: int, percent: int}|null Null if Imagify's functions are unavailable.
	 */
	private function get_media_counts(): ?array {
		if (
			! function_exists( 'imagify_count_attachments' )
			|| ! function_exists( 'imagify_count_optimized_attachments' )
			|| ! function_exists( 'imagify_percent_optimized_attachments' )
		) {
			return null;
		}

		return imgdt_safe(
			static fn() => array(
				'total'     => (int) imagify_count_attachments(),
				'optimized' => (int) imagify_count_optimized_attachments(),
				'percent'   => (int) imagify_percent_optimized_attachments(),
			)
		);
	}

	/**
	 * Get the Imagify plan label, the same way the Various diagnostics
	 * section does, for the report header.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	private function get_imagify_plan(): string {
		if ( ! function_exists( 'get_imagify_user' ) ) {
			return 'not active';
		}

		$user = get_imagify_user();

		return ! empty( $user->plan_label ) ? $user->plan_label : 'unknown';
	}
}
