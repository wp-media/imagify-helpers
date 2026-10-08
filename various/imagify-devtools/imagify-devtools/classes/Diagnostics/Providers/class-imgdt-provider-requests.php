<?php
/**
 * "Requests Tests" diagnostics section.
 *
 * Checks whether outgoing/incoming HTTP requests to Imagify's servers, and
 * to this site's own admin-ajax.php / admin-post.php / wp-cron.php, are
 * blocked by a firewall, .htaccess, the host, or Cloudflare.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Provider_Requests implements IMGDT_Diagnostics_Provider {

	/**
	 * How long a "is this URL blocked" result is cached for.
	 *
	 * @var int
	 */
	private const CACHE_DURATION = IMGDT_Cache::DEFAULT_DURATION;

	/**
	 * {@inheritDoc}
	 */
	public function get_section(): IMGDT_Diagnostic_Section {
		$blocked_by_constant = $this->is_blocked_by_constant();

		$rows = array(
			new IMGDT_Diagnostic_Row(
				sprintf(
					/* translators: 1 and 2 are constant names. */
					__( 'Imagify blocked by %1$s / %2$s', 'imagify-devtools' ),
					'<code>WP_HTTP_BLOCK_EXTERNAL</code>',
					'<code>WP_ACCESSIBLE_HOSTS</code>'
				),
				$blocked_by_constant,
				$blocked_by_constant
			),
			new IMGDT_Diagnostic_Row(
				sprintf( __( 'Value of the filter %s', 'imagify-devtools' ), '<code>https_ssl_verify</code>' ),
				apply_filters( 'https_ssl_verify', false ) ? 'true' : 'false'
			),
			new IMGDT_Diagnostic_Row(
				sprintf( __( 'Value of the filter %s', 'imagify-devtools' ), '<code>https_local_ssl_verify</code>' ),
				apply_filters( 'https_local_ssl_verify', false ) ? 'true' : 'false'
			),
		);

		foreach ( $this->get_checks() as $check ) {
			$rows[] = $this->build_row( $check );
		}

		return new IMGDT_Diagnostic_Section( 'requests', __( 'Requests Tests', 'imagify-devtools' ), $rows );
	}

	/**
	 * Whether Imagify's own domains are blocked by the `WP_HTTP_BLOCK_EXTERNAL`
	 * constant, per Imagify's own (already correct, already-cached) check —
	 * a definitive root cause rather than something to infer from which
	 * requests below happen to be blocked.
	 *
	 * @since 0.2.0
	 *
	 * @return bool False (not blocked, or Imagify isn't active to ask).
	 */
	public function is_blocked_by_constant(): bool {
		if ( ! class_exists( '\Imagify_Requirements' ) || ! method_exists( '\Imagify_Requirements', 'is_imagify_blocked' ) ) {
			return false;
		}

		return (bool) \Imagify_Requirements::is_imagify_blocked();
	}

	/**
	 * URLs and methods this provider checks. Each is tagged with a category:
	 * 'external' for genuine outbound requests to Imagify's own servers
	 * (fixed by whitelisting Imagify's domains), 'loopback' for the site
	 * calling its own admin-ajax.php/admin-post.php/wp-cron.php (fixed on
	 * the hosting/firewall/security-plugin/CDN side — whitelisting Imagify
	 * does nothing for this one).
	 *
	 * @since 0.2.0
	 *
	 * @return array<array{url: string, method: string, category: string}>
	 */
	private function get_checks(): array {
		$checks = array(
			array(
				'url'      => 'https://imagify.io',
				'method'   => 'GET',
				'category' => 'external',
			),
			array(
				'url'      => 'https://app.imagify.io/api/version/',
				'method'   => 'GET',
				'category' => 'external',
			),
		);

		foreach (
			array(
				admin_url( 'admin-ajax.php?action=imgdt_connectivity_test' ),
				admin_url( 'admin-post.php?action=imgdt_connectivity_test' ),
				site_url( 'wp-cron.php' ),
			) as $url
		) {
			$checks[] = array(
				'url'      => $url,
				'method'   => 'POST',
				'category' => 'loopback',
			);
		}

		return $checks;
	}

	/**
	 * Short, plain-text note appended to a loopback check's label — these
	 * carry this plugin's own user-agent, not a plain default WP request, so
	 * a block here means "this specific request is blocked," not
	 * necessarily "every kind of self-triggered WP traffic is blocked."
	 *
	 * @since 0.2.0
	 *
	 * @param string $category One of the categories from get_checks().
	 * @return string Empty string for categories that need no note.
	 */
	private function category_suffix( string $category ): string {
		return 'loopback' === $category ? __( ' (custom request)', 'imagify-devtools' ) : '';
	}

	/**
	 * Get every currently blocked check, as short label/detail/category
	 * tuples, for the Triage summary.
	 *
	 * @since 0.2.0
	 *
	 * @return array<array{label: string, detail: string, category: string, cloudflare: string}>
	 */
	public function get_blocked_checks(): array {
		$blocked = array();

		foreach ( $this->get_checks() as $check ) {
			$result = $this->check_url( $check );

			if ( ! $result['blocked'] ) {
				continue;
			}

			$blocked[] = array(
				'label'      => $check['method'] . ' ' . strtok( $check['url'], '?' ) . $this->category_suffix( $check['category'] ),
				'detail'     => $result['message'],
				'category'   => $check['category'],
				'cloudflare' => $result['cloudflare'],
			);
		}

		return $blocked;
	}

	/**
	 * Build one "is this URL blocked" row, including its "Clear cache" action.
	 *
	 * @since 0.1.0
	 *
	 * @param array{url: string, method: string, category: string} $check One entry from get_checks().
	 * @return IMGDT_Diagnostic_Row
	 */
	private function build_row( array $check ): IMGDT_Diagnostic_Row {
		$result      = $this->check_url( $check );
		$cache_key   = $this->cache_key( $check['url'], $check['method'] );
		$display_url = strtok( $check['url'], '?' );
		$suffix      = $this->category_suffix( $check['category'] );

		$label = sprintf(
			/* translators: 1 is $_GET or $_POST, 2 is a URL, 3 is an optional short note. */
			__( '%1$s requests to %2$s blocked%3$s', 'imagify-devtools' ),
			'GET' === $check['method'] ? '<code>$_GET</code>' : '<code>$_POST</code>',
			'<code>' . $display_url . '</code>',
			$suffix ? ' <em>' . esc_html( $suffix ) . '</em>' : ''
		);

		$more_info = '';

		if ( $result['blocked'] ) {
			if ( 'network' === $result['kind'] ) {
				$more_info = sprintf(
					/* translators: %s is an error message. */
					__( 'Request returned an error: %s', 'imagify-devtools' ),
					'<code>' . esc_html( $result['message'] ) . '</code>'
				);
			} elseif ( 'challenged' === $result['cloudflare'] ) {
				$more_info = sprintf(
					/* translators: %s is a response header. */
					__( 'Blocked by Cloudflare (%s).', 'imagify-devtools' ),
					'<code>cf-mitigated: challenge</code>'
				);
			} else {
				$more_info = sprintf(
					/* translators: 1 is a file name, 2 is an HTTP status code and reason. */
					__( 'Blocked by %1$s, a firewall, or the host (%2$s).', 'imagify-devtools' ),
					'<code>.htaccess</code>',
					esc_html( $result['message'] )
				);

				if ( 'proxied' === $result['cloudflare'] ) {
					$more_info .= ' ' . __( 'Cloudflare: proxied.', 'imagify-devtools' );
				}
			}
		}

		return new IMGDT_Diagnostic_Row(
			$label,
			$result['blocked'],
			$result['blocked'],
			$more_info,
			IMGDT_Admin_Post::clear_cache_button( $cache_key )
		);
	}

	/**
	 * Cache key for one URL/method check.
	 *
	 * @since 0.2.0
	 *
	 * @param string $url    The URL.
	 * @param string $method 'GET' or 'POST'.
	 * @return string
	 */
	private function cache_key( string $url, string $method ): string {
		return 'req_' . substr( md5( $url . '|' . $method ), 0, 10 );
	}

	/**
	 * Check whether a URL is blocked, caching the result.
	 *
	 * @since 0.2.0
	 *
	 * @param array{url: string, method: string, category: string} $check One entry from get_checks().
	 * @return array{blocked: bool, kind: string, message: string, cloudflare: string} `kind` is 'http' or 'network'; `cloudflare` is 'challenged', 'proxied', or ''.
	 */
	private function check_url( array $check ): array {
		$cache_key = $this->cache_key( $check['url'], $check['method'] );
		$cached    = IMGDT_Cache::get( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$is_local  = $this->is_local_url( $check['url'] );
		$sslverify = apply_filters( $is_local ? 'https_local_ssl_verify' : 'https_ssl_verify', false );

		$response = wp_remote_request(
			$check['url'],
			array(
				'method'     => $check['method'],
				'user-agent' => 'Imagify DevTools',
				'sslverify'  => $sslverify,
				'timeout'    => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			$result = array(
				'blocked'    => true,
				'kind'       => 'network',
				'message'    => $response->get_error_message(),
				'cloudflare' => '',
			);
		} else {
			$http_code = (int) wp_remote_retrieve_response_code( $response );

			$result = 200 === $http_code
				? array(
					'blocked'    => false,
					'kind'       => 'http',
					'message'    => '',
					'cloudflare' => '',
				)
				: array(
					'blocked'    => true,
					'kind'       => 'http',
					'message'    => sprintf( '%d %s', $http_code, get_status_header_desc( $http_code ) ),
					'cloudflare' => $this->detect_cloudflare( $response ),
				);
		}

		IMGDT_Cache::set( $cache_key, $result, self::CACHE_DURATION );

		return $result;
	}

	/**
	 * Detect whether Cloudflare itself produced a response, using its own
	 * documented headers rather than guessing from the status code alone.
	 * `cf-mitigated: challenge` is Cloudflare's own signal that this
	 * specific response is a challenge/block page it generated, not the
	 * origin server's — a stronger, more precise claim than "this site
	 * happens to be behind Cloudflare" (`cf-ray`, present on effectively any
	 * Cloudflare-proxied response, block or not).
	 *
	 * @since 0.2.0
	 *
	 * @param array $response A successful wp_remote_request() response (not a WP_Error).
	 * @return string 'challenged', 'proxied', or '' (no Cloudflare signal).
	 */
	private function detect_cloudflare( array $response ): string {
		$mitigated = wp_remote_retrieve_header( $response, 'cf-mitigated' );

		if ( $mitigated && false !== stripos( (string) $mitigated, 'challenge' ) ) {
			return 'challenged';
		}

		return wp_remote_retrieve_header( $response, 'cf-ray' ) ? 'proxied' : '';
	}

	/**
	 * Whether a URL points at this site's own admin/site URL.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url The URL.
	 * @return bool
	 */
	private function is_local_url( string $url ): bool {
		static $hosts = null;

		if ( null === $hosts ) {
			$hosts = array(
				wp_parse_url( admin_url(), PHP_URL_HOST ),
				wp_parse_url( site_url(), PHP_URL_HOST ),
			);
		}

		return in_array( wp_parse_url( $url, PHP_URL_HOST ), $hosts, true );
	}
}
