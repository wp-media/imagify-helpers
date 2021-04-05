<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that display the logs.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 * @source  Adapted from SecuPress
 */
class IMGT_Logs_List_Table extends WP_List_Table {

	const VERSION = '1.0';

	/**
	 * Current Log.
	 *
	 * @var object
	 */
	protected $log = false;

	/**
	 * Query vars used to fetch Logs.
	 *
	 * @var array
	 */
	private $imgt_query_vars;

	/**
	 * Constructor.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 * @see WP_List_Table::__construct() for more information on default arguments.
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'plural' => $args['screen']->post_type,
				'screen' => $args['screen'],
			)
		);
	}

	/**
	 * Get the current Log.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * return object
	 */
	public function get_log() {
		return $this->log;
	}

	/**
	 * Prepare all the things.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function prepare_items() {
		global $avail_post_stati, $wp_query, $per_page, $mode;

		// Set the infos we need.
		$post_type = $this->screen->post_type;

		// Set some globals.
		$mode     = 'list';
		$per_page = $this->get_items_per_page( 'edit_' . $post_type . '_per_page' );

		/** This filter is documented in wp-admin/includes/post.php */
		$per_page = apply_filters( 'edit_posts_per_page', $per_page, $post_type );

		$avail_post_stati = get_available_post_statuses( $post_type );

		// Get posts.
		$this->query();

		if ( $wp_query->found_posts || $this->get_pagenum() === 1 ) {
			$total_items = $wp_query->found_posts;
		} else {
			$post_counts = (array) wp_count_posts( $post_type );
			$total_items = array_sum( $post_counts );
		}

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Query the Posts.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function query() {
		global $avail_post_stati;

		// Prepare the query args.
		$args = IMGT_Logs::get_instance()->logs_query_args(
			array(
				'post_type' => $this->screen->post_type,
			)
		);

		// Order by.
		$orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );

		if ( $orderby ) {
			switch ( $orderby ) {
				case 'date':
					$args['orderby'] = 'date menu_order';
					break;
				default:
					$args['orderby'] = $orderby;
			}
		}

		if ( empty( $args['orderby'] ) ) {
			$args['orderby'] = 'date menu_order';
		}

		// Order.
		if ( empty( $args['order'] ) ) {
			$args['order'] = 'date menu_order' === $args['orderby'] ? 'DESC' : 'ASC';
		}

		$order         = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
		$args['order'] = $order ? $order : $args['order'];

		// Posts per page.
		$args['posts_per_page'] = (int) get_user_option( 'edit_' . $args['post_type'] . '_per_page' );

		if ( empty( $posts_per_page ) || $args['posts_per_page'] < 1 ) {
			$args['posts_per_page'] = 20;
		}

		/** This filter is documented in wp-admin/includes/post.php */
		$args['posts_per_page'] = apply_filters( 'edit_' . $args['post_type'] . '_per_page', $args['posts_per_page'] );

		/** This filter is documented in wp-admin/includes/post.php */
		$args['posts_per_page'] = apply_filters( 'edit_posts_per_page', $args['posts_per_page'], $args['post_type'] );

		// Don't allow plugins to mess our request.
		$min_prio = defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : -2147483648; // phpcs:ignore PHPCompatibility.Constants.NewConstants.php_int_minFound
		add_filter( 'request', array( $this, 'store_request' ), $min_prio + 10 );
		add_filter( 'request', array( $this, 'force_initial_request' ), PHP_INT_MAX - 10 );

		// Get posts.
		wp( $args );

