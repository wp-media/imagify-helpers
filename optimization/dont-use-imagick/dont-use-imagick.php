<?php
/**
 * Plugin Name: Don’t use ImageMagick
 * Description: Don't use the ImageMagick library to manipulate images (poor performances).
 * Version: 1.0
 * Author: WP Media
 * Author URI: https://wp-media.me/
 * Licence: GPLv2
 *
 * Copyright SAS WP MEDIA 2020
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'wp_image_editors', function( $editors ) {
	$editors = array_diff(
		$editors,
		[ 'WP_Image_Editor_Imagick' ]
	);
	$editors[] = 'WP_Image_Editor_Imagick';

	return $editors;
} );
