<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Log class.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 * @source  Adapted from SecuPress
 */
class IMGT_Log {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * A DATETIME formated date.
	 *
	 * @var (string)
	 */
	protected $time = '';

	/**
	 * Part of the result of `microtime()`.
	 * Ex: `0.03746700 1452528510` => `3746700`.
	 *
	 * @var (int)
	 */
	protected $order = 0;

	/**
	 * The Log type: option, network_option, filter, action. ONLY USE `[a-z0-9_]` CHARACTERS, NO `-`!
	 *
	 * @var (string)
	 */
	protected $type = '';

	/**
	 * The Log sub-type: used only with option and network_option, it can be "add" or "update".
	 *
	 * @var (string)
	 */
	protected $subtype = '';

	/**
	 * An identifier: option name, hook name...
	 *
	 * @var (string)
	 */
	protected $target = '';

	/**
	 * The Log data: basically its content will be used in `vsprintf()`.
	 *
	 * @var (array)
	 */
	protected $data = array();

	/**
	 * Tell if the data has been prepared and escaped before display.
	 *
	 * @var (bool)
	 */
	protected $data_escaped = false;

	/**
	 * The Log title.
	 *
	 * @var (string)
	 */
	protected $title = '';

	/**
	 * The Log message.
	 *
	 * @var (string)
	 */
	protected $message = '';


	/** Instance ================================================================================ */

	/**
	 * Constructor.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (array|object) $args An array containing the following arguments. If a `WP_Post` is used, it is converted in an adequate array.
	 *                             - (string) $time   A DATETIME formated date.
	 *                             - (int)    $order  Part of the result of `microtime()`.
	 *                             - (string) $type   The Log type + subtype separated with a `|`.
	 *                             - (string) $target An identifier.
	 *                             - (array)  $data   The Log data: basically what will be used in `vsprintf()` (log title and message).
	 */
	public function __construct( $args ) {
		$process_data = true;

		if ( ! is_array( $args ) ) {
			// If it's a Post, convert it in an adequate array.
			$args         = self::post_to_args( $args );
			$process_data = false;
		}

		$args = array_merge(
			array(
				'time'   => '',
				'order'  => 0,
				'type'   => '',
				'target' => '',
				'data'   => array(),
			),
			$args
		);

		// Extract the subtype from the type.
		$args['type'] = self::split_subtype( $args['type'] );

		$this->time    = esc_html( $args['time'] );
		$this->order   = (int) $args['order'];
		$this->type    = esc_html( $args['type']['type'] );
		$this->subtype = esc_html( $args['type']['subtype'] );
		$this->target  = esc_html( $args['target'] );
		$this->data    = (array) $args['data'];

		if ( $process_data ) {
			/**
			 * The data needs to be preprocessed before being inserted in the database.
			 */
			$this->pre_process_data();
		}
	}


	/** Public methods ========================================================================== */

	/**
	 * Get the Log formated date and time.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $format See http://de2.php.net/manual/en/function.date.php.
	 *
	 * @return (string) The formated date.
	 */
	public function get_time( $format = false ) {
		if ( ! is_string( $format ) ) {
			$format = __( 'Y/m/d g:i:s a' );
		}

		return mysql2date( $format, $this->time, true );
	}

	/**
	 * Get the Log title.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (string) A title containing some related data.
	 */
	public function get_title() {
		$this->set_title();
		return $this->title;
	}

	/**
	 * Get the Log message.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (string) A message containing all related data.
	 */
	public function get_message() {
		$this->set_message();

		if ( preg_match( "/^<pre>(.+\n.+)<\/pre>$/", $this->message, $matches ) ) {
			$data[ $key ] = '<code>' . substr( $matches[1], 0, 50 ) . '&hellip;</code>';
		}

		return $this->message;
	}

	/**
	 * Get the data.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (array)
	 */
	public function get_data() {
		return $this->data;
	}


	/** Internal methods ======================================================================== */

	/** Data ==================================================================================== */

	/**
	 * Set the data.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (array) $data The data.
	 */
	protected function set_data( $data ) {
		$this->data = $data;
	}

