<?php
/**
 * Plugin Name: Imagify | Custom Site Root
 * Description: Fixes the site path in Imagify.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/various/imagify-custom-site-root/
 * Author:      WP Media
 * Author URI:  https://wp-media.me/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP Media 2020
 */

namespace ImagifyPlugin\Helpers\various\custom_site_root;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

/**
 * Filter the path to the site's root.
 *
 * @author Grégory Viguier
 *
 * @param string $root_path Path to the site's root. Default is null.
 */
function custom_site_root( $root_path ) {
	$upload_basedir = imagify_get_filesystem()->get_upload_basedir( true );

	if ( strpos( $upload_basedir, '/wp-content/' ) === false ) {
		return $root_path;
	}

	$upload_basedir = explode( '/wp-content/', $upload_basedir );
	$upload_basedir = reset( $upload_basedir );

	return trailingslashit( $upload_basedir );
}
add_filter( 'imagify_site_root', __NAMESPACE__ . '\custom_site_root', 10001 );