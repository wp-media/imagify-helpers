<?php
/**
 * Small stateless helpers shared by several diagnostic providers.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Tools {

	/**
	 * Format a chmod integer as a 4-digit octal string, e.g. `0755`.
	 *
	 * @since 0.1.0
	 *
	 * @param int $chmod A chmod value, e.g. the result of `fileperms()`.
	 * @return string
	 */
	public static function to_octal( int $chmod ): string {
		return '0' . decoct( $chmod & 0777 );
	}

	/**
	 * Get the list of mime types Imagify (and this plugin) cares about.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Optional. 'image' to restrict to image types.
	 * @return array<string, string> extension(s) => mime type.
	 */
	public static function get_mime_types( string $type = '' ): array {
		$mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
			'avif'         => 'image/avif',
		);

		if ( 'image' === $type ) {
			return $mimes;
		}

		return $mimes + array( 'pdf' => 'application/pdf' );
	}

	/**
	 * Get the list of registered thumbnail (image) sizes.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array{width:int, height:int, crop:bool}>
	 */
	public static function get_thumbnail_sizes(): array {
		$sizes = array();

		foreach ( get_intermediate_image_sizes() as $size ) {
			$sizes[ $size ] = array(
				'width'  => (int) get_option( "{$size}_size_w" ),
				'height' => (int) get_option( "{$size}_size_h" ),
				'crop'   => (bool) get_option( "{$size}_crop" ),
			);
		}

		global $_wp_additional_image_sizes;

		if ( $_wp_additional_image_sizes ) {
			foreach ( $_wp_additional_image_sizes as $size => $data ) {
				if ( isset( $sizes[ $size ] ) ) {
					continue;
				}

				$sizes[ $size ] = array(
					'width'  => (int) ( $data['width'] ?? 0 ),
					'height' => (int) ( $data['height'] ?? 0 ),
					'crop'   => (bool) ( $data['crop'] ?? false ),
				);
			}
		}

		return $sizes;
	}

	/**
	 * Get the post statuses used for attachments in the various DB counts.
	 *
	 * @since 0.1.0
	 *
	 * @return string[]
	 */
	public static function get_post_statuses(): array {
		return array( 'inherit', 'private' );
	}

	/**
	 * Get the main site ID (blog ID 1 on a fresh multisite, but not always).
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public static function get_main_site_id(): int {
		if ( ! is_multisite() ) {
			return get_current_blog_id();
		}

		if ( function_exists( 'get_main_site_id' ) ) {
			return get_main_site_id();
		}

		return (int) get_network()->site_id;
	}
}