	/**
	 * Prepare and escape the data. This phase is mandatory before displaying it in the Logs list.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return (bool) True if ready to be displayed. False if not or empty.
	 */
	protected function escape_data() {
		static $color_done = false;

		if ( ! $this->data ) {
			return false;
		}

		if ( $this->data_escaped ) {
			return true;
		}

		$this->data_escaped = true;

		if ( ! $color_done ) {
			$color_done = true;

			// Make sure we have the default values, or our CSS won't work.
			if ( wp_is_ini_value_changeable( 'highlight.default' ) ) {
				ini_set( 'highlight.default', '#0000BB' );
			}
			if ( wp_is_ini_value_changeable( 'highlight.keyword' ) ) {
				ini_set( 'highlight.keyword', '#007700' );
			}
			if ( wp_is_ini_value_changeable( 'highlight.string' ) ) {
				ini_set( 'highlight.string', '#DD0000' );
			}
		}

		// Prepare and escape the data.
		foreach ( $this->data as $key => $data ) {
			if ( is_null( $data ) ) {
				$this->data[ $key ] = '<em>[null]</em>';
			} elseif ( true === $data ) {
				$this->data[ $key ] = '<em>[true]</em>';
			} elseif ( false === $data ) {
				$this->data[ $key ] = '<em>[false]</em>';
			} elseif ( '' === $data ) {
				// If changed, also change it in `IMGT_Log::set(_network)_option_title()` and `::set(_network)_option_message()`.
				$this->data[ $key ] = '<em>[' . __( 'empty string', 'imagify-tools' ) . ']</em>';
			} else {
				if ( ! is_scalar( $data ) ) {
					$data = call_user_func( 'var_export', $data, true );
				}

				if ( substr_count( $data, "\n" ) ) {
					// Add some (uggly) colors.
					$data = highlight_string( "<?php\n$data", true );
					// Remove wrappers.
					$data = preg_replace( '@^<code>\s*<span style="color: *#000000">\s*(.*)\s*</span>\s*</code>$@', '$1', $data );
					// Remove the first `<?php`.
					if ( preg_match( '@^(<span .+>)&lt;\?php<br \/>(</span>)?@', $data, $matches ) ) {
						$replacement = ! empty( $matches[2] ) ? '' : '$1';
						$data        = preg_replace( '@^(<span .+>)&lt;\?php<br \/>(</span>)?@', $replacement, $data );
					}
					// Replace the `style` attributes by `class` attributes.
					$data = preg_replace( '@<span style="color: #([0-9A-F]+)">@', '<span class="imgt-code-color imgt-code-color-$1">', $data );

					$this->data[ $key ] = "<pre><code>$data</code></pre>";
				} elseif ( strlen( $data ) > 50 ) {
					// 50 seems to be a good limit between short and long code.
					$this->data[ $key ] = '<pre><code>' . esc_html( $data ) . '</code></pre>';
				} else {
					$this->data[ $key ] = '<code>' . esc_html( $data ) . '</code>';
				}
			}
		}

		return true;
	}

	/** Pre-process data ======================================================================== */

