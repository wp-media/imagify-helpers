<?php
/**
 * "Image Editor Component" diagnostics section.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Provider_Image_Editor implements IMGDT_Diagnostics_Provider {

	/**
	 * Methods Imagify actually calls on the image editor, so we can flag an
	 * implementation early if one of them is missing.
	 *
	 * @since 0.1.0
	 *
	 * @return string[]
	 */
	private function required_methods(): array {
		$methods = array( 'resize', 'multi_resize', 'generate_filename', 'save' );

		if ( is_callable( 'exif_read_data' ) ) {
			$methods[] = 'rotate';
		}

		return $methods;
	}

	/**
	 * A real, existing image file to run the editor capability tests against.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	private function test_image_path(): string {
		if ( defined( 'IMAGIFY_PATH' ) && file_exists( IMAGIFY_PATH . 'assets/images/imagify-logo.png' ) ) {
			return IMAGIFY_PATH . 'assets/images/imagify-logo.png';
		}

		return ABSPATH . WPINC . '/images/media/default.png';
	}

	/**
	 * Get the image editor implementation WordPress will select, e.g. 'Imagick' or 'GD'.
	 *
	 * @since 0.2.0
	 *
	 * @return string Empty string if none qualify.
	 */
	public function get_selected(): string {
		$class = $this->get_selected_class();

		return $class ? str_replace( 'WP_Image_Editor_', '', $class ) : '';
	}

	/**
	 * Get the raw class name WordPress will select (unlike get_selected(),
	 * not stripped of the `WP_Image_Editor_` prefix) — used to tell a core
	 * class apart from a third-party plugin's own editor class.
	 *
	 * @since 0.2.0
	 *
	 * @return string Empty string if none qualify.
	 */
	public function get_selected_raw_class(): string {
		return (string) $this->get_selected_class();
	}

	/**
	 * Whether core's GD implementation passes the capability tests,
	 * independent of the current `wp_image_editors` filter/order.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public function gd_available(): bool {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';

		return $this->implementation_passes( 'WP_Image_Editor_GD', $this->test_args() );
	}

	/**
	 * Get the image editor class WordPress will select.
	 *
	 * @since 0.2.0
	 *
	 * @return string|false
	 */
	private function get_selected_class() {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

		$args = $this->test_args();

		/** This filter is documented in wp-includes/media.php. */
		$implementations = apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

		foreach ( $implementations as $implementation ) {
			if ( $this->implementation_passes( $implementation, $args ) ) {
				return $implementation;
			}
		}

		return false;
	}

	/**
	 * Build the args passed to each implementation's capability tests.
	 *
	 * @since 0.2.0
	 *
	 * @return array
	 */
	private function test_args(): array {
		return array(
			'path'       => $this->test_image_path(),
			'mime_types' => IMGDT_Tools::get_mime_types( 'image' ),
			'methods'    => $this->required_methods(),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_section(): IMGDT_Diagnostic_Section {
		$args     = $this->test_args();
		$selected = $this->get_selected_class();

		$rows = array(
			new IMGDT_Diagnostic_Row(
				__( 'Selected Image Editor', 'imagify-devtools' ),
				$selected ? str_replace( 'WP_Image_Editor_', '', $selected ) : _x( 'None', 'image editor', 'imagify-devtools' ),
				! $selected,
				sprintf(
					/* translators: 1 and 2 are values. */
					__( 'Should be %1$s or %2$s most of the time.', 'imagify-devtools' ),
					'<code>Imagick</code>',
					'<code>GD</code>'
				)
			),
		);

		$candidates = array_unique( array_filter( array_merge( array( $selected ), array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) ) ) );

		// A single implementation failing isn't a problem as long as another
		// one was actually selected — only flag the individual rows red when
		// nothing at all qualifies (the "Selected Image Editor: None" case
		// above already covers that as the real, critical failure).
		$flag_individual_failures = ! $selected;

		foreach ( $candidates as $implementation ) {
			$rows[] = $this->row_for_implementation( $implementation, $args, $flag_individual_failures );
		}

		return new IMGDT_Diagnostic_Section( 'image-editor', __( 'Image Editor Component', 'imagify-devtools' ), $rows );
	}

	/**
	 * Whether a WP_Image_Editor implementation is usable for our purposes.
	 *
	 * @since 0.1.0
	 *
	 * @param string $implementation Class name.
	 * @param array  $args           Args passed to `::test()`.
	 * @return bool
	 */
	private function implementation_passes( string $implementation, array $args ): bool {
		if ( ! call_user_func( array( $implementation, 'test' ), $args ) ) {
			return false;
		}

		foreach ( $args['mime_types'] as $mime_type ) {
			if ( ! call_user_func( array( $implementation, 'supports_mime_type' ), $mime_type ) ) {
				return false;
			}
		}

		return ! array_diff( $args['methods'], get_class_methods( $implementation ) );
	}

	/**
	 * Build the diagnostic row explaining why an implementation does/doesn't qualify.
	 *
	 * @since 0.1.0
	 *
	 * @param string $implementation Class name.
	 * @param array  $args           Args passed to `::test()`.
	 * @param bool   $flag_failure   Whether a failure on this specific implementation
	 *                                should be marked as an error row.
	 * @return IMGDT_Diagnostic_Row
	 */
	private function row_for_implementation( string $implementation, array $args, bool $flag_failure ): IMGDT_Diagnostic_Row {
		$name = str_replace( 'WP_Image_Editor_', '', $implementation );

		if ( ! call_user_func( array( $implementation, 'test' ), $args ) ) {
			return new IMGDT_Diagnostic_Row( $name, _x( 'Failed existence test.', 'image editor implementation', 'imagify-devtools' ), $flag_failure );
		}

		$unsupported_mimes = array_filter(
			$args['mime_types'],
			static fn( $mime_type ) => ! call_user_func( array( $implementation, 'supports_mime_type' ), $mime_type )
		);

		if ( $unsupported_mimes ) {
			return new IMGDT_Diagnostic_Row(
				$name,
				sprintf(
					/* translators: %s is a list of mime types. */
					_n( 'Unsupported mime type: %s.', 'Unsupported mime types: %s.', count( $unsupported_mimes ), 'imagify-devtools' ),
					implode( ', ', $unsupported_mimes )
				),
				$flag_failure
			);
		}

		$missing_methods = array_diff( $args['methods'], get_class_methods( $implementation ) );

		if ( $missing_methods ) {
			return new IMGDT_Diagnostic_Row(
				$name,
				sprintf(
					/* translators: %s is a list of method names. */
					_n( 'Unsupported method: %s.', 'Unsupported methods: %s.', count( $missing_methods ), 'imagify-devtools' ),
					implode( ', ', $missing_methods )
				),
				$flag_failure
			);
		}

		return new IMGDT_Diagnostic_Row( $name, 'OK' );
	}
}
