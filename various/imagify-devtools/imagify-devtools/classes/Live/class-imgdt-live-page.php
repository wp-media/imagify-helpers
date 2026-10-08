<?php
/**
 * Hidden admin page: live optimization trace.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Registers a page with no menu entry. Shows recently active media
 * optimizations and, for a selected one, its event timeline.
 *
 * @since 0.2.0
 */
class IMGDT_Live_Page {

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	public const SLUG = 'imagify-devtools-live';

	/**
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * @var IMGDT_Live_Store
	 */
	private IMGDT_Live_Store $store;

	/**
	 * Constructor (private, singleton).
	 */
	private function __construct() {
		$this->store = new IMGDT_Live_Store();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.2.0
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.2.0
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'wp_ajax_imgdt_live_poll', array( $this, 'handle_poll' ) );
	}

	/**
	 * Register the page without a parent menu, so it has no visible menu entry.
	 *
	 * @since 0.2.0
	 */
	public function register_page(): void {
		$hook = add_submenu_page(
			null,
			'Imagify DevTools — Live',
			'Imagify DevTools — Live',
			apply_filters( 'imgdt_capability', IMGDT_CAPABILITY ),
			self::SLUG,
			array( $this, 'render' )
		);

		add_action( 'load-' . $hook, array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin assets for this page.
	 *
	 * @since 0.2.0
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style( 'imgdt-admin', IMGDT_URL . 'assets/css/admin.css', array(), imgdt_asset_version( 'assets/css/admin.css' ) );
	}

	/**
	 * Render the page.
	 *
	 * @since 0.2.0
	 */
	public function render(): void {
		if ( ! imgdt_current_user_can() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'imagify-devtools' ), 403 );
		}

		$media_id      = isset( $_GET['media'] ) ? absint( $_GET['media'] ) : 0;
		$recent        = $this->store->get_recent_ids();
		$api_key_valid = $this->get_api_key_status();

		require IMGDT_VIEWS_PATH . 'live-page.php';
	}

	/**
	 * Handle the `wp_ajax_imgdt_live_poll` endpoint used by the page's
	 * polling script. Runs through admin-ajax.php, which sends no
	 * surrounding page chrome, unlike a submenu page callback.
	 *
	 * @since 0.2.0
	 */
	public function handle_poll(): void {
		if ( ! imgdt_current_user_can() ) {
			wp_die( '', '', array( 'response' => 403 ) );
		}

		$media_id = isset( $_GET['media'] ) ? absint( $_GET['media'] ) : 0;

		wp_send_json( array( 'events' => $this->format_events( $media_id ) ) );
	}

	/**
	 * Format a media's events for display, oldest first.
	 *
	 * @since 0.2.0
	 *
	 * @param int $media_id Media (attachment) ID.
	 * @return array
	 */
	public function format_events( int $media_id ): array {
		$events = $this->store->get_events( $media_id );

		return array_map(
			static function ( array $event ): array {
				return array(
					'time'   => wp_date( 'H:i:s', (int) $event['time'] ),
					'step'   => $event['step'],
					'detail' => $event['detail'],
				);
			},
			$events
		);
	}

	/**
	 * Get the background optimization queue's current status.
	 *
	 * @since 0.2.0
	 *
	 * @return array{available: bool, active: bool, paused: bool}
	 */
	public function get_queue_status(): array {
		return imgdt_safe(
			static function (): array {
				$class = '\Imagify\Job\MediaOptimization';

				if ( ! class_exists( $class ) || ! method_exists( $class, 'get_instance' ) ) {
					return array(
						'available' => false,
						'active'    => false,
						'paused'    => false,
					);
				}

				$process = $class::get_instance();

				return array(
					'available' => true,
					'active'    => method_exists( $process, 'is_active' ) ? $process->is_active() : false,
					'paused'    => method_exists( $process, 'is_paused' ) ? $process->is_paused() : false,
				);
			},
			array(
				'available' => false,
				'active'    => false,
				'paused'    => false,
			)
		);
	}

	/**
	 * Check whether the Imagify API key is currently valid.
	 *
	 * @since 0.2.0
	 *
	 * @return bool|null Null if this can't be determined (Imagify not active).
	 */
	public function get_api_key_status(): ?bool {
		return imgdt_safe(
			static function (): ?bool {
				if ( ! class_exists( '\Imagify_Requirements' ) || ! method_exists( '\Imagify_Requirements', 'is_api_key_valid' ) ) {
					return null;
				}

				return (bool) \Imagify_Requirements::is_api_key_valid();
			}
		);
	}

	/**
	 * Build the URL for this page.
	 *
	 * @since 0.2.0
	 *
	 * @param array $args Extra query args.
	 * @return string
	 */
	public static function url( array $args = array() ): string {
		$args = array_merge( array( 'page' => self::SLUG ), $args );

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Build the admin-ajax.php polling URL for a media.
	 *
	 * @since 0.2.0
	 *
	 * @param int $media_id Media (attachment) ID.
	 * @return string
	 */
	public static function poll_url( int $media_id ): string {
		return add_query_arg(
			array(
				'action' => 'imgdt_live_poll',
				'media'  => $media_id,
			),
			admin_url( 'admin-ajax.php' )
		);
	}
}
