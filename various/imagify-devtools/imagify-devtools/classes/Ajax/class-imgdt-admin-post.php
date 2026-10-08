<?php
/**
 * admin-post.php / admin-ajax.php handlers.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Registers and handles this plugin's form-post actions.
 *
 * @since 0.1.0
 */
class IMGDT_Admin_Post {

	/**
	 * Cache keys allowed to be cleared through the "Clear cache" buttons and
	 * the Triage "Refresh" action. An explicit allow-list, not the raw
	 * request value, so a crafted `key` parameter can't churn arbitrary
	 * transient names.
	 *
	 * @var string[]
	 */
	private const CLEARABLE_KEYS = array(
		'invalid_wp_metas',
		'orphan_files',
		'imagify_user',
	);

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public static function init(): void {
		add_action( 'admin_post_imgdt_clear_cache', static fn() => self::run_safely( array( __CLASS__, 'handle_clear_cache' ) ) );
		add_action( 'admin_post_imgdt_refresh', static fn() => self::run_safely( array( __CLASS__, 'handle_refresh' ) ) );
		add_action( 'admin_post_imgdt_purge_queue', static fn() => self::run_safely( array( __CLASS__, 'handle_purge_queue' ) ) );
		add_action( 'admin_post_imgdt_toggle_gd', static fn() => self::run_safely( array( __CLASS__, 'handle_toggle_gd' ) ) );
		add_action( 'admin_post_imgdt_connectivity_test', static fn() => self::run_safely( array( __CLASS__, 'handle_connectivity_test' ) ) );
		add_action( 'admin_post_nopriv_imgdt_connectivity_test', static fn() => self::run_safely( array( __CLASS__, 'handle_connectivity_test' ) ) );
		add_action( 'wp_ajax_imgdt_connectivity_test', static fn() => self::run_safely( array( __CLASS__, 'handle_connectivity_test' ) ) );
		add_action( 'wp_ajax_nopriv_imgdt_connectivity_test', static fn() => self::run_safely( array( __CLASS__, 'handle_connectivity_test' ) ) );
	}

	/**
	 * Run a handler, redirecting with a notice instead of showing a raw PHP
	 * error if it throws.
	 *
	 * @since 0.1.0
	 *
	 * @param callable $handler Handler to run.
	 */
	private static function run_safely( callable $handler ): void {
		$ok = imgdt_safe(
			static function () use ( $handler ) {
				$handler();
				return true;
			}
		);

		if ( ! $ok ) {
			wp_safe_redirect( add_query_arg( 'imgdt_error', '1', admin_url( 'admin.php?page=' . IMGDT_Admin_Pages::SLUG ) ) );
			exit;
		}
	}

	/**
	 * Handle `?action=imgdt_clear_cache&key=...`.
	 *
	 * @since 0.1.0
	 */
	public static function handle_clear_cache(): void {
		IMGDT_Capability::require_or_die();

		$key = isset( $_GET['key'] ) ? sanitize_key( wp_unslash( $_GET['key'] ) ) : '';

		check_admin_referer( 'imgdt_clear_cache_' . $key );

		if ( in_array( $key, self::CLEARABLE_KEYS, true ) || str_starts_with( $key, 'req_' ) ) {
			IMGDT_Cache::delete( $key );
		}

		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : admin_url( 'admin.php?page=' . IMGDT_Admin_Pages::SLUG ) );
		exit;
	}

	/**
	 * Handle `?action=imgdt_refresh`: clears every cached diagnostic value.
	 *
	 * @since 0.2.0
	 */
	public static function handle_refresh(): void {
		IMGDT_Capability::require_or_die();

		check_admin_referer( 'imgdt_refresh' );

		foreach ( self::CLEARABLE_KEYS as $key ) {
			IMGDT_Cache::delete( $key );
		}

		global $wpdb;

		$prefix = IMGDT_Cache::PREFIX . 'req_';

		if ( is_multisite() ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_site_transient_' . $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_site_transient_timeout_' . $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_site_transient_' . $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_site_transient_timeout_' . $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : admin_url( 'admin.php?page=' . IMGDT_Admin_Pages::SLUG ) );
		exit;
	}

	/**
	 * Handle `?action=imgdt_purge_queue`.
	 *
	 * @since 0.2.0
	 */
	public static function handle_purge_queue(): void {
		IMGDT_Capability::require_or_die();

		check_admin_referer( 'imgdt_purge_queue' );

		( new IMGDT_Queue_Purge() )->purge();

		$redirect = add_query_arg( 'imgdt_purged', '1', wp_get_referer() ?: admin_url( 'admin.php?page=' . IMGDT_Admin_Pages::SLUG ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Build the "Purge queue" URL.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public static function purge_queue_url(): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=imgdt_purge_queue' ), 'imgdt_purge_queue' );
	}

	/**
	 * Handle `?action=imgdt_toggle_gd`: turns the "prefer GD" override on or off.
	 *
	 * @since 0.2.0
	 */
	public static function handle_toggle_gd(): void {
		IMGDT_Capability::require_or_die();

		check_admin_referer( 'imgdt_toggle_gd' );

		if ( IMGDT_Image_Editor_Override::is_enabled() ) {
			IMGDT_Image_Editor_Override::disable();
		} else {
			IMGDT_Image_Editor_Override::enable();
		}

		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : admin_url( 'admin.php?page=' . IMGDT_Admin_Pages::SLUG ) );
		exit;
	}

	/**
	 * Build the "toggle prefer GD" URL.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public static function toggle_gd_url(): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=imgdt_toggle_gd' ), 'imgdt_toggle_gd' );
	}

	/**
	 * Handle the connectivity test endpoint: always responds 200, does
	 * nothing else. Used only to check whether local requests to
	 * admin-ajax.php / admin-post.php reach the server at all.
	 *
	 * @since 0.1.0
	 */
	public static function handle_connectivity_test(): void {
		status_header( 200 );
		exit;
	}

	/**
	 * Build a "Clear cache" button for a given cache key.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key Unprefixed cache key. Must be listed in CLEARABLE_KEYS,
	 *                     or start with `req_` for the per-URL request-block cache.
	 * @return string Safe HTML.
	 */
	public static function clear_cache_button( string $key ): string {
		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=imgdt_clear_cache&key=' . rawurlencode( $key ) ),
			'imgdt_clear_cache_' . $key
		);

		return sprintf(
			'<a class="button button-small imgdt-clear-cache" href="%1$s">%2$s</a> <span class="imgdt-cache-ttl">%3$s</span>',
			esc_url( $url ),
			esc_html__( 'Clear cache', 'imagify-devtools' ),
			esc_html(
				sprintf(
					/* translators: %d is a number of minutes. */
					_n( 'cached for %d more minute', 'cached for %d more minutes', IMGDT_Cache::minutes_remaining( $key ), 'imagify-devtools' ),
					IMGDT_Cache::minutes_remaining( $key )
				)
			)
		);
	}

	/**
	 * Build the Triage "Refresh" URL.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public static function refresh_url(): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=imgdt_refresh' ), 'imgdt_refresh' );
	}
}
