<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that logs some hooks.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 * @source  Adapted from SecuPress
 */
class IMGT_Logs {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * A prefix used in various places.
	 *
	 * @var (string)
	 */
	const PREFIX = 'imagify_tools';

	/**
	 * The Post Type.
	 *
	 * @var (string)
	 */
	const POST_TYPE = 'imgt_log';

	/**
	 * The name of the transient that will store the delayed Logs.
	 *
	 * @var (string)
	 */
	const DELAYED_LOGS_TRANSIENT_NAME = 'imgt_delayed_logs';

	/**
	 * Options to Log.
	 *
	 * @see `maybe_log_option()` for an explanation about the values.
	 *
	 * @var (array)
	 */
	protected $options = array(
		'imagify_settings' => null,
	);

	/**
	 * Network options to Log.
	 *
	 * @var (array)
	 */
	protected $network_options = array(
		'imagify_settings' => null,
	);

	/**
	 * Filters to Log.
	 *
	 * @var (array)
	 */
	protected $filters = array();

	/**
	 * Actions to Log.
	 *
	 * @var (array)
	 */
	protected $actions = array(
		'http_api_debug'             => 5, // `WP_Http`
		'imagify_curl_http_response' => 5, // `Imagify::curl_http_call()`
	);

	/**
	 * An array of Log arrays: all things in this page that should be logged will end here, before being saved at the end of the page.
	 *
	 * @var (array)
	 */
	protected $logs_queue = array();

	/**
	 * True once the custom post type is registered.
	 *
	 * @var bool
	 */
	protected $init_done = false;

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


	/** Init ==================================================================================== */

	/**
	 * Class init.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function init() {
		/**
		 * Launch main hooks.
		 */
		$init_done = did_action( 'init' ) || doing_action( 'init' );

		// Register the Post Type.
		if ( $init_done ) {
			$this->register_post_type();
		} else {
			add_action( 'init', array( $this, 'register_post_type' ), 1 );

			// Some Logs creation may have been delayed.
			add_action( 'init', array( $this, 'save_delayed_logs' ) );
		}

		// Filter the post slug to allow duplicates.
		add_filter( 'wp_unique_post_slug', array( $this, 'allow_log_name_duplicates' ), 10, 6 );

		/**
		 * Log everything.
		 */
		// Log options.
		if ( $this->options ) {
			add_action( 'added_option',   array( $this, 'maybe_log_added_option' ),   PHP_INT_MAX, 2 );
			add_action( 'updated_option', array( $this, 'maybe_log_updated_option' ), PHP_INT_MAX, 3 );
		}

		// Log network options.
		if ( $this->network_options && is_multisite() ) {
			add_action( 'add_site_option',    array( $this, 'maybe_log_added_network_option' ),   PHP_INT_MAX, 2 );
			add_action( 'update_site_option', array( $this, 'maybe_log_updated_network_option' ), PHP_INT_MAX, 3 );
		}

		// Log filters.
		if ( $this->filters ) {
			foreach ( $this->filters as $tag => $accepted_args ) {
				add_action( $tag, array( $this, 'log_filter' ), PHP_INT_MAX, $accepted_args );
			}
		}

