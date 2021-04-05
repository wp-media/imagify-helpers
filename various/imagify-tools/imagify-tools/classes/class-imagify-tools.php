<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Plugin main class.
 *
 * @since  1.0
 * @author Grégory Viguier
 */
class Imagify_Tools {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1';

	/**
	 * Path to the plugin file.
	 *
	 * @var string
	 */
	protected static $plugin_file;

	/**
	 * Path to the plugin folder.
	 *
	 * @var string
	 */
	protected static $plugin_dir;

	/**
	 * URL to the plugin assets folder.
	 *
	 * @var string
	 */
	protected static $assets_url;

	/**
	 * Tell if this plugin is installed as a MU Plugin.
	 *
	 * @var bool
	 */
	protected static $is_muplugin;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * The constructor.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function __construct() {}

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
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function init() {
		if ( ! self::is_muplugin() ) {
			register_activation_hook( self::get_plugin_file(), array( $this, 'activation' ) );
		}

		add_action( 'plugins_loaded', array( $this, 'load_plugin' ), 20 );
		add_action( 'admin_init',     array( $this, 'maybe_redirect' ), 2 );
	}

	/**
	 * Load everything.
	 *
	 * @since  1.0.5
	 * @author Grégory Viguier
	 */
	public function load_plugin() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		// Nothing to do if autosave.
		if ( defined( 'DOING_AUTOSAVE' ) ) {
			return;
		}

		// Load files.
		include_once IMAGIFY_TOOLS_FUNCTIONS_PATH . 'compat.php';
		include_once IMAGIFY_TOOLS_FUNCTIONS_PATH . 'common.php';

		// Load translations.
		self::load_plugin_translations();

		// Register classes.
		spl_autoload_register( array( __CLASS__, 'autoload' ) );

		// Init classes.
		IMGT_Logs::get_instance()->init();

		if ( is_admin() ) {
			IMGT_Admin_Post::get_instance()->init();
			IMGT_Nextgen_Gallery::get_instance()->init();

			if ( ! wp_doing_ajax() ) {
				IMGT_Admin_Pages::get_instance()->init();
				IMGT_Attachments_Metas::get_instance()->init();
			}
		}

		/**
		 * Fires when Imagify Tools is correctly loaded.
		 *
		 * @since  1.0
		 * @author Grégory Viguier
		 */
		do_action( 'imagify_tools_loaded' );
	}

	/**
	 * What to do on plugin activation.
	 *
	 * @since  1.0.5
	 * @access public
	 * @author Grégory Viguier
	 */
	public function activation() {
		// Set the library mode to list.
		update_user_option( get_current_user_id(), 'media_library_mode', 'list' );

		// Redirect to the plugin's page.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		set_transient( 'imgt_activation', 1, 60 );
	}

	/**
	 * Redirect the user on plugin activation.
	 *
	 * @since  1.0.5
	 * @access public
	 * @author Grégory Viguier
	 */
	public function maybe_redirect() {
		global $pagenow;

		if ( 'plugins.php' !== $pagenow ) {
			return;
		}

		if ( ! current_user_can( imagify_tools_get_capacity() ) ) {
			return;
		}

		if ( ! get_transient( 'imgt_activation' ) ) {
			return;
		}

		delete_transient( 'imgt_activation' );

		$admin_url = 'admin.php?page=' . IMGT_Admin_Pages::MAIN_PAGE_SLUG;

		if ( is_network_admin() ) {
			$admin_url = network_admin_url( $admin_url );
		} else {
			$admin_url = admin_url( $admin_url );
		}

		wp_safe_redirect( esc_url_raw( $admin_url ) );
		die();
	}

	/**
	 * Get the path to the plugin file.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string.
	 */
	public static function get_plugin_file() {
		if ( ! isset( self::$plugin_file ) ) {
			self::set_properties();
		}
		return self::$plugin_file;
	}

	/**
	 * Get the path to the plugin folder.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string.
	 */
	public static function get_plugin_dir() {
		if ( ! isset( self::$plugin_dir ) ) {
			self::set_properties();
		}
		return self::$plugin_dir;
	}

	/**
	 * Get the plugin assets URL.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string.
	 */
	public static function get_assets_url() {
		if ( ! isset( self::$assets_url ) ) {
			self::set_properties();
		}
		return self::$assets_url;
	}

	/**
	 * Tell if this plugin is installed as a MU Plugin.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return bool.
	 */
	public static function is_muplugin() {
		if ( ! isset( self::$is_muplugin ) ) {
			self::set_properties();
		}
		return self::$is_muplugin;
	}

	/**
	 * Set the class properties.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected static function set_properties() {
		global $wp_plugin_paths;

		$wpmu_plugins_path = trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) );
		$plugin_file       = wp_normalize_path( IMAGIFY_TOOLS_FILE );
		self::$plugin_file = $plugin_file;
		self::$plugin_dir  = wp_normalize_path( IMAGIFY_TOOLS_PATH );

		if ( $wp_plugin_paths ) {
			arsort( $wp_plugin_paths );

			foreach ( $wp_plugin_paths as $dir => $realdir ) {
				if ( strpos( self::$plugin_file, $realdir ) === 0 ) {
					self::$plugin_file = $dir . substr( self::$plugin_file, strlen( $realdir ) );
				}
			}
		}

		self::$is_muplugin = strpos( self::$plugin_file, $wpmu_plugins_path ) === 0;

		if ( self::$plugin_file !== $plugin_file ) {
			self::$plugin_dir = str_replace( dirname( $plugin_file ), dirname( self::$plugin_file ), self::$plugin_dir );
		}

		self::$assets_url = plugin_dir_url( self::$plugin_dir . '/index.php' ) . 'assets/';
	}

	/**
	 * Classes autoloader.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param string $class Name of the class to include.
	 */
	public static function autoload( $class ) {
		// Since we have a very small number of classes, we'll use a white-list of class names.
		$classes = array(
			'IMGT_Admin_Model_Main'  => 1,
			'IMGT_Admin_Model_Logs'  => 1,
			'IMGT_Admin_Pages'       => 1,
			'IMGT_Admin_Post'        => 1,
			'IMGT_Admin_View'        => 1,
			'IMGT_Admin_View_Main'   => 1,
			'IMGT_Admin_View_Logs'   => 1,
			'IMGT_Attachments_Metas' => 1,
			'IMGT_Logs'              => 1,
			'IMGT_Logs_List_Table'   => 1,
			'IMGT_Log'               => 1,
			'IMGT_Nextgen_Gallery'   => 1,
			'IMGT_Tools'             => 1,
		);

		if ( isset( $classes[ $class ] ) ) {
			$class = str_replace( '_', '-', strtolower( $class ) );
			include IMAGIFY_TOOLS_CLASSES_PATH . 'class-' . $class . '.php';
		}
	}

	/**
	 * Load plugin translations.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected static function load_plugin_translations() {
		if ( self::is_muplugin() ) {
			load_muplugin_textdomain( 'imagify', wp_basename( IMAGIFY_TOOLS_PATH ) . '/languages' );
		} else {
			load_plugin_textdomain( 'imagify', false, wp_basename( IMAGIFY_TOOLS_PATH ) . '/languages' );
		}
	}
}