	/**
	 * Prepare the data to be ready for `vsprintf()`.
	 * This will be used before storing the Log in database.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function pre_process_data() {
		// Pre-proccess (maybe).
		$method_name = str_replace( array( '.', '-', '|' ), '_', $this->target );
		$method_name = 'pre_process_' . $this->type . ( $this->subtype ? '_' . $this->subtype : '' ) . '_' . $method_name;

		if ( method_exists( $this, $method_name ) ) {
			$this->data = (array) call_user_func_array( array( $this, $method_name ), $this->data );
		}
	}

	/**
	 * Fires after an HTTP API response is received and before the response is returned.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  array|object $response HTTP response or WP_Error object.
	 * @param  string       $context  Context under which the hook is fired.
	 * @param  string       $class    HTTP transport used.
	 * @param  array        $args     HTTP request arguments.
	 * @param  string       $url      The request URL.
	 * @return array                  An array containing:
	 *                                - string       $url      The requested URL.
	 *                                - array        $args     The request arguments.
	 *                                - array|object $response Array containing 'headers', 'body', 'response', 'cookies', 'filename'. A WP_Error instance upon error.
	 */
	protected function pre_process_action_http_api_debug( $response, $context, $class, $args, $url ) {
		if ( 'response' !== $context ) {
			return array();
		}

		if ( ! empty( $args['user-agent'] ) && 'Imagify Tools' === $args['user-agent'] ) {
			return compact( 'url', 'args', 'response' );
		}

		if ( preg_match( '@^https?://([^/]+\.)?imagify\.io(/|\?|$)@', $url ) ) {
			return compact( 'url', 'args', 'response' );
		}

		$ajax_url = preg_quote( admin_url( 'admin-ajax.php' ), '@' );

		if ( isset( $args['method'], $args['body']['action'] ) && 'POST' === strtoupper( $args['method'] ) && 0 === strpos( $args['body']['action'], 'imagify_' ) && preg_match( '@^' . $ajax_url . '@', $url ) ) {
			return compact( 'url', 'args', 'response' );
		}

		if ( preg_match( '@^' . $ajax_url . '.*[&?]action=imagify_@', $url ) ) {
			return compact( 'url', 'args', 'response' );
		}

		return array();
	}

	/**
	 * Fires after an HTTP API response is received and before the response is returned.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string        $url       The requested URL.
	 * @param  array         $args      The request arguments.
	 * @param  string|object $response  The request response or an Exception.
	 * @param  int           $http_code The request HTTP code.
	 * @param  string        $error     An error message.
	 * @return array
	 */
	protected function pre_process_action_imagify_curl_http_response( $url, $args, $response, $http_code = null, $error = null ) {
		return compact( 'url', 'args', 'response', 'http_code', 'error' );
	}

	/** Title =================================================================================== */

	/**
	 * Set the Log title.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_title() {
		switch ( $this->type ) {
			case 'option':
				$this->set_option_title();
				break;
			case 'network_option':
				$this->set_network_option_title();
				break;
			case 'filter':
				$this->set_filter_title();
				break;
			case 'action':
				$this->set_action_title();
				break;
			default:
				return;
		}

		/**
		 * First, `$this->title` must be set by the method extending this one.
		 */
		if ( ! $this->escape_data() ) {
			return;
		}

		$data = $this->data;

		// Replace the `<pre>` blocks with `<code>` inline blocks.
		foreach ( $data as $key => $value ) {
			if ( preg_match( '/^<pre>(?:<code>)?(.*)(?:<\/code>)?<\/pre>$/', $value, $matches ) ) {
				$matches[1]   = explode( "\n", $matches[1] );
				$matches[1]   = reset( $matches[1] );
				$data[ $key ] = '<code>' . wp_strip_all_tags( $matches[1] ) . '</code>';
			}
		}

