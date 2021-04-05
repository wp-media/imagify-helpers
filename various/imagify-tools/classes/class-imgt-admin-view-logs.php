<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handle the view for the logs page.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 * @source  Adapted from SecuPress
 */
class IMGT_Admin_View_Logs extends IMGT_Admin_View {

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
	protected $template = 'logs-page.php';

	/**
	 * Id of the log currently being displayed.
	 *
	 * @var int
	 */
	protected $current_log_id;

	/**
	 * Log currently being displayed.
	 *
	 * @var object
	 */
	protected $current_log;

	/**
	 * Init.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function init() {
		global $wp_list_table; // The model MUST be instanciated BEFORE this class.

		/**
		 * Display a Log content.
		 * If the Log doesn't exist, remove the "log" parameter and redirect.
		 */
		$log = filter_input(
			INPUT_GET,
			'log',
			FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'default'   => 0,
					'min_range' => 0,
				),
			)
		);

		if ( $log ) {
			$this->current_log_id = IMGT_Logs::get_instance()->log_exists( $log );

			if ( ! $this->current_log_id ) {
				$sendback = $wp_list_table->paged_page_url();
				wp_safe_redirect( esc_url_raw( $sendback ) );
				exit();
			}

			$this->current_log = new IMGT_Log( $this->current_log_id );
		}

		/**
		 * Screen options and stuff.
		 */
		$current_screen = get_current_screen();

		if ( method_exists( $current_screen, 'set_screen_reader_content' ) ) {

			$post_type_object = get_post_type_object( $wp_list_table->screen->post_type );

			$current_screen->set_screen_reader_content(
				array(
					'heading_views'      => $post_type_object->labels->filter_items_list,
					'heading_pagination' => $post_type_object->labels->items_list_navigation,
					'heading_list'       => $post_type_object->labels->items_list,
				)
			);
		}

		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'edit_' . $wp_list_table->screen->post_type . '_per_page',
			)
		);

		/**
		 * Styles.
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue some CSS.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function enqueue_styles() {
		$url = Imagify_Tools::get_assets_url();
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$ver = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : IMAGIFY_TOOLS_VERSION;

		wp_enqueue_style( 'imgt-admin', $url . 'css/admin' . $min . '.css', array(), $ver );
		wp_enqueue_style( 'imgt-logs', $url . 'css/logs' . $min . '.css', array( 'imgt-admin' ), $ver );
	}

	/**
	 * Print the page title.
	 *
	 * @since  1.0.1
	 * @author Grégory Viguier
	 *
	 * @return object This class instance.
	 */
	public function render_title() {
		global $title;

		// Current time.
		$page_title = $title . '<span class="imgt-current-time">' . mysql2date( __( '\<\b\>Y/m/d\<\/\b\> g:i:s a', 'imagify-tools' ), current_time( 'mysql' ), true ) . '</span>';

		// Display an uninstall button.
		echo $this->get_uninstall_button();

		printf( '<%1$s class="imgt-page-title">%2$s</%1$s>', self::get_heading_tag(), $page_title );

		// Messages.
		settings_errors();

		return $this;
	}

	/**
	 * Maybe display the current Log infos.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return True if a Log is displayed. False otherwize.
	 */
	protected function display_current_log() {
		global $wp_list_table;

		if ( ! $this->current_log_id || ! $this->current_log ) {
			return;
		}

		// Add a class to the current Log row.
		add_filter( 'post_class', array( $this, 'add_current_log_class' ), 10, 3 );

		$this->render_content( 'log-details.php' );
	}

	/**
	 * Add a "current-log" class to the row of the Log currently being displayed.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  array $classes An array of post classes.
	 * @param  array $class   An array of additional classes added to the post.
	 * @param  int   $post_id The post ID.
	 * @return array
	 */
	public function add_current_log_class( $classes, $class, $post_id ) {
		if ( $post_id === $this->current_log_id ) {
			$classes[] = 'current-log';
		}
		return $classes;
	}


	/** Tools =================================================================================== */

	/**
	 * Get the URL to download Logs.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $referer The page referer.
	 * @return string
	 */
	public function download_logs_url( $referer ) {
		return $this->action_url( 'download_logs', $referer );
	}

	/**
	 * Get the URL to delete all Logs.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $referer The page referer.
	 * @return string
	 */
	public function delete_logs_url( $referer ) {
		return $this->action_url( 'delete_logs', $referer );
	}

	/**
	 * Get the URL to delete one Log.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  int    $log_id  The Log ID.
	 * @param  string $referer The page referer.
	 * @return string
	 */
	public function delete_log_url( $log_id, $referer ) {
		return $this->action_url( 'delete_log', $referer, array( 'log' => $log_id ) );
	}

	/**
	 * Get the URL to delete one Log.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $action  The action to use in the nonce and the URL.
	 * @param  string $referer The page referer.
	 * @param  array  $args    Some arguments to add to the URL.
	 * @return string
	 */
	protected function action_url( $action, $referer, $args = array() ) {
		$action = IMGT_Admin_Post::get_action( $action );
		$href   = rawurlencode( esc_url_raw( $referer ) );
		$href   = admin_url( 'admin-post.php?action=' . $action . '&_wp_http_referer=' . $href );

		if ( $args ) {
			$href = add_query_arg( $args, $href );
		}

		return wp_nonce_url( $href, $action );
	}
}
