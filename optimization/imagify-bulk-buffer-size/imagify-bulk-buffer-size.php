<?php
/**
 * Plugin Name: Imagify bulk optimization buffer size
 * Description: In Imagify’s bulk optimization, set the number of parallel optimizations to 1 for all contexts.
 * Version: 1.0
 * Author: WP Media
 * Author URI: https://wp-media.me/
 * Licence: GPLv2
 *
 * Copyright SAS WP MEDIA 2020
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter(
	'imagify_bulk_buffer_sizes',
	function ( $buffer_sizes ) {
		if ( ! $buffer_sizes || ! is_array( $buffer_sizes ) ) {
			return [];
		}
		return array_fill_keys( array_keys( $buffer_sizes ), 1 );
	}
);
