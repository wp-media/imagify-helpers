<?php
/**
 * Plugin Name: Imagify | Queue Purge
 * Description: Monitor and clear stuck optimization batches, transients, and scheduled background actions safely.
 * Author: WP Media Support
 * Version: 1.0.0
 * License: GPLv2 or later
 * Text Domain: imagify-queue-purge
 */

namespace ImagifyPlugin\Helpers\QueuePurge;

defined( 'ABSPATH' ) || die();

class Imagify_Queue_Purge_Tool {

	/**
	 * Initialize hooks and actions.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_purge_menu' ] );
		add_action( 'admin_post_imagify_queue_purge_action', [ $this, 'process_queue_purge' ] );
	}

	/**
	 * Register the standalone tools page.
	 */
	public function register_purge_menu() {
		add_management_page(
			'Imagify Queue Purge',
			'Imagify Queue Purge',
			'manage_options',
			'imagify-queue-purge',
			[ $this, 'render_purge_ui' ]
		);
	}

	/**
	 * Fetch counts of stuck or pending processes from database.
	 *
	 * @return array
	 */
	private function get_queue_state_counts() {
		global $wpdb;

		// Count media optimization batches.
		$batches = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE 'imagify_optimize_media_batch_%'" );

		// Count temporary imagify transients.
		$transients = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_transient_imagify_%'" );

		// Count pending or active action scheduler items.
		$as_jobs = 0;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}actionscheduler_actions'" ) ) {
			$as_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}actionscheduler_actions WHERE hook IN ('imagify_optimize_media', 'imagify_convert_next_gen') AND status IN ('pending', 'in-progress')" );
		}

		return [
			'batches'    => (int) $batches,
			'transients' => (int) $transients,
			'as_jobs'    => (int) $as_jobs,
		];
	}

	/**
	 * Render the administration control panel view.
	 */
	public function render_purge_ui() {
		$counts    = $this->get_queue_state_counts();
		$purge_url = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_queue_purge_action' ), 'imagify_purge_secure_nonce' );
		?>
		<div class="wrap">
			<h1>Imagify Queue Purge Engine</h1>
			
			<div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px;">
				<h2>Current Optimization Pipeline State</h2>
				<p>Monitor and terminate stuck queues or background processes in real time.</p>
				
				<table class="widefat fixed" style="margin-bottom: 20px; margin-top: 15px;">
					<thead>
						<tr>
							<th>Resource Type</th>
							<th>Active Queue Count</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong>Media Batches</strong> (In-flight DB optimization blocks)</td>
							<td><mark style="background-color: #fff9c4; padding: 2px 6px; font-weight: bold;"><?php echo esc_html( $counts['batches'] ); ?></mark></td>
						</tr>
						<tr>
							<td><strong>Background Actions</strong> (Action Scheduler items)</td>
							<td><mark style="background-color: #fff9c4; padding: 2px 6px; font-weight: bold;"><?php echo esc_html( $counts['as_jobs'] ); ?></mark></td>
						</tr>
						<tr>
							<td><strong>Temporary Transients</strong> (Cached application states)</td>
							<td><mark style="background-color: #fff9c4; padding: 2px 6px; font-weight: bold;"><?php echo esc_html( $counts['transients'] ); ?></mark></td>
						</tr>
					</tbody>
				</table>

				<p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">
					If the counts above stay stuck despite no active optimization running, use the force purge option below. This will clear the pipeline but will not alter your optimized images.
				</p>
				
				<a href="<?php echo esc_url( $purge_url ); ?>" 
				   class="button button-primary" 
				   onclick="return confirm('Confirming will force clear all pending optimization request pipelines. Continue?');">
					Clear & Purge All Queues
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Process the database and queue cleanup execution safely.
	 */
	public function process_queue_purge() {
		if ( ! current_user_can( 'manage_options' ) ) { 
			wp_die( 'Unauthorized action.' ); 
		}
		
		check_admin_referer( 'imagify_purge_secure_nonce' );

		global $wpdb;

		// 1. Flush temporary transients.
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_imagify_%' OR option_name LIKE '_transient_timeout_imagify_%'" );

		// 2. Erase processing database batches.
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'imagify_optimize_media_batch_%'" );

		// 3. Unschedule background action scheduler tasks.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'imagify_optimize_media' );
			as_unschedule_all_actions( 'imagify_convert_next_gen' );
		}

		// 4. Clear object cache memory tracks.
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		wp_safe_redirect( admin_url( 'tools.php?page=imagify-queue-purge&purge=success' ) );
		exit;
	}
}

new Imagify_Queue_Purge_Tool();

/**
 * Render administrative dashboard notice upon successful execution.
 */
add_action( 'admin_notices', function() {
	if ( isset( $_GET['purge'] ) && 'success' === $_GET['purge'] ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>Imagify Queue Purge:</strong> Optimization pipeline cleared and background state refreshed successfully.</p></div>';
	}
});
