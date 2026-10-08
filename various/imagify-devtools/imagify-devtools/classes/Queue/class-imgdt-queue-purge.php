<?php
/**
 * Clears a stuck Imagify media optimization queue.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Scoped strictly to Imagify's own optimization job state: batches,
 * process lock, status flag, and scheduler hooks. Never touches Imagify's
 * settings, API key, user cache, or any other plugin's data.
 *
 * Based on the `imagify-queue-purge` helper plugin.
 *
 * @since 0.2.0
 * @author Saransh Jain
 */
class IMGDT_Queue_Purge {

	/**
	 * Clear the queue.
	 *
	 * @since 0.2.0
	 */
	public function purge(): void {
		// Imagify's own reset service: process lock, legacy RPC transients,
		// stale bulk-run flags, and Action Scheduler hooks. Multisite-aware
		// already. Deliberately does not touch settings, API key, or caches
		// unrelated to a running job.
		if ( class_exists( '\Imagify\Tools\ResetInternalState' ) ) {
			( new \Imagify\Tools\ResetInternalState() )->reset();
		}

		// ResetInternalState does not clear the batches themselves (the
		// actual queued items) -- do that here, the same multisite-aware way.
		$this->delete_batches();
	}

	/**
	 * Delete every pending batch row.
	 *
	 * @since 0.2.0
	 */
	private function delete_batches(): void {
		global $wpdb;

		$like = $wpdb->esc_like( IMGDT_Queue_Status::IDENTIFIER . '_batch_' ) . '%';

		if ( is_multisite() ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $like is esc_like()'d.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $like ) );
			return;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $like is esc_like()'d.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
	}
}
