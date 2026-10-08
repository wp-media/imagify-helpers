<?php
/**
 * View-rendering helpers shared by the admin templates.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * All output here is already escaped before it's returned — templates can
 * `echo` these results directly.
 *
 * @since 0.1.0
 */
class IMGDT_View {

	/**
	 * Render one diagnostic row as a `<tr>`.
	 *
	 * @since 0.1.0
	 *
	 * @param IMGDT_Diagnostic_Row $row       The row.
	 * @param string                $anchor_id Optional id to attach to the row (for jump links).
	 * @return string Safe HTML.
	 */
	public static function row( IMGDT_Diagnostic_Row $row, string $anchor_id = '' ): string {
		$class = $row->is_error ? ' class="imgdt-row-error"' : '';
		$id    = $anchor_id ? ' id="' . esc_attr( $anchor_id ) . '"' : '';

		$more_info = $row->more_info;

		if ( $row->extra_html ) {
			$more_info .= ( $more_info ? ' ' : '' ) . $row->extra_html;
		}

		return sprintf(
			'<tr%1$s%2$s><th scope="row">%3$s</th><td>%4$s</td><td>%5$s</td></tr>',
			$id,
			$class,
			wp_kses_post( $row->label ),
			self::value( $row->value ),
			wp_kses_post( $more_info )
		);
	}

	/**
	 * Render a raw value for display: booleans as Yes/No, associative arrays
	 * as a nested key/value table, everything else through `print_r()`.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value The value.
	 * @return string Safe HTML.
	 */
	public static function value( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? esc_html__( 'Yes', 'imagify-devtools' ) : esc_html__( 'No', 'imagify-devtools' );
		}

		if ( is_array( $value ) && $value && ! array_is_list( $value ) ) {
			$rows = array();

			foreach ( $value as $key => $sub_value ) {
				$rows[] = sprintf( '<tr><th>%1$s</th><td>%2$s</td></tr>', esc_html( (string) $key ), self::value( $sub_value ) );
			}

			return '<table class="imgdt-subtable"><tbody>' . implode( '', $rows ) . '</tbody></table>';
		}

		if ( is_array( $value ) ) {
			$value = $value ? implode( ', ', array_map( 'strval', $value ) ) : '';
		} elseif ( is_object( $value ) ) {
			$value = print_r( $value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_print_r
		}

		return '<pre>' . esc_html( (string) $value ) . '</pre>';
	}

	/**
	 * Human-readable anchor id for a section.
	 *
	 * @since 0.1.0
	 *
	 * @param string $section_id The section id.
	 * @return string
	 */
	public static function section_anchor( string $section_id ): string {
		return 'imgdt-section-' . $section_id;
	}
}
