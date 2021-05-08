<?php
/**
 * Plugin Name: Imagify | Beat Control
 * Description: Reduces Imagify Beat interval to once every 120 seconds.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/various/imagify-beat-control/
 * Author:      WP Media
 * Author URI:  https://wp-media.me/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2020
 */

namespace ImagifyPlugin\Helpers\various\beat_control;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

function ease_heartbeat_interval() {

	add_filter(
		'imagifybeat_settings',
		function( $settings ) {
			// EDIT_HERE
			$settings['interval'] = 120;
			// STOP_EDITING
			return $settings;
		}
	);
}
add_action( 'imagify_loaded', __NAMESPACE__ . '\ease_heartbeat_interval' );