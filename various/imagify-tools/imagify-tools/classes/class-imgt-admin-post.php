<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the admin post callbacks.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 */
class IMGT_Admin_Post {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * A prefix used in various places.
	 *
	 * @var (string)
	 */
	const PREFIX = 'imagify_tools';

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
		/**
		 * Logs.
		 */
		// Download Logs list.
		add_action( 'admin_post_' . self::get_action( 'download_logs' ),                            array( $this, 'download_logs_cb' ) );

		// Delete all Logs list.
		add_action( 'admin_post_' . self::get_action( 'delete_logs' ),                              array( $this, 'clear_logs_cb' ) );

		// Bulk delete Logs.
		add_action( 'admin_post_' . self::get_action( 'bulk_delete_logs' ),                         array( $this, 'bulk_delete_logs_cb' ) );

		// Delete a Log.
		add_action( 'admin_post_' . self::get_action( 'delete_log' ),                               array( $this, 'delete_log_cb' ) );

		/**
		 * Infos page.
		 */
		// Ajax test.
		add_action( 'wp_ajax_' . self::get_action( 'test' ),                                        array( $this, 'ajax_test_cb' ) );
		add_action( 'admin_post_' . self::get_action( 'test' ),                                     array( $this, 'ajax_test_cb' ) );

		// Clear request cache.
		add_action( 'admin_post_' . self::get_action( 'clear_request_cache' ),                      array( $this, 'clear_request_cache_cb' ) );

		// Clear Imagify user cache.
		add_action( 'admin_post_' . self::get_action( 'clear_imagify_user_cache' ),                 array( $this, 'clear_imagify_user_cache_cb' ) );

		// Clear invalid metas cache.
		add_action( 'admin_post_' . self::get_action( 'clear_medias_with_invalid_wp_metas_cache' ), array( $this, 'clear_medias_with_invalid_wp_metas_cache_cb' ) );

		// Clear orphan files cache.
		add_action( 'admin_post_' . self::get_action( 'clear_orphan_files_cache' ),                 array( $this, 'clear_orphan_files_cache_cb' ) );

		// Fix NGG table engine.
		add_action( 'admin_post_' . self::get_action( 'fix_ngg_table_engine' ),                     array( $this, 'fix_ngg_table_engine_cb' ) );

