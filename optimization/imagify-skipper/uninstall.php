<?php
/**
 * Uninstall routine for Imagify Skipper.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || die();

delete_option( 'imagify_skipper_settings' );
delete_site_option( 'imagify_skipper_settings' );
