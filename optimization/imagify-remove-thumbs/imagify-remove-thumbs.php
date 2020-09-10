<?php
/**
 * Plugin Name: Imagify helper - Remove image sizes 1536&2048px
 * Plugin URI: https://wordpress.org/plugins/imagify/
 * Description: A WordPress plugin that removes specific thumbnail sizes.
 * Version: 1.1
 * Author: WP Media
 * Author URI: https://wp-media.me/
 * Licence: GPLv2
 *
 * Text Domain: imagify-remove-thumbs
 *
 * Copyright SAS WP MEDIA 2020
 */

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'big_image_size_threshold', '__return_false' );

function remove_default_image_sizes( $sizes) {
    unset( $sizes['1536x1536']);
	unset( $sizes['2048x2048']);
	return $sizes;
}

add_filter('intermediate_image_sizes_advanced', 'remove_default_image_sizes');
	
function remove_images_sizes() {
    remove_image_size( '1536x1536' );
	remove_image_size( '2048x2048' );
}
add_action('init', 'remove_images_sizes');