		/**
		 * Uninstall.
		 */
		// Uninstall this plugin (when a MU Plugin).
		add_action( 'admin_post_' . self::get_action( 'uninstall' ),                                array( $this, 'uninstall_cb' ) );
	}


	/** Callbacks for Logs ====================================================================== */

	/**
	 * Admin post callback that allows to download the Logs as a .txt file.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 * @source SecuPress
	 */
	public function download_logs_cb() {
		$this->check_nonce_and_user( self::get_action( 'download_logs' ) );

		if ( ini_get( 'zlib.output_compression' ) ) {
			ini_set( 'zlib.output_compression', 'Off' );
		}

		set_time_limit( 0 );

		if ( ! headers_sent() ) {
			$filename = 'imagify-tools-logs-' . current_time( 'Y-m-d@H-i-s' ) . '.txt';

			ob_start();
			nocache_headers();
			header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
			header( 'Content-Disposition: attachment; filename="' . utf8_encode( $filename ) . '"' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Cache-Control: private, max-age=0, must-revalidate' );
			header( 'Pragma: public' );
			header( 'Connection: close' );
			ob_end_clean();
			flush();
		}

		$logs = IMGT_Logs::get_instance()->get_logs();

		if ( $logs && is_array( $logs ) ) {
			$log_header = str_pad( '==%IMGT-LOG%', 100, '=', STR_PAD_RIGHT ) . "\n";

			foreach ( $logs as $log ) {
				$log = new IMGT_Log( $log );

				echo $log_header;
				echo '[' . $log->get_time() . "]\n";
				echo html_entity_decode( wp_strip_all_tags( str_replace( '<br/>', "\n", $log->get_message() ) ) );
				echo "\n\n";
			}
		}
		die;
	}

	/**
	 * Admin post callback that allows to delete all Logs.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 * @source SecuPress
	 */
	public function clear_logs_cb() {
		$this->check_nonce_and_user( self::get_action( 'delete_logs' ) );

		IMGT_Logs::get_instance()->delete_logs();

		$this->redirect( 'logs_cleared', __( 'Logs deleted.', 'imagify-tools' ) );
	}

	/**
	 * Admin post callback that allows to delete several Logs of a certain type.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 * @source SecuPress
	 */
	public function bulk_delete_logs_cb() {
		$this->check_nonce_and_user( 'imgt-bulk-logs' ); // Common nonce value to all bulk actions.

		$logs = filter_input(
			INPUT_GET,
			'post',
			FILTER_VALIDATE_INT,
			array(
				'flags'   => FILTER_REQUIRE_ARRAY,
				'options' => array(
					'default'   => 0,
					'min_range' => 0,
				),
			)
		);

		if ( ! $logs ) {
			$deleted = 0;
		} else {
			$deleted = IMGT_Logs::get_instance()->delete_logs( $logs );
		}

		/* translators: %s is a formatted number, don't use %d. */
		$this->redirect( 'logs_bulk_deleted', sprintf( _n( '%s log permanently deleted.', '%s logs permanently deleted.', $deleted, 'imagify-tools' ), number_format_i18n( $deleted ) ) );
	}

	/**
	 * Admin post callback that allows to delete a Log.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 * @source SecuPress
	 */
	public function delete_log_cb() {
		$this->check_nonce_and_user( self::get_action( 'delete_log' ) );

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

		if ( ! $log ) {
			wp_nonce_ays( '' );
		}

		if ( ! IMGT_Logs::get_instance()->delete_log( $log ) ) {
			wp_nonce_ays( '' );
		}

		$this->redirect( 'log_deleted', __( 'Log permanently deleted.', 'imagify-tools' ) );
	}

	/**
	 * Ajax test.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function ajax_test_cb() {
		echo 'OK';
		die();
	}

	/**
	 * Admin post callback that allows to clear a request cache (Infos page).
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function clear_request_cache_cb() {
		$this->check_nonce_and_user( self::get_action( 'clear_request_cache' ) );

		$cache = filter_input( INPUT_GET, 'cache', FILTER_SANITIZE_STRING );

		if ( ! $cache ) {
			wp_nonce_ays( '' );
		}

		imagify_tools_delete_site_transient( IMGT_Admin_Model_Main::REQUEST_CACHE_PREFIX . $cache );

		$this->redirect( 'request_cache_cleared', __( 'Request cache cleared.', 'imagify-tools' ) );
	}

	/**
	 * Admin post callback that allows to clear Imagify user cache (Infos page).
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function clear_imagify_user_cache_cb() {
		$this->check_nonce_and_user( self::get_action( 'clear_imagify_user_cache' ) );

		imagify_tools_delete_site_transient( 'imgt_user' );

		$this->redirect( 'imagify_user_cache_cleared', __( 'Imagify User cache cleared.', 'imagify-tools' ) );
	}

	/**
	 * Admin post callback that allows to clear the cache used for medias without WP metas (Infos page).
	 *
	 * @since  1.0.2
	 * @author Grégory Viguier
	 */
	public function clear_medias_with_invalid_wp_metas_cache_cb() {
		$this->check_nonce_and_user( self::get_action( 'clear_medias_with_invalid_wp_metas_cache' ) );

		imagify_tools_delete_site_transient( 'imgt_medias_invalid_wp_metas' );

		$this->redirect( 'imagify_medias_with_invalid_wp_metas_cache_cleared', __( 'Cache for medias with invalid WP metas cleared.', 'imagify-tools' ) );
	}

	/**
	 * Admin post callback that allows to clear the cache used for orphan files (Infos page).
	 *
	 * @since  1.0.2
	 * @author Grégory Viguier
	 */
	public function clear_orphan_files_cache_cb() {
		$this->check_nonce_and_user( self::get_action( 'clear_orphan_files_cache' ) );

		imagify_tools_delete_site_transient( 'imgt_orphan_files' );

		$this->redirect( 'imagify_orphan_files_cache_cleared', __( 'Cache for orphan files cleared.', 'imagify-tools' ) );
	}

	/**
	 * Admin post callback that allows to fix NGG table engine (Infos page).
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function fix_ngg_table_engine_cb() {
		global $wpdb;

		$this->check_nonce_and_user( self::get_action( 'fix_ngg_table_engine' ) );

		$wpdb->query( "ALTER TABLE {$wpdb->prefix}ngg_imagify_data ENGINE=InnoDB;" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange

		$this->redirect( 'ngg_table_engine_fixed', __( 'NGG table engine fixed.', 'imagify-tools' ) );
	}

	/**
	 * Admin post callback that allows to fix NGG table engine (Infos page).
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function uninstall_cb() {
		$this->check_nonce_and_user( self::get_action( 'uninstall' ) );

		$filesystem = imagify_tools_get_filesystem();

		if ( Imagify_Tools::is_muplugin() ) {
			define( 'WP_UNINSTALL_PLUGIN', 1 );

			if ( ! $filesystem->exists( IMAGIFY_TOOLS_PATH . 'uninstall.php' ) ) {
				/* translators: %s is a file name. */
				wp_die( sprintf( __( 'Could not find the file %s.', 'imagify-tools' ), '<code>uninstall.php</code>' ) );
			}

			// Uninstall.
			include IMAGIFY_TOOLS_PATH . 'uninstall.php';

			// Delete.
			$success = $filesystem->delete( IMAGIFY_TOOLS_FILE );
		} else {
			deactivate_plugins( array( plugin_basename( IMAGIFY_TOOLS_FILE ) ) );
			$success = (bool) uninstall_plugin( IMAGIFY_TOOLS_FILE );
		}

		// Delete.
		$filesystem->delete( IMAGIFY_TOOLS_PATH, true );

		// Don't redirect to the plugin page, it doesn't exist anymore.
		$referer = wp_get_referer();

		if ( is_multisite() && strpos( $referer, network_admin_url( '/' ) ) === 0 ) {
			$referer = network_admin_url( 'plugins.php' );
		} else {
			$referer = admin_url( 'plugins.php' );
		}

		if ( ! $success || $filesystem->exists( IMAGIFY_TOOLS_FILE ) || $filesystem->exists( IMAGIFY_TOOLS_PATH ) ) {
			$plugins_to_delete = 0;
			$delete_result     = new WP_Error(
				'could_not_remove_plugin',
				/* translators: %s is a plugin file name, and this sentense already exists in WordPress. */
				sprintf( __( 'Could not fully remove the plugin(s) %s.', 'imagify-tools' ), basename( IMAGIFY_TOOLS_FILE ) )
			);
		} else {
			$plugins_to_delete = 1;
			$delete_result     = true;
		}

		set_transient( 'plugins_delete_result_' . get_current_user_id(), $delete_result );

		$referer = add_query_arg( 'deleted', $plugins_to_delete, $referer );

		wp_safe_redirect( esc_url_raw( $referer ) );
		exit();
	}


	/** Public tools ============================================================================ */

	/**
	 * A helper to format and get the action to use with nonces and admin post callbacks.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $action The action.
	 * @return string
	 */
	public static function get_action( $action ) {
		return self::PREFIX . '-' . str_replace( '-', '_', $action );
	}


	/** Internal tools ========================================================================== */

	/**
	 * Check nonce and user capability.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param string $nonce The nonce name.
	 */
	protected function check_nonce_and_user( $nonce ) {
		check_admin_referer( $nonce );
		$this->check_user_capability();
	}

	/**
	 * Check that the user can perform the action. Die otherwise.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function check_user_capability() {
		if ( ! current_user_can( imagify_tools_get_capacity() ) ) {
			wp_nonce_ays( '' );
		}
	}

	/**
	 * Redirect and display a "updated" admin notice.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param string $message_id A message ID to use in add_settings_error().
	 * @param string $message    A message to use in add_settings_error().
	 */
	protected function redirect( $message_id, $message ) {
		add_settings_error( 'general', $message_id, $message, 'updated' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
		wp_safe_redirect( esc_url_raw( $goback ) );
		exit();
	}
}
