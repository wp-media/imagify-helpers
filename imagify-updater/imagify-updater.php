<?php
/**
 * Plugin Name: Imagify | Update handler
 * Description: Simulate the update process by providing the new version details with the link to zip and it'll show the update notice.
 * Plugin URI:  http://imagify.io/
 * Author:      Imagify Support Team
 * Author URI:  http://imagify.io/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2023
 */

defined( 'ABSPATH' ) or die();

define( 'IMAGIFY_HELPER_UPDATE_NEW_VERSION', '2.2.0' );
define( 'IMAGIFY_HELPER_UPDATE_NEW_ZIP_URL', 'https://mega.wp-rocket.me/imagify-plugin_2.2.zip' );

function imagify_refresh_update() {
    if ( ! defined( 'IMAGIFY_VERSION' ) || version_compare( IMAGIFY_VERSION, IMAGIFY_HELPER_UPDATE_NEW_VERSION, '>=' ) ) {
        return;
    }
    $tmp_obj = (object) array(
        'id'           => 'w.org/plugins/imagify',
        'slug'         => 'imagify',
        'plugin'       => 'imagify/imagify.php',
        'new_version'  => IMAGIFY_HELPER_UPDATE_NEW_VERSION,
        'url'          => 'https://wordpress.org/plugins/imagify/',
        'package'      => IMAGIFY_HELPER_UPDATE_NEW_ZIP_URL,
        'icons' =>
            array (
                '1x' => 'https://ps.w.org/imagify/assets/icon.svg?rev=2833113',
                'svg' => 'https://ps.w.org/imagify/assets/icon.svg?rev=2833113',
            ),
        'banners' =>
            array (
                '2x' => 'https://ps.w.org/imagify/assets/banner-1544x500.png?rev=2759224',
                '1x' => 'https://ps.w.org/imagify/assets/banner-772x250.png?rev=2759224',
            ),
        'banners_rtl' => array (),
        'requires'     => '5.3',
        'tested'       => '6.4.1',
        'requires_php' => '7.0',
    );
    $plugin_transient = get_site_transient( 'update_plugins' );
    $plugin_transient->response[ 'imagify/imagify.php' ] = $tmp_obj;
    $plugin_transient->last_checked = time();
    unset( $plugin_transient->no_update[ 'imagify/imagify.php' ] );
    remove_action( 'set_site_transient_update_plugins', 'imagify_refresh_update', 11 );
    set_site_transient( 'update_plugins', $plugin_transient );
    add_action( 'set_site_transient_update_plugins', 'imagify_refresh_update', 11 );
}

add_action( 'set_site_transient_update_plugins', 'imagify_refresh_update', 11 );

register_activation_hook( __FILE__, function () {
    delete_site_transient( 'update_plugins' );
} );

register_deactivation_hook( __FILE__, function () {
    delete_site_transient( 'update_plugins' );
} );