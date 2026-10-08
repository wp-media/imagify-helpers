<?php
/**
 * Uninstall routine.
 *
 * Removes only what this plugin created: its cached transients and its
 * own settings. Never touches Imagify's own options, tables, or uploaded
 * files.
 *
 * @package Imagify_DevTools
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete every `imgdt_` site transient and this plugin's own options on
 * one site.
 *
 * @since 0.1.0
 */
function imgdt_uninstall_current_site(): void {
	global $wpdb;

	if ( is_multisite() ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
				$wpdb->esc_like( '_site_transient_imgdt_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_imgdt_' ) . '%'
			)
		);
	} else {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_site_transient_imgdt_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_imgdt_' ) . '%'
			)
		);
	}

	// Not referencing IMGDT_Image_Editor_Override::OPTION here: uninstall.php
	// runs standalone, before this plugin's autoloader is registered.
	delete_option( 'imgdt_prefer_gd' );
}

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		imgdt_uninstall_current_site();
		restore_current_blog();
	}
} else {
	imgdt_uninstall_current_site();
}