		// Log actions.
		if ( $this->actions ) {
			foreach ( $this->actions as $tag => $accepted_args ) {
				add_action( $tag, array( $this, 'log_action' ), PHP_INT_MAX, $accepted_args );
			}
		}
	}


	/** Log a hook ============================================================================== */

	/**
	 * If the added option is in our list, log it.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $option The option name.
	 * @param (mixed)  $value  The option new value.
	 */
	public function maybe_log_added_option( $option, $value ) {
		$this->maybe_log_option( $option, array( 'new' => $value ) );
	}

	/**
	 * If the updated option is in our list, log it.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $option    The option name.
	 * @param (mixed)  $old_value The option old value.
	 * @param (mixed)  $value     The option new value.
	 */
	public function maybe_log_updated_option( $option, $old_value, $value ) {
		$this->maybe_log_option(
			$option,
			array(
				'new' => $value,
				'old' => $old_value,
			)
		);
	}

	/**
	 * If the added network option is in our list, log it.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $option The option name.
	 * @param (mixed)  $value  The option new value.
	 */
	public function maybe_log_added_network_option( $option, $value ) {
		$this->maybe_log_option( $option, array( 'new' => $value ), true );
	}

	/**
	 * If the updated network option is in our list, log it.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $option    The option name.
	 * @param (mixed)  $value     The option new value.
	 * @param (mixed)  $old_value The option old value.
	 */
	public function maybe_log_updated_network_option( $option, $value, $old_value ) {
		$this->maybe_log_option(
			$option,
			array(
				'new' => $value,
				'old' => $old_value,
			),
			true
		);
	}

	/**
	 * If the option is in our list, log it.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $option  The option name.
	 * @param (array)  $values  The option values (the new one and maybe the old one).
	 * @param (bool)   $network If true, it's a network option.
	 */
	protected function maybe_log_option( $option, $values, $network = false ) {
		if ( $network ) {
			$options = $this->network_options;
			$type    = 'network_option';
		} else {
			$options = $this->options;
			$type    = 'option';
		}

		if ( ! array_key_exists( $option, $options ) ) {
			return;
		}

		$compare = $options[ $option ];
		$subtype = current_filter();
		$subtype = substr( $subtype, 0, 6 ) === 'update' ? 'update' : 'add';
		$type   .= '|' . $subtype;
		$values  = array_merge( array( 'option' => $option ), $values );

		// Null => any change will be logged.
		if ( null === $compare ) {
			$this->log( $type, $option, $values );
		}
		// '1' => only this numeric value will be logged.
		elseif ( is_int( $compare ) || is_numeric( $compare ) ) {
			if ( (int) $compare === (int) $values['new'] ) {
				$this->log( $type, $option, $values );
			}
		}
		// '!xxx' => any value that is not this one will be logged.
		elseif ( is_string( $compare ) && substr( $compare, 0, 1 ) === '!' ) {
			$compare = substr( $compare, 1 );

			// '!1'
			if ( is_numeric( $compare ) ) {
				if ( (int) $compare !== (int) $values['new'] ) {
					$this->log( $type, $option, $values );
				}
			}
			// '!subscriber'
			elseif ( $compare !== $values['new'] ) {
				$this->log( $type, $option, $values );
			}
		}
		// 'xxx' => only this value will be logged.
		elseif ( $compare === $values['new'] ) {
			$this->log( $type, $option, $values );
		}
	}

	/**
	 * Log a filter.
	 * Params: (mixed) Any number of parameters of various types: see the numbers in `$this->filters`.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (mixed) The filter first parameter, we don't wan't to kill everything.
	 */
	public function log_filter() {
		$tag  = current_filter();
		$args = func_get_args();

		$this->log( 'filter', $tag, $args );
		return $args[0];
	}

	/**
	 * Log an action.
	 * Params: (mixed) Any number of parameters of various types: see the numbers in `$this->actions`.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function log_action() {
		$tag  = current_filter();
		$args = func_get_args();

		$this->log( 'action', $tag, $args );
	}

	/**
	 * Temporary store a Log in queue.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $type   The Log type (action, filter, option|new...).
	 * @param (string) $target The Log code (action name, filter name, option name).
	 * @param (array)  $data   Some data that may be used to describe what happened.
	 */
	protected function log( $type, $target, $data = null ) {
		static $done = false;

		// Build the Log array.
		$log = array(
			'type'   => $type,
			'target' => $target,
			'data'   => (array) $data,
			'time'   => current_time( 'mysql' ),
			'order'  => microtime(),
		);

		$log_inst = new IMGT_Log( $log );

		// The data has been preprocessed: add it to the array.
		$log['data'] = $log_inst->get_data();

		// Possibility not to log this action.
		if ( ! $log['data'] ) {
			return;
		}

		// Add this Log to the queue.
		$this->logs_queue[] = $log;

		if ( $done ) {
			return;
		}
		$done = true;

		// Launch the hook that will save them all in the database.
		add_action( 'shutdown', array( $this, 'save_current_logs' ) );
	}


	/** Other hooks ============================================================================= */

	/**
	 * Register the Post Type.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function register_post_type() {
		$post_type_labels = array(
			'name'                  => _x( 'Logs', 'post type general name', 'imagify-tools' ),
			'singular_name'         => _x( 'Log', 'post type singular name', 'imagify-tools' ),
			'menu_name'             => _x( 'Logs', 'post type general name', 'imagify-tools' ),
			'all_items'             => __( 'All Logs', 'imagify-tools' ),
			'add_new'               => _x( 'Add New', 'imagify_tools_log', 'imagify-tools' ),
			'add_new_item'          => __( 'Add New Log', 'imagify-tools' ),
			'edit_item'             => __( 'Edit Log', 'imagify-tools' ),
			'new_item'              => __( 'New Log', 'imagify-tools' ),
			'view_item'             => __( 'View Log', 'imagify-tools' ),
			'items_archive'         => _x( 'Logs', 'post type general name', 'imagify-tools' ),
			'search_items'          => __( 'Search Logs', 'imagify-tools' ),
			'not_found'             => __( 'No logs found.', 'imagify-tools' ),
			'not_found_in_trash'    => __( 'No logs found in Trash.', 'imagify-tools' ),
			'parent_item_colon'     => __( 'Parent Log:', 'imagify-tools' ),
			'archives'              => __( 'Log Archives', 'imagify-tools' ),
			'insert_into_item'      => __( 'Insert into log', 'imagify-tools' ),
			'uploaded_to_this_item' => __( 'Uploaded to this log', 'imagify-tools' ),
			'filter_items_list'     => __( 'Filter logs list', 'imagify-tools' ),
			'items_list_navigation' => __( 'Logs list navigation', 'imagify-tools' ),
			'items_list'            => __( 'Logs list', 'imagify-tools' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => $post_type_labels,
				'capability_type' => self::POST_TYPE,
				'supports'        => false,
				'rewrite'         => false,
				'map_meta_cap'    => true,
				'capabilities'    => array(
					'read' => 'read_' . self::POST_TYPE . 's',
				),
			)
		);

		$this->init_done = true;
	}

	/**
	 * Filter the unique post slug: we need to allow duplicates.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $slug          The post slug.
	 * @param (int)    $post_id       Post ID.
	 * @param (string) $post_status   The post status.
	 * @param (string) $post_type     Post type.
	 * @param (int)    $post_parent   Post parent ID.
	 * @param (string) $original_slug The original post slug.
	 *
	 * @return (string) The slug.
	 */
	public function allow_log_name_duplicates( $slug, $post_id, $post_status, $post_type, $post_parent, $original_slug ) {
		if ( self::POST_TYPE !== $post_type ) {
			return $slug;
		}

		/**
		 * The slug should be provided with a "imgt_" prefix.
		 * That way, when `wp_unique_post_slug()` checks for duplicates, it won't find any, we save one useless request to the database.
		 */
		return preg_replace( '/^' . self::PREFIX . '_/', '', $original_slug );
	}

	/**
	 * If some Logs have been delayed, store them now.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function save_delayed_logs() {
		$logs = self::get_stored_delayed_logs();

		if ( ! $logs ) {
			return;
		}

		self::delete_stored_delayed_logs();

		$added = 0;

		if ( is_multisite() ) {
			// On multisites, create posts in the main blog.
			switch_to_blog( imagify_tools_get_main_blog_id() );
		}

		foreach ( $logs as $log ) {
			// Create the Log.
			if ( isset( $log['args'], $log['meta'] ) && self::insert_log( $log['args'], $log['meta'] ) ) {
				++$added;
			}
		}

		// Limit the number of Logs stored in the database.
		if ( $added ) {
			$this->limit_logs_number();
		}

		if ( is_multisite() ) {
			restore_current_blog();
		}
	}

	/**
	 * Save all new Logs.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function save_current_logs() {
		$this->save_logs( $this->logs_queue );
		$this->logs_queue = array();
	}


	/** Internal ================================================================================ */

	/**
	 * Store new Logs. If the maximum number of Logs is reached, the oldest ones are deleted.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (array) $new_logs The new Logs: an array of arrays.
	 *
	 * @return (int) Number of Logs added.
	 */
	protected function save_logs( $new_logs ) {
		if ( ! $new_logs ) {
			return 0;
		}

		$added   = 0;
		$user_id = 0;

		if ( is_multisite() ) {
			// On multisite, create posts in the main blog.
			switch_to_blog( imagify_tools_get_main_blog_id() );

			// A post author is needed.
			$user_id = self::get_default_super_administrator();
		}

		if ( ! $user_id ) {
			$user_id = self::get_default_administrator();
		}

		// Maybe it's too soon, we can't save logs before the custom post type is registered.
		if ( ! $this->init_done ) {
			// The custom post type is not registered yet, we will store the logs in a transient and create them later.
			$delayed_logs = self::get_stored_delayed_logs();
		}

		foreach ( $new_logs as $new_log ) {
			$args = array(
				'post_type'   => self::POST_TYPE,  // Post type.
				'post_date'   => $new_log['time'], // Post date / Time.
				'menu_order'  => 0,                // Menu order / Microtime.
				'post_status' => 'notpublic',      // Post status.
				'post_author' => $user_id,         // Post author: needed to create the post, we don't want the current user to create it.
			);

			// Menu order / Microtime.
			if ( ! empty( $new_log['order'] ) ) {
				if ( ! is_int( $new_log['order'] ) ) {
					// It's a microtime.
					$new_log['order'] = explode( ' ', $new_log['order'] );  // Ex: array( '0.03746700', '1452528510' ).
					$new_log['order'] = reset( $new_log['order'] );         // Ex: '0.03746700'.
					$new_log['order'] = explode( '.', $new_log['order'] );  // Ex: array( '0', '03746700' ).
					$new_log['order'] = end( $new_log['order'] );           // Ex: '03746700'.
					$new_log['order'] = (int) str_pad( $new_log['order'], 8, '0', STR_PAD_RIGHT ); // We make sure we have '03746700', not '037467'.
				}

				$args['menu_order'] = $new_log['order'];
			}

			// Post name / Type: option, network_option, action, filter. option and network_option are suffixed with `|add` or `|update`.
			if ( ! empty( $new_log['type'] ) ) {
				$args['post_name'] = self::PREFIX . '_' . str_replace( '|', '-', $new_log['type'] );
			}

			// Post title / Target: option name, action name, filter name, URI.
			if ( ! empty( $new_log['target'] ) ) {
				$args['post_title'] = $new_log['target'];
			}

			// Guid: don't let WordPress do its stuff.
			$args['guid'] = $args['post_date'] . str_pad( $args['menu_order'], 8, '0', STR_PAD_RIGHT );
			$args['guid'] = str_replace( array( ' ', '-', ':' ), '', $args['guid'] );

			// It's too soon, we need to delay the log creation.
			if ( ! $this->init_done ) {
				$delayed_logs[] = array(
					'args' => $args,
					'meta' => $new_log,
				);
				++$added;
			}
			// Create the Log.
			elseif ( self::insert_log( $args, $new_log ) ) {
				++$added;
			}
		}

		if ( $added ) {
			if ( ! $this->init_done ) {
				// Store the delayed logs.
				self::store_delayed_logs( $delayed_logs );
			}
			else {
				// Limit the number of Logs stored in the database.
				$this->limit_logs_number();
			}
		}

		if ( is_multisite() ) {
			restore_current_blog();
		}

		return $added;
	}

	/**
	 * Create a Log.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (array) $args  Arguments for `wp_insert_post()`.
	 * @param (array) $meta  An array containing some post meta to add.
	 *
	 * @return (int|bool) The post ID on success. False on failure.
	 */
	protected static function insert_log( $args, $meta ) {
		// Create the Log.
		$post_id = wp_insert_post( $args );

		if ( ! $post_id ) {
			return false;
		}

		// Meta: data.
		if ( ! empty( $meta['data'] ) ) {
			update_post_meta( $post_id, 'data', imagify_tools_compress_data( $meta['data'] ) );
		}

		return $post_id;
	}


	/** Internal tools ========================================================================== */

	/**
	 * Limit the number of Logs by deleting the old ones.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function limit_logs_number() {
		$logs = $this->get_logs(
			array(
				'fields'         => 'ids',
				'offset'         => 500,
				'posts_per_page' => 100000, // If -1, 'offset' won't work. Any large number does the trick.
				'order'          => 'DESC',
			)
		);

		if ( $logs ) {
			foreach ( $logs as $post_id ) {
				$this->delete_log( $post_id );
			}
		}
	}

	/**
	 * Get the default administrator.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (int) A user ID.
	 */
	protected static function get_default_administrator() {
		$user_ids = get_users(
			array(
				'blog_id'     => imagify_tools_get_main_blog_id(),
				'role'        => 'administrator',
				'number'      => 1,
				'orderby'     => 'ID',
				'fields'      => 'ID',
				'count_total' => false,
			)
		);

		return (int) reset( $user_ids );
	}

	/**
	 * Get the default super-administrator.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (int) A user ID.
	 */
	protected static function get_default_super_administrator() {
		global $wpdb;

		$super_admins = get_super_admins();

		if ( ! $super_admins || ! is_array( $super_admins ) ) {
			return 0;
		}

		$super_admins = implode( "','", esc_sql( $super_admins ) );
		$user_ids     = $wpdb->get_col( "SELECT ID FROM $wpdb->users WHERE user_login IN ('$super_admins') ORDER BY ID ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $user_ids ) {
			return 0;
		}

		$administrators = get_users(
			array(
				'blog_id'     => imagify_tools_get_main_blog_id(),
				'role'        => 'administrator',
				'number'      => 100,
				'orderby'     => 'ID',
				'fields'      => 'ID',
				'count_total' => false,
			)
		);

		$user_ids = array_intersect( $user_ids, $administrators );

		return $user_ids ? (int) reset( $user_ids ) : 0;
	}

	/**
	 * Get the delayed Logs stored in a transient.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (array) An array of logs.
	 */
	protected static function get_stored_delayed_logs() {
		$logs = imagify_tools_get_site_transient( self::DELAYED_LOGS_TRANSIENT_NAME );

		if ( false !== $logs && ! is_array( $logs ) ) {
			self::delete_stored_delayed_logs();
		}

		return is_array( $logs ) ? $logs : array();
	}

	/**
	 * Delete the transient containing the delayed Logs.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected static function delete_stored_delayed_logs() {
		imagify_tools_delete_site_transient( self::DELAYED_LOGS_TRANSIENT_NAME );
	}

	/**
	 * Store the delayed Logs in a transient.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (array) $logs An array of logs.
	 */
	protected static function store_delayed_logs( $logs ) {
		if ( ! $logs || ! is_array( $logs ) ) {
			self::delete_stored_delayed_logs();
			return;
		}

		imagify_tools_set_site_transient( self::DELAYED_LOGS_TRANSIENT_NAME, $logs, 30 * MINUTE_IN_SECONDS );
	}


	/** Public Helpers ========================================================================== */

	/**
	 * Get stored Logs.
	 * Some default arguments (like post_type and post_status) are already set by `$this->logs_query_args()`.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_posts/.
	 * @see https://codex.wordpress.org/Class_Reference/WP_Query#Parameters.
	 *
	 * @param (array) $args Arguments meant for `WP_Query`.
	 *
	 * @return (array) An array of Logs.
	 */
	public function get_logs( $args = array() ) {
		return get_posts( $this->logs_query_args( $args ) );
	}

	/**
	 * Delete some Logs.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (int) Number of deleted Logs.
	 */
	public function delete_logs() {
		global $wpdb;

		$args = func_get_args();

		if ( ! isset( $args[0] ) ) {
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", self::POST_TYPE ) );
		} elseif ( is_array( $args[0] ) ) {
			$post_ids = $args[0];
		} else {
			return 0;
		}

		if ( ! $post_ids ) {
			return 0;
		}

		// Delete Postmeta.
		$sql = sprintf( "DELETE FROM $wpdb->postmeta WHERE post_id IN (%s)", implode( ',', $post_ids ) );
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Delete Posts.
		$sql = sprintf( "DELETE FROM $wpdb->posts WHERE ID IN (%s)", implode( ',', $post_ids ) );
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return count( $post_ids );
	}

	/**
	 * Delete one Log.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (int) $post_id The Log ID.
	 *
	 * @return (bool) True, if succeed. False, if failure.
	 */
	public function delete_log( $post_id ) {
		return wp_delete_post( (int) $post_id, true );
	}

	/**
	 * Tell if a log exists.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param int $post_id A Log ID.
	 *
	 * @return bool|int The Log ID on success. False on failure.
	 */
	public function log_exists( $post_id ) {
		$post_id = (int) $post_id;

		if ( $post_id <= 0 ) {
			false;
		}

		$log = get_post( $post_id );

		if ( ! $log ) {
			return false;
		}

		return self::POST_TYPE === $log->post_type ? (int) $log->ID : false;
	}

	/**
	 * Build args for a Logs query.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (array) $args Some `WP_Query` arguments.
	 *
	 * @return (array) The new args merged with default args.
	 */
	public function logs_query_args( $args = array() ) {
		return array_merge(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'notpublic',
				'posts_per_page' => -1,
				'orderby'        => 'date menu_order',
				'order'          => 'DESC',
			),
			$args
		);
	}
}
