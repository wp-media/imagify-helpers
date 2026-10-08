<?php
/**
 * "WordPress Filesystem" diagnostics section.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Provider_Filesystem implements IMGDT_Diagnostics_Provider {

	/**
	 * {@inheritDoc}
	 */
	public function get_section(): IMGDT_Diagnostic_Section {
		imgdt_get_filesystem(); // Makes sure FS_CHMOD_DIR / FS_CHMOD_FILE are defined.

		$error_string  = '*** ' . __( 'Error', 'imagify-devtools' ) . ' ***';
		$wp_upload_dir = wp_parse_args(
			wp_upload_dir(),
			array(
				'path'    => $error_string,
				'url'     => $error_string,
				'subdir'  => $error_string,
				'basedir' => $error_string,
				'baseurl' => $error_string,
				'error'   => $error_string,
			)
		);

		if ( '' === $wp_upload_dir['error'] ) {
			$wp_upload_dir['error'] = '*** ' . __( 'empty string', 'imagify-devtools' ) . ' ***';
		} elseif ( false === $wp_upload_dir['error'] ) {
			$wp_upload_dir['error'] = 'false (boolean)';
		}

		$chmod_dir         = fileperms( ABSPATH ) & 0777 | 0755;
		$chmod_file        = fileperms( ABSPATH . 'index.php' ) & 0777 | 0644;
		$backup_dir        = trailingslashit( $wp_upload_dir['basedir'] ) . 'backup/';
		$backup_dir_exists = file_exists( $backup_dir ) && wp_is_writable( $backup_dir );
		$imagify_settings  = get_site_option( 'imagify_settings' );
		$backup_enabled    = ! empty( $imagify_settings['backup'] );

		$rows = array(
			new IMGDT_Diagnostic_Row(
				'ABSPATH',
				ABSPATH,
				! path_is_absolute( ABSPATH ),
				__( 'Should be an absolute path.', 'imagify-devtools' )
			),
			new IMGDT_Diagnostic_Row(
				'IMAGIFY_PATH',
				defined( 'IMAGIFY_PATH' ) ? IMAGIFY_PATH : __( 'Not defined', 'imagify-devtools' ),
				defined( 'IMAGIFY_PATH' ) && ! path_is_absolute( IMAGIFY_PATH ),
				__( 'Should be an absolute path.', 'imagify-devtools' )
			),
			new IMGDT_Diagnostic_Row(
				'wp_upload_dir() <em>(path)</em>',
				$wp_upload_dir['path'],
				$error_string === $wp_upload_dir['path'] || ! path_is_absolute( $wp_upload_dir['path'] ),
				__( 'Should be an absolute path.', 'imagify-devtools' )
			),
			new IMGDT_Diagnostic_Row(
				'wp_upload_dir() <em>(url)</em>',
				$wp_upload_dir['url'],
				$error_string === $wp_upload_dir['url'] || ! filter_var( $wp_upload_dir['url'], FILTER_VALIDATE_URL ),
				__( 'Should be a valid URL.', 'imagify-devtools' )
			),
			new IMGDT_Diagnostic_Row(
				'wp_upload_dir() <em>(basedir)</em>',
				$wp_upload_dir['basedir'],
				$error_string === $wp_upload_dir['basedir'] || ! path_is_absolute( $wp_upload_dir['basedir'] ),
				__( 'Should be an absolute path.', 'imagify-devtools' )
			),
			new IMGDT_Diagnostic_Row(
				'wp_upload_dir() <em>(baseurl)</em>',
				$wp_upload_dir['baseurl'],
				$error_string === $wp_upload_dir['baseurl'] || ! filter_var( $wp_upload_dir['baseurl'], FILTER_VALIDATE_URL ),
				__( 'Should be a valid URL.', 'imagify-devtools' )
			),
			IMGDT_Diagnostic_Row::compare(
				'wp_upload_dir() <em>(error)</em>',
				$wp_upload_dir['error'],
				'false (boolean)',
				sprintf(
					/* translators: %s is a value. */
					__( 'Should be %s.', 'imagify-devtools' ),
					'<code>false (boolean)</code>'
				)
			),
			new IMGDT_Diagnostic_Row(
				__( 'Backups folder exists and is writable', 'imagify-devtools' ),
				$backup_dir_exists,
				$backup_enabled && ! $backup_dir_exists,
				$backup_enabled
					? __( 'Backup is enabled.', 'imagify-devtools' )
					: __( 'No need, backup is disabled.', 'imagify-devtools' )
			),
			IMGDT_Diagnostic_Row::compare(
				'FS_CHMOD_DIR',
				IMGDT_Tools::to_octal( FS_CHMOD_DIR ) . ' (' . FS_CHMOD_DIR . ')',
				IMGDT_Tools::to_octal( $chmod_dir ) . ' (' . $chmod_dir . ')',
				sprintf(
					/* translators: %s is a value. */
					__( 'Should be %s.', 'imagify-devtools' ),
					'<code>' . IMGDT_Tools::to_octal( $chmod_dir ) . ' (' . $chmod_dir . ')</code>'
				)
			),
			IMGDT_Diagnostic_Row::compare(
				'FS_CHMOD_FILE',
				IMGDT_Tools::to_octal( FS_CHMOD_FILE ) . ' (' . FS_CHMOD_FILE . ')',
				IMGDT_Tools::to_octal( $chmod_file ) . ' (' . $chmod_file . ')',
				sprintf(
					/* translators: %s is a value. */
					__( 'Should be %s.', 'imagify-devtools' ),
					'<code>' . IMGDT_Tools::to_octal( $chmod_file ) . ' (' . $chmod_file . ')</code>'
				)
			),
		);

		return new IMGDT_Diagnostic_Section( 'filesystem', __( 'WordPress Filesystem', 'imagify-devtools' ), $rows );
	}
}
