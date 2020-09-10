<?php
/**
 * Plugin Name: Imagify Custom Site Root
 * Description: Fix the site path in Imagify.
 * Version: 1.0
 * Author: WP Media
 * Author URI: https://wp-media.me/
 * Licence: GPLv2
 *
 * Copyright 2020 WP Media
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'imagify_site_root', 'imgf_custom_site_root', 10001 );
/**
 * Filter the path to the site's root.
 *
 * @since  1.0
 * @author Grégory Viguier
 *
 * @param string $root_path Path to the site's root. Default is null.
 */
function imgf_custom_site_root( $root_path ) {
	$upload_basedir = imagify_get_filesystem()->get_upload_basedir( true );

	if ( strpos( $upload_basedir, '/wp-content/' ) === false ) {
		return $root_path;
	}

	$upload_basedir = explode( '/wp-content/', $upload_basedir );
	$upload_basedir = reset( $upload_basedir );

	return trailingslashit( $upload_basedir );
}
