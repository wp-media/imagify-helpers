<?php
/**
 * "Various Tests and Values" diagnostics section.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Provider_Various implements IMGDT_Diagnostics_Provider {

	/**
	 * Get the PHP memory limit in effect for optimization requests.
	 *
	 * @since 0.2.0
	 *
	 * @return string e.g. '256M'.
	 */
	public function get_memory_limit(): string {
		return (string) ini_get( 'memory_limit' );
	}

	/**
	 * Whether WP-Cron looks like it's actually running. A plugin can't see
	 * whether a real system crontab exists on the host (that's outside PHP's
	 * visibility) — the practical signal instead is whether WP-Cron's own
	 * event queue has a backlog it isn't working through.
	 *
	 * @since 0.2.0
	 *
	 * @return array{value: string, is_error: bool}
	 */
	private function get_cron_status(): array {
		if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
			return array(
				'value'    => __( 'Enabled (page-load triggered)', 'imagify-devtools' ),
				'is_error' => false,
			);
		}

		$overdue = $this->count_overdue_cron_events();

		if ( 0 === $overdue ) {
			return array(
				'value'    => __( 'Disabled, but no overdue events — an external trigger looks active', 'imagify-devtools' ),
				'is_error' => false,
			);
		}

		return array(
			'value'    => sprintf(
				/* translators: %d is a number of overdue cron events. */
				_n( 'Disabled — %d event overdue, nothing appears to be triggering it', 'Disabled — %d events overdue, nothing appears to be triggering it', $overdue, 'imagify-devtools' ),
				$overdue
			),
			'is_error' => true,
		);
	}

	/**
	 * Count scheduled cron events whose timestamp is more than an hour in
	 * the past — a backlog like that means nothing is calling `wp-cron.php`
	 * at all, not just that this request can't see a real system crontab.
	 *
	 * @since 0.2.0
	 *
	 * @return int
	 */
	private function count_overdue_cron_events(): int {
		if ( ! function_exists( '_get_cron_array' ) ) {
			return 0;
		}

		$threshold = time() - HOUR_IN_SECONDS;
		$overdue   = 0;

		foreach ( _get_cron_array() as $timestamp => $hooks ) {
			if ( ! is_int( $timestamp ) || $timestamp >= $threshold || ! is_array( $hooks ) ) {
				continue;
			}

			$overdue += array_sum( array_map( 'count', $hooks ) );
		}

		return $overdue;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_section(): IMGDT_Diagnostic_Section {
		global $wp_object_cache, $wp_version;

		$max_execution_time = (int) ini_get( 'max_execution_time' );
		$is_ssl_reported    = is_ssl();
		$is_ssl_real        = $this->is_really_ssl();
		$cron_status        = $this->get_cron_status();

		$rows = array(
			new IMGDT_Diagnostic_Row( __( 'Hosting company', 'imagify-devtools' ), $this->get_hosting_company() ),
			new IMGDT_Diagnostic_Row( __( 'WP-Cron', 'imagify-devtools' ), $cron_status['value'], $cron_status['is_error'] ),
			new IMGDT_Diagnostic_Row( __( 'PHP version', 'imagify-devtools' ), PHP_VERSION, version_compare( PHP_VERSION, '8.1' ) < 0, __( 'Should be 8.1 or higher.', 'imagify-devtools' ) ),
			new IMGDT_Diagnostic_Row( __( 'WP version', 'imagify-devtools' ), $wp_version, version_compare( $wp_version, '6.0' ) < 0 ),
			new IMGDT_Diagnostic_Row(
				__( 'Max execution time', 'imagify-devtools' ),
				$max_execution_time,
				$max_execution_time > 0 && $max_execution_time < 30,
				__( 'Should be 30 seconds or more (0 = unlimited).', 'imagify-devtools' )
			),
			new IMGDT_Diagnostic_Row(
				sprintf(
					/* translators: 1 and 2 are constant names. */
					__( 'Memory limit (%1$s / %2$s / real value)', 'imagify-devtools' ),
					'<code>WP_MEMORY_LIMIT</code>',
					'<code>WP_MAX_MEMORY_LIMIT</code>'
				),
				WP_MEMORY_LIMIT . ' / ' . WP_MAX_MEMORY_LIMIT . ' / ' . ini_get( 'memory_limit' )
			),
			new IMGDT_Diagnostic_Row(
				__( 'Uses external object cache', 'imagify-devtools' ),
				wp_using_ext_object_cache(),
				false,
				wp_using_ext_object_cache() && is_object( $wp_object_cache ) ? get_class( $wp_object_cache ) : ''
			),
			new IMGDT_Diagnostic_Row( __( 'Is multisite', 'imagify-devtools' ), is_multisite() ),
			new IMGDT_Diagnostic_Row(
				__( 'Is SSL', 'imagify-devtools' ),
				$is_ssl_reported,
				$is_ssl_reported !== $is_ssl_real,
				$is_ssl_reported !== $is_ssl_real
					? sprintf(
						/* translators: %s is a function name. */
						__( '%s returns a result that disagrees with the admin URL scheme — could indicate a reverse-proxy/SSL termination issue.', 'imagify-devtools' ),
						'<code>is_ssl()</code>'
					)
					: ''
			),
			new IMGDT_Diagnostic_Row( __( 'Your user ID', 'imagify-devtools' ), get_current_user_id() ),
			new IMGDT_Diagnostic_Row( __( 'Your IP address', 'imagify-devtools' ), imgdt_get_ip() ),
			new IMGDT_Diagnostic_Row( __( 'Imagify settings', 'imagify-devtools' ), $this->redact( get_site_option( 'imagify_settings' ) ) ),
			new IMGDT_Diagnostic_Row(
				__( 'Imagify user', 'imagify-devtools' ),
				$this->get_imagify_user(),
				false,
				'',
				IMGDT_Admin_Post::clear_cache_button( 'imagify_user' )
			),
			new IMGDT_Diagnostic_Row( '$_SERVER', $this->sanitize( $this->redact( $_SERVER ) ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- sanitized recursively below.
		);

		return new IMGDT_Diagnostic_Section( 'various', __( 'Various Tests and Values', 'imagify-devtools' ), $rows );
	}

	/**
	 * Mask values that can hold credentials or a live session: the Imagify
	 * API key in the settings, and cookies, auth headers and credential-style
	 * environment variables in `$_SERVER`. This output ends up in
	 * screenshots, the Copy report and the PDF, all of which get sent to
	 * support.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $data Value to redact; anything other than an array is returned unchanged.
	 * @return mixed
	 */
	private function redact( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $value ) {
			if ( is_string( $key ) && preg_match( '/api_?key|cookie|auth|pass|secret|token|session/i', $key ) ) {
				$data[ $key ] = '[redacted]';
			}
		}

		return $data;
	}

	/**
	 * Recursively sanitize a value for display.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $data Value to sanitize.
	 * @return mixed
	 */
	private function sanitize( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'sanitize' ), $data );
		}

		if ( is_numeric( $data ) ) {
			return $data + 0;
		}

		if ( is_string( $data ) ) {
			return sanitize_text_field( wp_unslash( $data ) );
		}

		return $data;
	}

	/**
	 * A more reliable SSL check than `is_ssl()` alone: also trusts the admin URL scheme,
	 * which correctly reflects `FORCE_SSL_ADMIN` and reverse-proxy configuration.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function is_really_ssl(): bool {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ) ) {
			return true;
		}

		return is_ssl() || 0 === strpos( admin_url( 'admin-ajax.php' ), 'https://' );
	}

	/**
	 * Try to guess the hosting company from well-known constants/classes.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	private function get_hosting_company(): string {
		return match ( true ) {
			defined( 'IS_PRESSABLE' ) => 'Pressable',
			defined( 'FLYWHEEL_CONFIG_DIR' ) => 'Flywheel',
			class_exists( '\WPaaS\Plugin' ) => 'GoDaddy',
			defined( 'DB_HOST' ) && str_contains( DB_HOST, '.infomaniak.com' ) => 'Infomaniak',
			isset( $_SERVER['KINSTA_CACHE_ZONE'] ) => 'Kinsta',
			defined( 'O2SWITCH_VARNISH_PURGE_KEY' ) => 'o2switch',
			isset( $_SERVER['ONECOM_DOCUMENT_ROOT'] ) => 'One.com',
			class_exists( 'PagelyCachePurge' ) => 'Pagely',
			defined( 'WP_NINUKIS_WP_NAME' ) => 'Pressidium',
			class_exists( '\Savvii\Options' ) => 'Savvii',
			class_exists( 'SG_CachePress_Environment' ) => 'SiteGround',
			class_exists( 'WpeCommon' ) => 'WP Engine',
			defined( 'DB_HOST' ) && str_contains( DB_HOST, '.wpserveur.net' ) => 'WPServeur',
			defined( 'WPCOMSH_VERSION' ) => 'WordPress.com',
			! empty( $_SERVER['SERVER_ADDR'] ) && in_array( $_SERVER['SERVER_ADDR'], array( '127.0.0.1', '::1' ), true ) => 'localhost',
			default => __( 'Unknown', 'imagify-devtools' ),
		};
	}

	/**
	 * Get (and cache) the Imagify user data, as reported by Imagify's own API client.
	 *
	 * @since 0.1.0
	 *
	 * @return mixed
	 */
	private function get_imagify_user() {
		if ( ! function_exists( 'get_imagify_user' ) ) {
			return __( 'Needs Imagify to be active.', 'imagify-devtools' );
		}

		$cached = IMGDT_Cache::get( 'imagify_user' );

		if ( false !== $cached ) {
			return $cached;
		}

		$user = get_imagify_user();

		IMGDT_Cache::set( 'imagify_user', $user );

		return $user;
	}
}
