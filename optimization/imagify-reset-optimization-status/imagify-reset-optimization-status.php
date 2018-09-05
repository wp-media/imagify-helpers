<?php
/**
 * Plugin Name: Imagify | Reset Optimization Status
 * Description: Will “reset” Imagify’s optimization status in the database, so that previously optimized images will be considered not optimized. Physical image files will not actually be modified! How to use: 1. Activate plugin. 2. Reload plugin page once. 3. Deactivate plugin!
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/optimization/imagify-reset-optimization-status/
 * Author:      Imagify Support Team
 * Author URI:  http://imagify.io/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2018
 */
namespace ImagifyPlugin\Helpers\optimization\status;

defined( 'ABSPATH' ) or die();

add_filter( 'init', __NAMESPACE__ . '\reset' );
/**
 * “Reset” Imagify so that images uploaded to the Media library via FTP can be optimised.
 *
 * @author Grégory Viguier
 * @author Caspar Hübinger
 *
 * @return void
 */
function reset() {

	$deleted1 = delete_metadata( 'post', '', '_imagify_status', '', true );
	$deleted2 = delete_metadata( 'post', '', '_imagify_optimization_level', '', true );
	$deleted3 = delete_metadata( 'post', '', '_imagify_data', '', true );

	if ( $deleted1 || $deleted2 || $deleted3 ) {
		wp_cache_set( 'last_changed', microtime(), 'posts' );
	}
}
