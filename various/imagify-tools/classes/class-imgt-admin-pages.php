<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the plugin pages in the administration area.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 */
class IMGT_Admin_Pages {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * Main page slug.
	 *
	 * @var string
	 */
	const MAIN_PAGE_SLUG = 'imgt';

	/**
	 * The view instance.
	 *
	 * @var object
	 */
	protected $view;

	/**
	 * Pages data.
	 *
	 * @var array
	 */
	protected static $pages_data;

	/**
	 * The current page name.
	 *
	 * @var string|bool
	 */
	protected static $current_page = false;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * The constructor.
	 *
	 * @since 1.0
	 * @author Grégory Viguier
	 */
	protected function __construct() {
		$prefix = self::MAIN_PAGE_SLUG;

		self::$pages_data = array(
			$prefix           => array(
				'data'       => 'Main',
				'view'       => 'Main',
				'page_title' => __( 'Imagify Tools Infos', 'imagify-tools' ),
				'menu_title' => __( 'Imagify Tools', 'imagify-tools' ),
			),
			$prefix . '-logs' => array(
				'data'       => 'Logs',
				'view'       => 'Logs',
				'page_title' => __( 'Logs', 'imagify-tools' ),
				'menu_title' => __( 'Logs', 'imagify-tools' ),
			),
		);
	}

	/**
	 * Get the main Instance.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Delete the main Instance.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public static function delete_instance() {
		unset( self::$instance );
	}

	/**
	 * Class init.
	 * Model and view are loaded later in the 'load-*' hook.
	 *
	 * @since 1.0
	 * @author Grégory Viguier
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'create_menus' ) );

		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'create_menus' ) );
		}
	}

	/**
	 * Create the menu items.
	 * It also launches the hooks that will load the page data and init the view.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function create_menus() {
		global $submenu;

		$capability   = imagify_tools_get_capacity();
		$current_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );

		foreach ( self::$pages_data as $query_arg => $args ) {
			if ( self::MAIN_PAGE_SLUG === $query_arg ) {
				$screen_id = add_menu_page( $args['page_title'], $args['menu_title'], $capability, $query_arg, array( $this->get_view(), 'render_page' ) );
			} else {
				$screen_id = add_submenu_page( self::MAIN_PAGE_SLUG, $args['page_title'], $args['menu_title'], $capability, $query_arg, array( $this->get_view(), 'render_page' ) );
			}

			if ( $query_arg === $current_page ) {
				self::$current_page = $query_arg;
				add_action( 'load-' . $screen_id, array( $this, 'load_model_and_init_view' ) );
			}
		}

		if ( ! empty( $submenu[ self::MAIN_PAGE_SLUG ] ) ) {
			$submenu[ self::MAIN_PAGE_SLUG ][0][0] = __( 'Infos', 'imagify-tools' );
		}
	}

	/**
	 * Get the view.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return object
	 */
	public function get_view() {
		if ( empty( $this->view ) ) {
			$this->view = new IMGT_Admin_View();
		}

		return $this->view->get_view();
	}

	/**
	 * Load the model handling the page data and the view that handles the page template.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function load_model_and_init_view() {
		$model = 'IMGT_Admin_Model_' . self::$pages_data[ self::$current_page ]['data'];
		$view  = 'IMGT_Admin_View_' . self::$pages_data[ self::$current_page ]['view'];

		$this->get_view()->load_data( $model )->init( $view );
	}

	/**
	 * Get the current page ID.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public static function get_current_page() {
		return self::$current_page;
	}
}