		// Add the data to the title.
		$this->title = vsprintf( $this->title, $data );
	}

	/**
	 * Set the raw Log title for an option.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_option_title() {
		if ( 'add' === $this->subtype ) {
			/* translators: 1 is an option name. */
			$this->title = __( 'Option %s created', 'imagify-tools' );
		} else {
			/* translators: 1 is an option name. */
			$this->title = __( 'Option %s updated', 'imagify-tools' );
		}
	}

	/**
	 * Set the raw Log title for a network option.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_network_option_title() {
		if ( 'add' === $this->subtype ) {
			/* translators: 1 is an option name. */
			$this->title = __( 'Network option %s created', 'imagify-tools' );
		} else {
			/* translators: 1 is an option name. */
			$this->title = __( 'Network option %s updated', 'imagify-tools' );
		}
	}

	/**
	 * Set the raw Log title for a filter.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_filter_title() {
		// Nothing yet.
		$this->title = '';
	}

	/**
	 * Set the raw Log title for an action.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_action_title() {
		$titles = array(
			/* translators: 1 is a URL. */
			'http_api_debug'             => __( 'External request to %s', 'imagify-tools' ),
			'imagify_curl_http_response' => __( 'Image optimization', 'imagify-tools' ),
		);

		$this->title = isset( $titles[ $this->target ] ) ? $titles[ $this->target ] : '';
	}


	/** Message ================================================================================= */

	/**
	 * Set the Log message.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_message() {
		// Set the raw message.
		switch ( $this->type ) {
			case 'option':
				$this->set_option_message();
				break;
			case 'network_option':
				$this->set_network_option_message();
				break;
			case 'filter':
				$this->set_filter_message();
				break;
			case 'action':
				$this->set_action_message();
				break;
			default:
				return;
		}

		/**
		 * First, `$this->message` must be set by the method extending this one.
		 */
		if ( $this->escape_data() ) {
			// Make sure to have enough data to print, some messages could have been changed and need new (missing) information.
			$this->data[] = '';
			$this->data[] = '';
			$this->data[] = '';
			$this->data[] = '';
			$this->data[] = '';
			// Add the data to the message.
			$this->message = vsprintf( $this->message, $this->data );
		}
	}

	/**
	 * Set the raw Log message for an option.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_option_message() {
		if ( 'add' === $this->subtype ) {
			/* translators: 1 is an option name, 2 is its value. */
			$this->message = __( 'Option %1$s created with the following value: %2$s', 'imagify-tools' );
		} else {
			/* translators: 1 is an option name, 2 is its value. */
			$this->message = __( 'Option %1$s updated from the value %3$s to %2$s', 'imagify-tools' );
		}
	}

	/**
	 * Set the raw Log message for a network option.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_network_option_message() {
		if ( 'add' === $this->subtype ) {
			/* translators: 1 is an option name, 2 is its value. */
			$this->message = __( 'Network option %1$s created with the following value: %2$s', 'imagify-tools' );
		} else {
			/* translators: 1 is an option name, 2 is its value. */
			$this->message = __( 'Network option %1$s updated from the value %3$s to %2$s', 'imagify-tools' );
		}
	}

	/**
	 * Set the raw Log message for a filter.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_filter_message() {
		// Nothing yet.
		$this->message = '';
	}

	/**
	 * Set the raw Log message for an action.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	protected function set_action_message() {
		$messages = array(
			/* translators: 1 is a URL, 2 and 3 are some code. */
			'http_api_debug'             => __( 'External request to: %1$s with the following arguments: %2$s The response was: %3$s', 'imagify-tools' ),
			/* translators: 1 is a URL; 2, 3, 4, and 5 are some code. */
			'imagify_curl_http_response' => __( 'Image optimization to: %1$s with the following arguments: %2$s The response was: %3$s HTTP code was %4$s and maybe with an error: %5$s', 'imagify-tools' ),
		);

		$this->message = isset( $messages[ $this->target ] ) ? $messages[ $this->target ] : '';
	}

	/** Tools =================================================================================== */

	/**
	 * Convert a Post object into an array that can be used to instanciate a Log.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (int|object) $post A post ID or a `WP_Post` object.
	 *
	 * @return (array)
	 */
	protected static function post_to_args( $post ) {
		$post = get_post( $post );

		if ( ! $post || ! is_a( $post, 'WP_Post' ) || ! $post->ID ) {
			return array();
		}

		$args = array(
			'time'   => $post->post_date,
			'order'  => $post->menu_order,
			'type'   => $post->post_name,
			'target' => $post->post_title,
			'data'   => imagify_tools_decompress_data( get_post_meta( $post->ID, 'data', true ) ),
		);

		$args['type'] = str_replace( '-', '|', $args['type'] );

		return $args;
	}

	/**
	 * Split a type into type + sub-type.
	 * Type and sub-type are separated with a "|" caracter. Only option and network_option have a sub-type.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param (string) $type A Log type.
	 *
	 * @return (array) An array containing the type an (maybe) the sub-type.
	 */
	protected static function split_subtype( $type ) {
		$out = array(
			'type'    => $type,
			'subtype' => '',
		);

		if ( strpos( $type, '|' ) !== false ) {
			$type   = explode( '|', $type, 2 );
			$type[] = '';

			$out['type']    = $type[0];
			$out['subtype'] = $type[1];
		}

		return $out;
	}
}
