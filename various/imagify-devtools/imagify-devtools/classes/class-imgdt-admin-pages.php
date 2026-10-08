<?php
/**
 * Admin menu and page controller.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Registers the top-level admin page and renders it, tab by tab.
 *
 * @since 0.1.0
 */
class IMGDT_Admin_Pages {

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	public const SLUG = 'imagify-devtools';

	/**
	 * Default tab.
	 *
	 * @var string
	 */
	private const DEFAULT_TAB = 'triage';

	/**
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * @var IMGDT_Diagnostics_Registry
	 */
	private IMGDT_Diagnostics_Registry $registry;

	/**
	 * Tabs, built lazily by get_tabs() since it calls __(), which must not
	 * run before the `init` hook.
	 *
	 * @var array<string, array{label: string, view: string}>|null
	 */
	private ?array $tabs = null;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->registry = new IMGDT_Diagnostics_Registry();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.1.0
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
	 * Init: register hooks.
	 *
	 * @since 0.1.0
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Register the admin menu entry.
	 *
	 * @since 0.1.0
	 */
	public function register_menu(): void {
		$hook = add_menu_page(
			__( 'Imagify DevTools', 'imagify-devtools' ),
			__( 'Imagify DevTools', 'imagify-devtools' ),
			apply_filters( 'imgdt_capability', IMGDT_CAPABILITY ),
			self::SLUG,
			array( $this, 'render_page' ),
			'dashicons-search',
			80
		);

		add_action( 'load-' . $hook, array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style( 'imgdt-admin', IMGDT_URL . 'assets/css/admin.css', array(), imgdt_asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script( 'imgdt-admin', IMGDT_URL . 'assets/js/admin.js', array(), imgdt_asset_version( 'assets/js/admin.js' ), true );
		wp_localize_script(
			'imgdt-admin',
			'imgdtAdmin',
			array(
				'expandAll'   => __( 'Expand all', 'imagify-devtools' ),
				'collapseAll' => __( 'Collapse all', 'imagify-devtools' ),
			)
		);
	}

	/**
	 * Get the currently active tab slug.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	private function current_tab(): string {
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : self::DEFAULT_TAB;
		$tabs = $this->get_tabs();

		return isset( $tabs[ $tab ] ) ? $tab : self::DEFAULT_TAB;
	}

	/**
	 * Render the page shell: title, tab nav, and the active tab's view.
	 *
	 * @since 0.1.0
	 */
	public function render_page(): void {
		if ( ! imgdt_current_user_can() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'imagify-devtools' ), 403 );
		}

		$current     = $this->current_tab();
		$registry    = $this->registry;
		$error_count = (int) imgdt_safe( static fn() => $registry->get_error_count(), 0 );

		require IMGDT_VIEWS_PATH . 'page-shell.php';
	}

	/**
	 * Get the registered tabs, building them on first call.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array{label: string, view: string}>
	 */
	public function get_tabs(): array {
		if ( null !== $this->tabs ) {
			return $this->tabs;
		}

		$tabs = array(
			'triage'      => array(
				'label' => __( 'Triage', 'imagify-devtools' ),
				'view'  => 'tab-triage.php',
			),
			'diagnostics' => array(
				'label' => __( 'Full diagnostics', 'imagify-devtools' ),
				'view'  => 'tab-sections.php',
			),
		);

		/**
		 * Filters the DevTools admin tabs.
		 *
		 * @since 0.1.0
		 *
		 * @param array $tabs Tab slug => ['label' => ..., 'view' => ...].
		 */
		$this->tabs = apply_filters( 'imgdt_admin_tabs', $tabs );

		return $this->tabs;
	}
}
