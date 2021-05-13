<?php
/**
 * Plugin Name: Imagify | Remove Thumbnail Sizes
 * Description: Prevents 1536x1536 and 2048x2048 thumbnail sizes from being generated by WordPress. Can be edited to prevent other thumbnail sizes as well.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/various/imagify-remove-thumbnail-sizes/
 * Author:      WP Media
 * Author URI:  https://wp-media.me/
 * Licence:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP Media 2020
 */

namespace Imagify\Helpers\various\remove_thumbnail_sizes;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

$thumb_names = array(
	// EDIT_HERE
	'1536x1536',
	'2048x2048'
	// STOP_EDITING
);

add_filter( 'big_image_size_threshold', '__return_false' );

add_filter(
	'intermediate_image_sizes_advanced',
	function( $sizes ) use ( $thumb_names ) {
		foreach ( $thumb_names as $thumb_name ) {
			unset( $sizes[$thumb_name] );
		}
		return $sizes;
	}
);

add_action(
	'init',
	function() use ( $thumb_names ) {
		foreach( $thumb_names as $thumb_name ) {
			remove_image_size( $thumb_name );
		}
	}
);