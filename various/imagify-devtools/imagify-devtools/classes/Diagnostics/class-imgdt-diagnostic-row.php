<?php
/**
 * A single diagnostic test result.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Immutable value object for one row of a diagnostic section.
 *
 * @since 0.1.0
 */
class IMGDT_Diagnostic_Row {

	/**
	 * @param string      $label      Human-readable label. May contain safe inline HTML (`<code>`, `<em>`).
	 * @param mixed       $value      The raw value. Booleans/arrays are formatted by the view.
	 * @param bool        $is_error   Whether this row should be highlighted as a problem.
	 * @param string      $more_info  Optional extra context/help text. May contain safe inline HTML.
	 * @param string|null $extra_html Optional extra markup appended after more_info (e.g. an action link/button).
	 */
	public function __construct(
		public readonly string $label,
		public readonly mixed $value,
		public readonly bool $is_error = false,
		public readonly string $more_info = '',
		public readonly ?string $extra_html = null
	) {}

	/**
	 * Convenience constructor that derives `is_error` from an expected value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $label     Row label.
	 * @param mixed  $value     Actual value.
	 * @param mixed  $expected  Value that `$value` should equal to be considered OK.
	 * @param string $more_info Optional help text.
	 * @return self
	 */
	public static function compare( string $label, mixed $value, mixed $expected, string $more_info = '' ): self {
		return new self( $label, $value, $value !== $expected, $more_info );
	}
}
