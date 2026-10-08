<?php
/**
 * A group of diagnostic rows.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Diagnostic_Section {

	/**
	 * @param string                  $id    Unique, URL-safe slug (used for the collapsible anchor).
	 * @param string                  $title Human-readable title.
	 * @param IMGDT_Diagnostic_Row[]  $rows  Rows belonging to this section.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $title,
		public readonly array $rows
	) {}

	/**
	 * Count the rows flagged as errors.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function error_count(): int {
		return count( array_filter( $this->rows, static fn( IMGDT_Diagnostic_Row $row ) => $row->is_error ) );
	}

	/**
	 * Get only the rows flagged as errors.
	 *
	 * @since 0.1.0
	 *
	 * @return IMGDT_Diagnostic_Row[]
	 */
	public function error_rows(): array {
		return array_values( array_filter( $this->rows, static fn( IMGDT_Diagnostic_Row $row ) => $row->is_error ) );
	}
}
