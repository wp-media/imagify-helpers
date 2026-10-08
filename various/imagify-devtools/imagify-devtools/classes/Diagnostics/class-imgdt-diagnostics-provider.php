<?php
/**
 * Contract for a diagnostics section provider.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Each provider builds exactly one `IMGDT_Diagnostic_Section`. Splitting the
 * legacy single 700-line model into one small class per section keeps each
 * one testable/readable and makes it obvious where to add a new check.
 *
 * @since 0.1.0
 */
interface IMGDT_Diagnostics_Provider {

	/**
	 * Build the section this provider is responsible for.
	 *
	 * @since 0.1.0
	 *
	 * @return IMGDT_Diagnostic_Section
	 */
	public function get_section(): IMGDT_Diagnostic_Section;
}
