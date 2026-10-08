<?php
/**
 * Reads the current state of Imagify's media optimization background queue.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Imagify's optimizer (`Imagify\Job\MediaOptimization`) is a
 * deliciousbrains wp-background-processing job with prefix `imagify` and
 * action `optimize_media`, giving the identifier `imagify_optimize_media`.
 * Batches are stored as options named `imagify_optimize_media_batch_%`
 * (site options on multisite, so they live in `wp_sitemeta`, not
 * `wp_options`); the process lock is the site transient
 * `imagify_optimize_media_process_lock`.
 *
 * Based on the `imagify-queue-purge` helper plugin.
 *
 * @since 0.2.0
 * @author Saransh Jain
 */
class IMGDT_Queue_Status {

	/**
	 * The background job's identifier.
	 *
	 * @var string
	 */
	public const IDENTIFIER = 'imagify_optimize_media';

	/**
	 * Count pending batches.
	 *
	 * @since 0.2.0
	 *
	 * @return int
	 */
	public function get_batch_count(): int {
		global $wpdb;

		$like = $wpdb->esc_like( self::IDENTIFIER . '_batch_' ) . '%';

		if ( is_multisite() ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $like is esc_like()'d.
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $like ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $like is esc_like()'d.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
	}

	/**
	 * Whether the process lock is currently held.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public function is_locked(): bool {
		return (bool) get_site_transient( self::IDENTIFIER . '_process_lock' );
	}

	/**
	 * Whether the queue is paused or cancelled.
	 *
	 * @since 0.2.0
	 *
	 * @return string '', 'paused', or 'cancelled'.
	 */
	public function get_status(): string {
		$status = (int) get_site_option( self::IDENTIFIER . '_status' );

		return match ( $status ) {
			1       => 'cancelled',
			2       => 'paused',
			default => '',
		};
	}

	/**
	 * Whether a cron event is scheduled to keep working through the queue.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public function has_scheduled_cron(): bool {
		return (bool) wp_next_scheduled( self::IDENTIFIER . '_cron' );
	}

	/**
	 * A batch exists, but neither a lock nor a scheduled cron event will
	 * ever pick it up — the signature of a genuinely stuck queue, not just
	 * one that's briefly busy.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public function looks_stalled(): bool {
		return $this->get_batch_count() > 0 && ! $this->is_locked() && ! $this->has_scheduled_cron();
	}

	/**
	 * Count Imagify-prefixed transients, for a general sense of leftover
	 * cached state. Shown for awareness only — the purge action does not
	 * blanket-delete these, only the specific patterns Imagify's own
	 * ResetInternalState targets.
	 *
	 * @since 0.2.0
	 *
	 * @return int
	 */
	public function get_transient_count(): int {
		global $wpdb;

		$like = $wpdb->esc_like( '_transient_imagify_' ) . '%';

		if ( is_multisite() ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $like is esc_like()'d.
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $like ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $like is esc_like()'d.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
	}

	/**
	 * Count pending/in-progress Action Scheduler items for Imagify's hooks,
	 * if Action Scheduler is present.
	 *
	 * @since 0.2.0
	 *
	 * @return int
	 */
	public function get_scheduler_count(): int {
		global $wpdb;

		$table = $wpdb->prefix . 'actionscheduler_actions';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name only, no user input.
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table is a fixed, verified table name.
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE hook IN ('imagify_optimize_media', 'imagify_convert_next_gen') AND status IN ('pending', 'in-progress')"
		);
	}
}
