<?php
/**
 * "Imagify Filesystem" diagnostics section.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Provider_Imagify_Filesystem implements IMGDT_Diagnostics_Provider {

	/**
	 * {@inheritDoc}
	 */
	public function get_section(): IMGDT_Diagnostic_Section {
		$filesystem = imgdt_get_filesystem();
		$is_imagify = $filesystem instanceof Imagify_Filesystem;
		$rows       = array();

		if ( ! $is_imagify ) {
			$rows[] = new IMGDT_Diagnostic_Row(
				__( 'Imagify Filesystem is unavailable', 'imagify-devtools' ),
				'',
				true,
				__( 'Activate Imagify to get more data here.', 'imagify-devtools' )
			);

			return new IMGDT_Diagnostic_Section( 'imagify-filesystem', __( 'Imagify Filesystem', 'imagify-devtools' ), $rows );
		}

		$internal_path_test = ABSPATH;

		if ( method_exists( $filesystem, 'has_wp_its_own_directory' ) ) {
			$rows[] = new IMGDT_Diagnostic_Row(
				__( 'WP has its own directory', 'imagify-devtools' ),
				$filesystem->has_wp_its_own_directory()
			);
		}

		if ( method_exists( $filesystem, 'get_root' ) ) {
			$root   = $filesystem->get_root();
			$rows[] = new IMGDT_Diagnostic_Row(
				'$filesystem->get_root() (<em>' . __( "Server's root", 'imagify-devtools' ) . '</em>)',
				$root,
				! path_is_absolute( $root ),
				__( 'Should be an absolute path.', 'imagify-devtools' )
			);

			$internal_path_test = $root;
		}

		if ( method_exists( $filesystem, 'get_site_root' ) ) {
			$site_root = $filesystem->get_site_root();
			$rows[]    = new IMGDT_Diagnostic_Row(
				'$filesystem->get_site_root() (<em>' . __( "Site's root", 'imagify-devtools' ) . '</em>)',
				$site_root,
				0 !== strpos( $site_root, $internal_path_test ) || ! path_is_absolute( $site_root ),
				sprintf(
					/* translators: %s is a file path. */
					__( 'Should be an absolute path and start with %s.', 'imagify-devtools' ),
					'<code>' . $internal_path_test . '</code>'
				)
			);

			$internal_path_test = $site_root;
		}

		if ( method_exists( $filesystem, 'get_abspath' ) ) {
			$abspath = $filesystem->get_abspath();
			$rows[]  = new IMGDT_Diagnostic_Row(
				'$filesystem->get_abspath() (<em>' . __( "WordPress' root", 'imagify-devtools' ) . '</em>)',
				$abspath,
				0 !== strpos( $abspath, $internal_path_test ) || ! path_is_absolute( $abspath ),
				sprintf(
					/* translators: %s is a file path. */
					__( 'Should be an absolute path and start with %s.', 'imagify-devtools' ),
					'<code>' . $internal_path_test . '</code>'
				)
			);
		}

		$has_errors = is_object( $filesystem ) && isset( $filesystem->errors ) && $filesystem->errors instanceof WP_Error
			? (bool) $filesystem->errors->has_errors()
			: false;

		$rows[] = new IMGDT_Diagnostic_Row(
			'imagify_get_filesystem()',
			get_class( $filesystem ),
			$has_errors,
			sprintf(
				/* translators: 1 and 2 are data names. */
				__( '%1$s and %2$s should be empty.', 'imagify-devtools' ),
				'<code>WP_Error->errors</code>',
				'<code>WP_Error->error_data</code>'
			)
		);

		return new IMGDT_Diagnostic_Section( 'imagify-filesystem', __( 'Imagify Filesystem', 'imagify-devtools' ), $rows );
	}
}