		remove_filter( 'request', array( $this, 'store_request' ), $min_prio + 10 );
		remove_filter( 'request', array( $this, 'force_initial_request' ), PHP_INT_MAX - 10 );
	}

	/**
	 * Store the request query vars.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  array $query_vars The query vars.
	 * @return array
	 */
	public function store_request( $query_vars ) {
		$this->imgt_query_vars = $query_vars;

		return $query_vars;
	}

	/**
	 * Put the stored request query vars back.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  array $query_vars The query vars.
	 * @return array
	 */
	public function force_initial_request( $query_vars ) {
		$query_vars = $this->imgt_query_vars;
		unset( $this->imgt_query_vars );

		return $query_vars;
	}

	/**
	 * Tell if we have Posts.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_items() {
		return have_posts();
	}

	/**
	 * Display a message telling no Posts are to be found.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function no_items() {
		echo get_post_type_object( $this->screen->post_type )->labels->not_found;
	}

	/**
	 * Determine if the current view is the "All" view.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return bool Whether the current view is the "All" view.
	 */
	protected function is_base_request() {
		$vars = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $vars['paged'] );

		if ( empty( $vars ) ) {
			return true;
		} elseif ( 1 === count( $vars ) && ! empty( $vars['post_type'] ) ) {
			return $this->screen->post_type === $vars['post_type'];
		}

		return 1 === count( $vars );
	}

	/**
	 * Get links allowing to filter the Posts by post status.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_views() {
		return array();
	}

	/**
	 * Get bulk actions that will be displayed in the `<select>`.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			IMGT_Admin_Post::get_action( 'bulk_delete_logs' ) => __( 'Delete Permanently', 'imagify-tools' ),
		);
	}

	/**
	 * Display "Delete All" and "Downlad All" buttons.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 * @author Grégory Viguier (Geoffrey)
	 *
	 * @param string $which The position: "top" or "bottom".
	 */
	public function extra_tablenav( $which ) {
		?>
		<div class="imgt-quick-actions alignleft actions">
			<?php
			if ( 'top' === $which && $this->has_items() ) {
				// "Downlad All" button.
				$href = $this->get_view()->download_logs_url( $this->paged_page_url() );
				?>
				<a id="download_all" class="imgt-button imgt-button-primary imgt-button-mini imgt-download-logs" href="<?php echo esc_url( $href ); ?>">
					<span class="text">
						<?php _e( 'Download All', 'imagify-tools' ); ?>
					</span>
				</a>

				<?php
				// "Delete All" button.
				$href = $this->get_view()->delete_logs_url( $this->paged_page_url() );
				?>
				<a id="delete_all" class="imgt-button imgt-button-secondary imgt-button-mini imgt-clear-logs" href="<?php echo esc_url( $href ); ?>">
					<span class="text">
						<?php _e( 'Delete All', 'imagify-tools' ); ?>
					</span>
				</a>
				<?php } ?>
		</div>
		<?php
		/** This action is documented in wp-admin/includes/class-wp-posts-list-table.php */
		do_action( 'manage_posts_extra_tablenav', $which );
	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param string $which The position: "top" or "bottom".
	 */
	public function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'imgt-bulk-logs', '_wpnonce', false );

			// Use a custom referer input, we don't want superfuous paramaters in the URL.
			echo '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( $this->paged_page_url() ) . '" />';

			$args = wp_parse_url( $this->paged_page_url() );
			$args = ! empty( $args['query'] ) ? $args['query'] : '';

			if ( $args ) {
				// Display all other parameters ("page" is the most important).
				$args = explode( '&', $args );

				foreach ( $args as $arg ) {
					$arg = explode( '=', $arg );

					if ( isset( $arg[1] ) ) {
						echo '<input type="hidden" name="' . $arg[0] . '" value="' . $arg[1] . "\"/>\n";
					}
				}
			}
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php
			if ( 'top' === $which && $this->has_items() ) {
				?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
			}

			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Get the classes to use on the `<table>`.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'posts' );
	}

	/**
	 * Get the columns we are going to display.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'    => '<input type="checkbox" />',
			/** Translators: manage posts column name */
			'title' => _x( 'Title', 'column name' ),
			'date'  => __( 'Date' ),
		);
	}

	/**
	 * Get the columns that can be sorted.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'title' => 'title',
			'date'  => array( 'date', true ),
		);
	}

	/**
	 * Display the rows.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param array $posts An array of posts.
	 * @param int   $level Level of the post (level as in parent/child relation).
	 */
	public function display_rows( $posts = array(), $level = 0 ) {
		global $wp_query, $per_page;

		if ( empty( $posts ) ) {
			$posts = $wp_query->posts;
		}

		$this->_display_rows( $posts, $level );
	}

	/**
	 * Display the rows.
	 * The current Log is set here.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param array $posts An array of posts.
	 * @param int   $level Level of the post (level as in parent/child relation).
	 */
	private function _display_rows( $posts, $level = 0 ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		foreach ( $posts as $post ) {
			$this->log = new IMGT_Log( $post );
			$this->single_row( $post, $level );
		}

		$this->log = false;
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @since  1.0
	 * @since  WP 4.3.0
	 * @author Grégory Viguier
	 *
	 * @param object $post The current WP_Post object.
	 */
	public function column_cb( $post ) {
		?>
		<label for="cb-select-<?php the_ID(); ?>">
			<span class="screen-reader-text">
				<?php
				/* translators: %s is a Post title. */
				printf( __( 'Select &#8220;%s&#8221;', 'imagify-tools' ), wp_strip_all_tags( $this->log->get_title() ) );
				?>
			</span>
			<input id="cb-select-<?php the_ID(); ?>" type="checkbox" name="post[]" value="<?php the_ID(); ?>" />
		</label>
		<?php
	}

	/**
	 * Handles the title column output.
	 *
	 * @since  1.0
	 * @since  WP 4.3.0
	 * @author Grégory Viguier
	 *
	 * @param object $post    The current WP_Post object.
	 * @param string $classes The cell classes.
	 * @param string $data    Cell data attributes.
	 * @param string $primary Name of the priramy column.
	 */
	protected function _column_title( $post, $classes, $data, $primary ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		echo '<td class="' . $classes . ' page-title" ', $data, '>';
			echo $this->column_title( $post );
			echo $this->handle_row_actions( $post, 'title', $primary );
		echo '</td>';
	}

	/**
	 * Handles the title column content.
	 *
	 * @since  1.0
	 * @since  WP 4.3.0
	 * @author Grégory Viguier
	 *
	 * @param object $post The current WP_Post object.
	 */
	public function column_title( $post ) {
		global $avail_post_stati;

		$title     = $this->log->get_title();
		$view_href = add_query_arg( array( 'log' => $post->ID ), $this->paged_page_url() );

		/* translators: %s is a Post title. */
		echo '<a class="imgt-view-log" href="' . esc_url( $view_href ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'imagify-tools' ), wp_strip_all_tags( $title ) ) ) . '">';
			echo $title;
		echo "</a>\n";

		if ( ! imagify_tools_wp_version_is( '4.3.0' ) ) {
			echo $this->handle_row_actions( $post, 'title', $this->get_default_primary_column_name() );
		}
	}

	/**
	 * Handles the post date column output.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param object $post The current WP_Post object.
	 */
	public function column_date( $post ) {
		echo $this->log->get_time( __( '\<\b\>Y/m/d\<\/\b\> g:i:s a', 'imagify-tools' ) );
	}

	/**
	 * Handles the default column output.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param object $post        The current WP_Post object.
	 * @param string $column_name The current column name.
	 */
	public function column_default( $post, $column_name ) {
		/** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
		do_action( 'manage_posts_custom_column', $column_name, $post->ID );

		/** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
		do_action( "manage_{$post->post_type}_posts_custom_column", $column_name, $post->ID );
	}

	/**
	 * Display a row.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param int|object $post  The current post ID or WP_Post object.
	 * @param int        $level Level of the post (level as in parent/child relation).
	 */
	public function single_row( $post, $level = 0 ) {
		$global_post = get_post();
		$post        = get_post( $post );

		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		?>
		<tr id="post-<?php echo $post->ID; ?>" class="<?php echo implode( ' ', get_post_class( 'level-0', $post->ID ) ); ?>">
			<?php $this->single_row_columns( $post ); ?>
		</tr>
		<?php
		$GLOBALS['post'] = $global_post;
	}

	/**
	 * Get the name of the default primary column.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string Name of the default primary column, in this case, 'title'.
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}

	/**
	 * Generate and display row action links.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  object $post        Current WP_Post object.
	 * @param  string $column_name Current column name.
	 * @param  string $primary     Primary column name.
	 * @return string Row actions output for posts.
	 */
	protected function handle_row_actions( $post, $column_name, $primary ) {
		global $avail_post_stati;

		if ( $primary !== $column_name ) {
			return '';
		}

		$delete_href = $this->get_view()->delete_log_url( $post->ID, $this->page_url() );
		$view_href   = add_query_arg( array( 'log' => $post->ID ), $this->paged_page_url() );

		$actions = array(
			'delete' => '<a class="imgt-delete-log submitdelete" href="' . esc_url( $delete_href ) . '" title="' . esc_attr__( 'Delete this item permanently', 'imagify-tools' ) . '">' . __( 'Delete Permanently', 'imagify-tools' ) . '</a>',
			'view'   => '<a class="imgt-view-log" href="' . esc_url( $view_href ) . '" title="' . esc_attr__( 'View this log details', 'imagify-tools' ) . '" tabindex="-1">' . __( 'View', 'imagify-tools' ) . '</a>',
		);

		return $this->row_actions( $actions );
	}

	/**
	 * The page URL.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function page_url() {
		return self_admin_url( 'admin.php?page=' . IMGT_Admin_Pages::get_current_page() );
	}

	/**
	 * The page URL, with the page number parameter.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function paged_page_url() {
		$page_url = $this->page_url();
		$pagenum  = $this->get_pagenum();

		if ( $pagenum > 1 ) {
			$page_url = add_query_arg( 'paged', $pagenum, $page_url );
		}

		return $page_url;
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
		return IMGT_Admin_Pages::get_instance()->get_view();
	}
}
