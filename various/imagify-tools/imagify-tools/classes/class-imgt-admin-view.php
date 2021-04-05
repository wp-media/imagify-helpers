<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handle the views.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 */
class IMGT_Admin_View {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * Template file.
	 *
	 * @var string
	 */
	protected $template = '';

	/**
	 * View instance.
	 *
	 * @var object
	 */
	protected $view;

	/**
	 * Data model instance.
	 *
	 * @var object
	 */
	protected $model;

	/**
	 * The constructor.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function __construct() {}

	/**
	 * Init the view.
	 *
	 * @since 1.0
	 * @author Grégory Viguier
	 *
	 * @return object This class instance.
	 */
	public function init() {
		/**
		 * Only used to initialize "sub" views.
		 *
		 * @param string $view Name of the view class.
		 */
		$view = func_get_args();
		$view = $view ? reset( $view ) : false;

		if ( ! $view ) {
			return $this;
		}

		$this->view = new $view();

		if ( $this->model ) {
			$this->view->model = $this->model;
		}

		$this->view->init();

		return $this;
	}

	/**
	 * Get the view.
	 *
	 * @since 1.0
	 * @author Grégory Viguier
	 *
	 * @return object The view instance.
	 */
	public function get_view() {
		if ( ! empty( $this->view ) ) {
			return $this->view;
		}

		return $this;
	}

	/**
	 * Load the data model.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $model Name of the model class.
	 * @return object        This class instance.
	 */
	final public function load_data( $model ) {
		if ( $this->view ) {
			return $this->view->load_data( $model );
		}

		$this->model = new $model();

		return $this;
	}

	/**
	 * Print the whole page content.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return object This class instance.
	 */
	final public function render_page() {
		if ( $this->view ) {
			return $this->view->render_page();
		}

		include IMAGIFY_TOOLS_VIEWS_PATH . 'layout.php';

		return $this;
	}

	/**
	 * Print the page title.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return object This class instance.
	 */
	public function render_title() {
		global $title;

		if ( $this->view ) {
			return $this->view->render_title();
		}

		// Display an uninstall button.
		echo $this->get_uninstall_button();

		printf( '<%1$s class="imgt-page-title">%2$s</%1$s>', self::get_heading_tag(), $title );

		// Messages.
		settings_errors();

		return $this;
	}

	/**
	 * Print the view.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $file_name A file name.
	 * @return object            This class instance.
	 */
	final public function render_content( $file_name = false ) {
		if ( $this->view ) {
			return $this->view->render_content( $file_name );
		}

		if ( ! $file_name && $this->template ) {
			$file_name = $this->template;
		}

		if ( ! $file_name || ! file_exists( IMAGIFY_TOOLS_VIEWS_PATH . $file_name ) ) {
			return $this;
		}

		include IMAGIFY_TOOLS_VIEWS_PATH . $file_name;

		return $this;
	}

	/**
	 * Print the tests area.
	 * Do some var_dump() and stuff in the tests.php file and they will be displayed at the bottom of the page.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return object This class instance.
	 */
	final public function render_tests_area() {
		if ( $this->view ) {
			return $this->view->render_tests_area();
		}

		if ( ! file_exists( IMAGIFY_TOOLS_PATH . 'tests.php' ) ) {
			return $this;
		}

		// Force errors to be displayed.
		error_reporting( E_ALL );
		ini_set( 'display_errors', 1 );
		ini_set( 'log_errors', 1 );
		ini_set( 'error_log', WP_CONTENT_DIR . '/debug.log' );

		// Include the file.
		ob_start();
		include IMAGIFY_TOOLS_PATH . 'tests.php';
		$tests = ob_get_clean();

		if ( ! $tests ) {
			return $this;
		}

		// Display the result.
		echo '<div id="imgt-tests-area" class="clear">';

		printf( '<%1$s>%2$s</%1$s>', self::get_heading_tag(), __( 'Tests area', 'imagify-tools' ) );

		echo preg_match( '@<pre[\s>]@', $tests ) ? $tests : '<pre>' . esc_html( $tests ) . '</pre>';

		echo '</div>';

		return $this;
	}

	/**
	 * Print the language attributes if needed.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return object This class instance.
	 */
	final public function render_language_attributes() {
		global $l10n;

		if ( $this->view ) {
			return $this->view->render_language_attributes();
		}

		if ( isset( $l10n['imagify-tools'] ) ) {
			return $this;
		}

		if ( 'en_US' === self::get_locale() ) {
			return $this;
		}

		echo ' lang="en-US"' . ( is_rtl() ? ' dir="ltr"' : '' );

		return $this;
	}

	/**
	 * Get the HTML markup for the plugin uninstall button.
	 *
	 * @since  1.0.1
	 * @author Grégory Viguier
	 *
	 * @param  string $template HTML to use for the link.
	 * @return string
	 */
	final public function get_uninstall_button( $template = false ) {
		$uninstall_url = IMGT_Admin_Post::get_action( 'uninstall' );
		$uninstall_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . $uninstall_url ), $uninstall_url );

		if ( ! $template ) {
			$template = '<a class="imgt-button imgt-button-secondary imgt-button-uninstall alignright" href="%s">%s</a>';
		}

		return sprintf( $template, esc_url( $uninstall_url ), __( 'Uninstall', 'imagify-tools' ) );
	}

	/**
	 * Get the html tag used for heading, depending on the WP version.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	final public static function get_heading_tag() {
		global $wp_version;
		static $heading_tag;

		if ( ! isset( $heading_tag ) ) {
			$heading_tag = version_compare( $wp_version, '4.3-alpha' ) >= 0 ? 'h1' : 'h2';
		}

		return $heading_tag;
	}

	/**
	 * Get the locale used in the administration area.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	final public static function get_locale() {
		static $locale;

		if ( ! isset( $locale ) ) {
			$locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		}

		return $locale;
	}
}
