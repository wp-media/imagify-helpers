<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || die( 'Cheatin&#8217; uh?' );

global $wpdb;

// Transients.
$transients = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_imgt%' OR option_name LIKE '%_transient_timeout_imgt%'" );

if ( is_multisite() ) {
	$transients = $wpdb->query( "DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE '%_transient_imgt%' OR meta_key LIKE '%_transient_timeout_imgt%'" );
}

// Logs.
$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'imgt_log' ) );

if ( $post_ids ) {
	$post_ids = implode( ',', $post_ids );

	// Delete Postmeta.
	$sql = sprintf( "DELETE FROM $wpdb->postmeta WHERE post_id IN (%s)", $post_ids );
	$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	// Delete Posts.
	$sql = sprintf( "DELETE FROM $wpdb->posts WHERE ID IN (%s)", $post_ids );
	$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}
