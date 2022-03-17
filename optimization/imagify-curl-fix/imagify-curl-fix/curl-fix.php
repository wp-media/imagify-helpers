<?php

/**
 * Plugin Name:       Imagify | cURL Fix
 * Description:       Sets a custom cURL timeout when the WP default is too short.
 * Plugin URI:        https://github.com/wp-media/imagify-helpers/tree/master/optimization/imagify-curl-fix/
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            WP Media
 * Author URI:        https://wp-media.me/
 * Licence:           GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

declare( strict_types=1 );

add_action( 'http_api_curl', function( $handle ) {
    curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 10000 );
    curl_setopt( $handle, CURLOPT_TIMEOUT, 10000 );
}, 1000 );
