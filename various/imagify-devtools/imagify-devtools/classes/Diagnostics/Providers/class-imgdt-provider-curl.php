<?php
/**
 * "cURL" diagnostics section.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Provider_Curl implements IMGDT_Diagnostics_Provider {

	/**
	 * cURL feature constants worth surfacing.
	 *
	 * @see https://curl.se/libcurl/c/curl_version_info.html
	 *
	 * @var string[]
	 */
	private const FEATURES = array(
		'CURL_VERSION_ASYNCHDNS',
		'CURL_VERSION_BROTLI',
		'CURL_VERSION_GSSNEGOTIATE',
		'CURL_VERSION_HTTP2',
		'CURL_VERSION_HTTPS_PROXY',
		'CURL_VERSION_IPV6',
		'CURL_VERSION_LARGEFILE',
		'CURL_VERSION_LIBZ',
		'CURL_VERSION_MULTI_SSL',
		'CURL_VERSION_NTLM',
		'CURL_VERSION_SSL',
		'CURL_VERSION_UNIX_SOCKETS',
	);

	/**
	 * Oldest cURL version we're confident works.
	 *
	 * @var string
	 */
	private const MIN_VERSION = '7.34';

	/**
	 * {@inheritDoc}
	 */
	public function get_section(): IMGDT_Diagnostic_Section {
		$rows = array(
			IMGDT_Diagnostic_Row::compare(
				__( 'Extension loaded', 'imagify-devtools' ),
				in_array( 'curl', get_loaded_extensions(), true ),
				true
			),
			IMGDT_Diagnostic_Row::compare(
				sprintf( __( '%s exists', 'imagify-devtools' ), '<code>curl_init()</code>' ),
				function_exists( 'curl_init' ),
				true
			),
			IMGDT_Diagnostic_Row::compare(
				sprintf( __( '%s exists', 'imagify-devtools' ), '<code>curl_exec()</code>' ),
				function_exists( 'curl_exec' ),
				true
			),
		);

		if ( ! function_exists( 'curl_version' ) ) {
			$rows[] = new IMGDT_Diagnostic_Row( '<code>curl_version()</code>', __( 'The function does not exist', 'imagify-devtools' ), true );

			return new IMGDT_Diagnostic_Section( 'curl', 'cURL', $rows );
		}

		$curl_info = curl_version();

		$rows[] = new IMGDT_Diagnostic_Row(
			'<code>curl_version()</code>',
			$curl_info['version'] ?? '',
			empty( $curl_info['version'] ) || version_compare( $curl_info['version'], self::MIN_VERSION ) < 0,
			sprintf(
				/* translators: %s is a cURL version. */
				__( 'Version should be %s at least.', 'imagify-devtools' ),
				'<code>' . self::MIN_VERSION . '</code>'
			)
		);

		$features = array();

		foreach ( self::FEATURES as $feature ) {
			if ( ! defined( $feature ) ) {
				continue;
			}

			$features[ $feature ] = ( ( $curl_info['features'] ?? 0 ) & constant( $feature ) )
				? __( 'Available', 'imagify-devtools' )
				: __( 'Not available', 'imagify-devtools' );
		}

		$rows[] = new IMGDT_Diagnostic_Row( __( 'cURL features', 'imagify-devtools' ), $features );

		return new IMGDT_Diagnostic_Section( 'curl', 'cURL', $rows );
	}
}
