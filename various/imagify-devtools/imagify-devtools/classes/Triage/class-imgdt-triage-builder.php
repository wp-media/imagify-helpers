<?php
/**
 * Builds the triage checklist.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Three fixed cards — requests, memory, image editor — plus a count of
 * everything else currently flagged in the full diagnostics. Order and
 * membership are fixed on purpose: this mirrors a specific triage
 * sequence, not a generic "most important checks" ranking.
 *
 * @since 0.2.0
 */
class IMGDT_Triage_Builder {

	/**
	 * @var IMGDT_Diagnostics_Registry
	 */
	private IMGDT_Diagnostics_Registry $registry;

	/**
	 * @param IMGDT_Diagnostics_Registry $registry Full diagnostics registry.
	 */
	public function __construct( IMGDT_Diagnostics_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Build the three triage cards.
	 *
	 * @since 0.2.0
	 *
	 * @return IMGDT_Triage_Card[]
	 */
	public function get_cards(): array {
		return array(
			$this->requests_card(),
			$this->memory_card(),
			$this->image_editor_card(),
		);
	}

	/**
	 * Count of flagged checks not represented by the three cards above.
	 *
	 * @since 0.2.0
	 *
	 * @return int
	 */
	public function get_other_count(): int {
		$requests = $this->registry->get_sections()['requests'] ?? null;
		$other    = $this->registry->get_error_count();

		if ( $requests instanceof IMGDT_Diagnostic_Section ) {
			$other -= $requests->error_count();
		}

		if ( '' === ( new IMGDT_Provider_Image_Editor() )->get_selected() ) {
			--$other;
		}

		return max( 0, $other );
	}

	/**
	 * Build the requests card. Blocked checks are grouped by category —
	 * "external" (genuine outbound requests to Imagify, fixed by whitelisting
	 * Imagify's domains) vs. "loopback" (the site calling its own
	 * admin-ajax.php/admin-post.php/wp-cron.php, fixed on the
	 * hosting/firewall/security-plugin/CDN side — whitelisting Imagify does
	 * nothing for this one) — so which fix applies is visible without
	 * reading each blocked URL. Each blocked line also names Cloudflare
	 * directly when its own response headers confirm it (`cf-mitigated`/
	 * `cf-ray`), instead of leaving that to be inferred.
	 *
	 * @since 0.2.0
	 *
	 * @return IMGDT_Triage_Card
	 */
	private function requests_card(): IMGDT_Triage_Card {
		$provider = new IMGDT_Provider_Requests();
		$blocked  = $provider->get_blocked_checks();

		if ( ! $blocked ) {
			return new IMGDT_Triage_Card( __( 'Requests', 'imagify-devtools' ), IMGDT_Triage_Card::STATUS_OK, __( 'None blocked', 'imagify-devtools' ) );
		}

		$groups = array(
			'external' => __( 'External (Imagify API)', 'imagify-devtools' ),
			'loopback' => __( 'Loopback', 'imagify-devtools' ),
		);

		$blocked_by_constant = $provider->is_blocked_by_constant();
		$lines               = array();

		foreach ( $groups as $category => $group_label ) {
			$items = array_values( array_filter( $blocked, static fn( array $item ) => $category === $item['category'] ) );

			if ( ! $items ) {
				continue;
			}

			$header = sprintf(
				/* translators: 1 is a group name, 2 is a count. */
				_n( '%1$s: %2$d blocked', '%1$s: %2$d blocked', count( $items ), 'imagify-devtools' ),
				$group_label,
				count( $items )
			);

			if ( 'external' === $category && $blocked_by_constant ) {
				// Plain text: this detail is escaped as a whole on output, same as every
				// other Triage card detail — no inline HTML here (unlike Full Diagnostics
				// rows, which render `more_info` unescaped).
				$header .= ' — ' . sprintf(
					/* translators: %s is a constant name. */
					__( 'confirmed cause: %s', 'imagify-devtools' ),
					'WP_HTTP_BLOCK_EXTERNAL'
				);
			}

			$lines[] = $header;

			foreach ( $items as $item ) {
				$line = '  ' . $item['label'] . ' — ' . $item['detail'];

				if ( 'challenged' === $item['cloudflare'] ) {
					$line .= ' · ' . __( 'Cloudflare: challenge', 'imagify-devtools' );
				} elseif ( 'proxied' === $item['cloudflare'] ) {
					$line .= ' · ' . __( 'Cloudflare: proxied', 'imagify-devtools' );
				}

				$lines[] = $line;
			}
		}

		return new IMGDT_Triage_Card(
			__( 'Requests', 'imagify-devtools' ),
			IMGDT_Triage_Card::STATUS_ERROR,
			sprintf(
				/* translators: %d is a number of blocked requests. */
				_n( 'Blocked (%d)', 'Blocked (%d)', count( $blocked ), 'imagify-devtools' ),
				count( $blocked )
			),
			implode( "\n", $lines )
		);
	}

	/**
	 * Build the memory/timeout card. Always informational — never flagged,
	 * since a low value here isn't a reliable enough signal on its own.
	 *
	 * @since 0.2.0
	 *
	 * @return IMGDT_Triage_Card
	 */
	private function memory_card(): IMGDT_Triage_Card {
		$various = new IMGDT_Provider_Various();
		$limit   = $various->get_memory_limit() ?: __( 'Unknown', 'imagify-devtools' );
		$timeout = (int) ini_get( 'max_execution_time' );

		return new IMGDT_Triage_Card(
			__( 'Memory & timeout', 'imagify-devtools' ),
			IMGDT_Triage_Card::STATUS_OK,
			$limit,
			sprintf(
				/* translators: 1 is the server's PHP memory_limit, 2 is WP_MEMORY_LIMIT, 3 is WP_MAX_MEMORY_LIMIT, 4 is max_execution_time in seconds. */
				__( 'Server: %1$s · WP: %2$s · WP max: %3$s · Timeout: %4$ss', 'imagify-devtools' ),
				$limit,
				defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '?',
				defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : '?',
				$timeout > 0 ? (string) $timeout : __( 'unlimited', 'imagify-devtools' )
			)
		);
	}

	/**
	 * Build the image editor card.
	 *
	 * @since 0.2.0
	 *
	 * @return IMGDT_Triage_Card
	 */
	private function image_editor_card(): IMGDT_Triage_Card {
		$provider = new IMGDT_Provider_Image_Editor();
		$selected = $provider->get_selected();
		$action   = $this->gd_override_action( $provider );

		if ( $selected ) {
			return new IMGDT_Triage_Card( __( 'Image editor', 'imagify-devtools' ), IMGDT_Triage_Card::STATUS_OK, $selected, '', $action );
		}

		return new IMGDT_Triage_Card(
			__( 'Image editor', 'imagify-devtools' ),
			IMGDT_Triage_Card::STATUS_ERROR,
			__( 'None available', 'imagify-devtools' ),
			__( 'Neither Imagick nor GD passed the required capability tests.', 'imagify-devtools' ),
			$action
		);
	}

	/**
	 * Build the "prefer GD" switch for the Image editor card, if it applies.
	 *
	 * Shown only when either the override is already on (so it can be
	 * turned back off), or the site is currently, naturally selecting
	 * core's own Imagick class and GD is available to switch to. Never
	 * shown for a third-party plugin's own editor class (e.g. FooGallery's,
	 * Jetpack's) — this override only ever touches the literal core
	 * `WP_Image_Editor_Imagick` class, so offering it anywhere else would
	 * promise something it can't do.
	 *
	 * @since 0.2.0
	 *
	 * @param IMGDT_Provider_Image_Editor $provider Image editor provider.
	 * @return string Safe HTML, empty string if the switch doesn't apply.
	 */
	private function gd_override_action( IMGDT_Provider_Image_Editor $provider ): string {
		$enabled = IMGDT_Image_Editor_Override::is_enabled();
		$natural = 'WP_Image_Editor_Imagick' === $provider->get_selected_raw_class();

		if ( ! $enabled && ( ! $natural || ! $provider->gd_available() ) ) {
			return '';
		}

		$url = IMGDT_Admin_Post::toggle_gd_url();

		return sprintf(
			'<label class="imgdt-triage-card-toggle"><input type="checkbox" onchange="location.href=this.value" value="%1$s"%2$s /> %3$s</label>',
			esc_url( $url ),
			$enabled ? ' checked' : '',
			$enabled
				? esc_html__( 'GD enabled.', 'imagify-devtools' )
				: esc_html__( 'Enable GD', 'imagify-devtools' )
		);
	}
}
