<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handle the data for the main page.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 */
class IMGT_Admin_Model_Main {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1';

	/**
	 * Info cache duration in minutes.
	 *
	 * @var int
	 */
	const CACHE_DURATION = 30;

	/**
	 * Prefix used to cache the requests.
	 *
	 * @var string
	 */
	const REQUEST_CACHE_PREFIX = 'imgt_req_';

	/**
	 * Data.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * The constructor.
	 *
	 * @since 1.0
	 * @author Grégory Viguier
	 */
	public function __construct() {
		$this->add_filesystem_section();
		$this->add_imagify_filesystem_section();
		$this->add_image_editor_section();
		$this->add_curl_section();
		$this->add_requests_section();
		$this->add_files_section();
		$this->add_various_section();
	}


	/** Build the data ========================================================================== */

	/**
	 * Add a section related to the filesystem.
	 *
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	public function add_filesystem_section() {
		/**
		 * Define FS_CHMOD_DIR and FS_CHMOD_FILE.
		 */
		imagify_tools_get_filesystem();

		/**
		 * Uploads dir and URL.
		 */
		$error_string  = '***' . __( 'Error', 'imagify-tools' ) . '***';
		$wp_upload_dir = (array) wp_upload_dir();
		$wp_upload_dir = array_merge(
			array(
				'path'    => $error_string, /* /absolute/path/to/uploads/sub/dir */
				'url'     => $error_string, /* http://example.com/wp-content/uploads/sub/dir */
				'subdir'  => $error_string, /* /sub/dir */
				'basedir' => $error_string, /* /absolute/path/to/uploads */
				'baseurl' => $error_string, /* http://example.com/wp-content/uploads */
				'error'   => $error_string, /* false */
			),
			$wp_upload_dir
		);

		if ( '' === $wp_upload_dir['error'] ) {
			$wp_upload_dir['error'] = '***' . __( 'empty string', 'imagify-tools' ) . '***';
		}
		if ( false === $wp_upload_dir['error'] ) {
			$wp_upload_dir['error'] = 'false (boolean)';
		}

		/**
		 * Chmod and backup dir.
		 */
		$chmod_dir         = fileperms( ABSPATH ) & 0777 | 0755;
		$chmod_file        = fileperms( ABSPATH . 'index.php' ) & 0777 | 0644;
		$backup_dir        = trailingslashit( $wp_upload_dir['basedir'] ) . 'backup/';
		$backup_dir_exists = file_exists( $backup_dir ) && wp_is_writable( $backup_dir );
		$imagify_settings  = get_site_option( 'imagify_settings' );

		$this->add_data_section(
			__( 'WordPress Filesystem', 'imagify-tools' ),
			array(
				array(
					'label'     => 'ABSPATH',
					'value'     => ABSPATH,
					'is_error'  => ! path_is_absolute( ABSPATH ),
					'more_info' => __( 'Should be an absolute path.', 'imagify-tools' ),
				),
				array(
					'label'     => 'IMAGIFY_PATH',
					'value'     => defined( 'IMAGIFY_PATH' ) ? IMAGIFY_PATH : __( 'Not defined', 'imagify-tools' ),
					'is_error'  => defined( 'IMAGIFY_PATH' ) && ! path_is_absolute( IMAGIFY_PATH ),
					'more_info' => __( 'Should be an absolute path.', 'imagify-tools' ),
				),
				array(
					'label'     => 'wp_upload_dir() <em>(path)</em>',
					'value'     => $wp_upload_dir['path'],
					'is_error'  => $error_string === $wp_upload_dir['path'] || ! path_is_absolute( $wp_upload_dir['path'] ),
					/* translators: %s is a file path. */
					'more_info' => __( 'Should be an absolute path.', 'imagify-tools' ),
				),
				array(
					'label'     => 'wp_upload_dir() <em>(url)</em>',
					'value'     => $wp_upload_dir['url'],
					'is_error'  => $error_string === $wp_upload_dir['url'] || ! filter_var( $wp_upload_dir['url'], FILTER_VALIDATE_URL ),
					'more_info' => __( 'Should be a valid URL.', 'imagify-tools' ),
				),
				array(
					'label'     => 'wp_upload_dir() <em>(subdir)</em>',
					'value'     => $wp_upload_dir['subdir'],
					'is_error'  => $error_string === $wp_upload_dir['path'],
					'more_info' => 'Meh',
				),
				array(
					'label'     => 'wp_upload_dir() <em>(basedir)</em>',
					'value'     => $wp_upload_dir['basedir'],
					'is_error'  => $error_string === $wp_upload_dir['basedir'] || ! path_is_absolute( $wp_upload_dir['basedir'] ),
					'more_info' => __( 'Should be an absolute path.', 'imagify-tools' ),
				),
				array(
					'label'     => 'wp_upload_dir() <em>(baseurl)</em>',
					'value'     => $wp_upload_dir['baseurl'],
					'is_error'  => $error_string === $wp_upload_dir['baseurl'] || ! filter_var( $wp_upload_dir['baseurl'], FILTER_VALIDATE_URL ),
					'more_info' => __( 'Should be a valid URL.', 'imagify-tools' ),
				),
				array(
					'label'     => 'wp_upload_dir() <em>(error)</em>',
					'value'     => $wp_upload_dir['error'],
					'compare'   => 'false (boolean)',
					/* translators: %s is a value. */
					'more_info' => sprintf( __( 'Should be %s.', 'imagify-tools' ), '<code>false (boolean)</code>' ),
				),
				array(
					'label'     => __( 'Backups folder exists and is writable', 'imagify-tools' ),
					'value'     => $backup_dir_exists,
					'is_error'  => ! empty( $imagify_settings['backup'] ) ? ! $backup_dir_exists : false,
					'more_info' => ! empty( $imagify_settings['backup'] ) ? __( 'Backup is enabled.', 'imagify-tools' ) : __( 'No need, backup is disabled.', 'imagify-tools' ),
				),
				array(
					'label'     => 'FS_CHMOD_DIR',
					'value'     => IMGT_Tools::to_octal( FS_CHMOD_DIR ) . ' (' . FS_CHMOD_DIR . ')',
					'compare'   => IMGT_Tools::to_octal( $chmod_dir ) . ' (' . $chmod_dir . ')',
					/* translators: %s is a value. */
					'more_info' => sprintf( __( 'Should be %s.', 'imagify-tools' ), '<code>' . IMGT_Tools::to_octal( $chmod_dir ) . ' (' . $chmod_dir . ')</code>' ),
				),
				array(
					'label'     => 'FS_CHMOD_FILE',
					'value'     => IMGT_Tools::to_octal( FS_CHMOD_FILE ) . ' (' . FS_CHMOD_FILE . ')',
					'compare'   => IMGT_Tools::to_octal( $chmod_file ) . ' (' . $chmod_file . ')',
					/* translators: %s is a value. */
					'more_info' => sprintf( __( 'Should be %s.', 'imagify-tools' ), '<code>' . IMGT_Tools::to_octal( $chmod_file ) . ' (' . $chmod_file . ')</code>' ),
				),
			)
		);
	}

	/**
	 * Add a section related to the filesystem.
	 *
	 * @since  1.0.4
	 * @author Grégory Viguier
	 */
	public function add_imagify_filesystem_section() {
		/**
		 * Chmod and backup dir.
		 */
		$filesystem            = imagify_tools_get_filesystem();
		$is_imagify_filesystem = $filesystem instanceof Imagify_Filesystem;
		$fields                = array();

		if ( $is_imagify_filesystem ) {
			$internal_path_test = ABSPATH;

			if ( method_exists( $filesystem, 'has_wp_its_own_directory' ) ) {
				$fields[] = array(
					'label' => __( 'WP has its own directory', 'imagify-tools' ),
					'value' => $filesystem->has_wp_its_own_directory(),
				);
			}

			if ( method_exists( $filesystem, 'get_root' ) ) {
				$fields[] = array(
					'label'     => '$filesystem->get_root() (<em>' . __( 'Server’s root', 'imagify-tools' ) . '</em>)',
					'value'     => $filesystem->get_root(),
					'is_error'  => ! path_is_absolute( $filesystem->get_root() ),
					'more_info' => __( 'Should be an absolute path.', 'imagify-tools' ),
				);

				$internal_path_test = $filesystem->get_root();
			}

			if ( method_exists( $filesystem, 'get_site_root' ) ) {
				$fields[] = array(
					'label'     => '$filesystem->get_site_root() (<em>' . __( 'Site’s root', 'imagify-tools' ) . '</em>)',
					'value'     => $filesystem->get_site_root(),
					'is_error'  => strpos( $filesystem->get_site_root(), $internal_path_test ) !== 0 || ! path_is_absolute( $filesystem->get_site_root() ),
					/* translators: %s is a file path. */
					'more_info' => sprintf( __( 'Should be an absolute path and start with %s.', 'imagify-tools' ), '<code>' . $internal_path_test . '</code>' ),
				);

				$internal_path_test = $filesystem->get_site_root();
			}

			if ( method_exists( $filesystem, 'get_abspath' ) ) {
				$fields[] = array(
					'label'     => '$filesystem->get_abspath() (<em>' . __( 'WordPress’ root', 'imagify-tools' ) . '</em>)',
					'value'     => $filesystem->get_abspath(),
					'is_error'  => strpos( $filesystem->get_abspath(), $internal_path_test ) !== 0 || ! path_is_absolute( $filesystem->get_abspath() ),
					/* translators: %s is a file path. */
					'more_info' => sprintf( __( 'Should be an absolute path and start with %s.', 'imagify-tools' ), '<code>' . $internal_path_test . '</code>' ),
				);
			}
		} else {
			$fields[] = array(
				'label' => __( 'Activate Imagify >= 1.7.1 for more data', 'imagify-tools' ),
				'value' => '',
			);
		}

		$fields = array_merge(
			$fields,
			array(
				array(
					'label'     => 'imagify_get_filesystem()',
					'value'     => $filesystem,
					'is_error'  => ! is_object( $filesystem ) || ! $filesystem || ! isset( $filesystem->errors ) || array_filter( (array) $filesystem->errors ),
					/* translators: 1 and 2 are data names. */
					'more_info' => sprintf( __( '%1$s and %2$s should be empty.', 'imagify-tools' ), '<code>WP_Error->errors</code>', '<code>WP_Error->error_data</code>' ),
				),
			)
		);

		$this->add_data_section( __( 'Imagify Filesystem', 'imagify-tools' ), $fields );
	}

	/**
	 * Add a section related to the image editor.
	 *
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	public function add_image_editor_section() {
		/**
		 * The selected editor class.
		 */
		$image_editor = $this->get_image_editor_class();

		$fields = array(
			array(
				'label'     => __( 'Selected Image Editor', 'imagify-tools' ),
				'value'     => $image_editor ? str_replace( 'WP_Image_Editor_', '', $image_editor ) : _x( 'None', 'image editor', 'imagify-tools' ),
				'is_error'  => ! $image_editor,
				/* translators: 1 and 2 are values. */
				'more_info' => sprintf( __( 'Should be %1$s or %2$s most of the time.', 'imagify-tools' ), '<code>Imagick</code>', '<code>GD</code>' ),
			),
		);

		/**
		 * Result of each class.
		 */
		$implementations = array_merge( array( $image_editor ), array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );
		$implementations = array_unique( array_filter( $implementations ) );

		if ( defined( 'IMAGIFY_PATH' ) ) {
			$image_path = IMAGIFY_PATH . 'assets/images/imagify-logo.png';
		} else {
			$image_path = admin_url( 'images/arrows.png' );
			$image_path = str_replace( site_url( '/' ), ABSPATH, $image_path );
		}

		$args = array(
			'path'       => $image_path,
			'mime_types' => IMGT_Tools::get_mime_types( 'image' ),
			'methods'    => $this->get_image_editor_methods(),
		);

		foreach ( $implementations as $implementation ) {
			$implementation_name = str_replace( 'WP_Image_Editor_', '', $implementation );

			// Existance test.
			if ( ! call_user_func( array( $implementation, 'test' ), $args ) ) {
				$fields[] = array(
					'label'    => $implementation_name,
					'value'    => _x( 'Failed existance test.', 'image editor implementation', 'imagify-tools' ),
					'is_error' => true,
				);
				continue;
			}

			// Supported mime types.
			$mime_types = array();

			foreach ( $args['mime_types'] as $mime_type ) {
				if ( ! call_user_func( array( $implementation, 'supports_mime_type' ), $mime_type ) ) {
					$mime_types[] = $mime_type;
					continue;
				}
			}

			if ( $mime_types ) {
				$fields[] = array(
					'label'    => $implementation_name,
					/* translators: %s is a list of mime types (yeah, surprise!). */
					'value'    => sprintf( _n( 'Unsupported mime type: %s.', 'Unsupported mime types: %s.', count( $mime_types ), 'imagify-tools' ), implode( ', ', $mime_types ) ),
					'is_error' => true,
				);
				continue;
			}

			// Supported methods.
			$methods = array_diff( $args['methods'], get_class_methods( $implementation ) );

			if ( $methods ) {
				$fields[] = array(
					'label'    => $implementation_name,
					/* translators: %s is a list of functions. */
					'value'    => sprintf( _n( 'Unsupported method: %s.', 'Unsupported methods: %s.', count( $methods ), 'imagify-tools' ), implode( ', ', $methods ) ),
					'is_error' => true,
				);
				continue;
			}

			$fields[] = array(
				'label' => $implementation_name,
				'value' => 'OK',
			);
		}

		$this->add_data_section( __( 'Image Editor Component', 'imagify-tools' ), $fields );
	}

	/**
	 * Add a section related to cURL.
	 *
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	public function add_curl_section() {
		$fields = array(
			array(
				'label'   => __( 'Extension loaded', 'imagify-tools' ),
				'value'   => in_array( 'curl', get_loaded_extensions(), true ),
				'compare' => true,
			),
			array(
				/* translators: %s is a function name. */
				'label'   => sprintf( __( '%s exists', 'imagify-tools' ), '<code>curl_init()</code>' ),
				'value'   => function_exists( 'curl_init' ),
				'compare' => true,
			),
			array(
				/* translators: %s is a function name. */
				'label'   => sprintf( __( '%s exists', 'imagify-tools' ), '<code>curl_exec()</code>' ),
				'value'   => function_exists( 'curl_exec' ),
				'compare' => true,
			),
		);

		if ( function_exists( 'curl_version' ) ) {
			$curl_array = curl_version();
			$fields[]   = array(
				'label'     => '<code>curl_version()</code>',
				'value'     => $curl_array,
				// 7.34.0 is most probably the oldest version supported (so far, 7.29.0 fails and 7.35.0 successes).
				'is_error'  => ! empty( $curl_array['version'] ) ? version_compare( $curl_array['version'], '7.34' ) < 0 : true,
				/* translators: 1 and 2 are cURL versions. */
				'more_info' => sprintf( __( 'Version should be %1$s at least, but we have seen %2$s working.', 'imagify-tools' ), '<code>7.34</code>', '<code>7.29.0</code>' ),
			);

			$curl_features = array(
				/**
				 * CURL features. We probably don't need everything but it helps gather data when needed.
				 *
				 * @see https://curl.haxx.se/libcurl/c/curl_version_info.html
				 */
				'CURL_VERSION_ASYNCHDNS'    => '',
				'CURL_VERSION_BROTLI'       => '',
				'CURL_VERSION_CONV'         => '',
				'CURL_VERSION_GSSNEGOTIATE' => '',
				'CURL_VERSION_HTTP2'        => '',
				'CURL_VERSION_HTTPS_PROXY'  => '',
				'CURL_VERSION_IDN'          => '',
				'CURL_VERSION_IPV6'         => '',
				'CURL_VERSION_LARGEFILE'    => '',
				'CURL_VERSION_LIBZ'         => '',
				'CURL_VERSION_MULTI_SSL'    => '',
				'CURL_VERSION_NTLM'         => '',
				'CURL_VERSION_NTLM_WB'      => '',
				'CURL_VERSION_PSL'          => '',
				'CURL_VERSION_SPNEGO'       => '',
				'CURL_VERSION_SSL'          => '',
				'CURL_VERSION_TLSAUTH_SRP'  => '',
				'CURL_VERSION_UNIX_SOCKETS' => '',
			);

			if ( isset( $curl_array['features'] ) ) {
				foreach ( $curl_features as $feature => $value ) {
					if ( defined( $feature ) ) {
						$curl_features[ $feature ] = $curl_array['features'] & constant( $feature ) ? __( 'Available', 'imagify-tools' ) : __( 'Not available', 'imagify-tools' );
					}
				}
			}

			$fields[] = array(
				'label' => __( 'cURL features', 'imagify-tools' ),
				'value' => $curl_features,
			);
		} else {
			$fields[] = array(
				'label'    => '<code>curl_version()</code>',
				'value'    => __( 'The function does not exist', 'imagify-tools' ),
				'is_error' => true,
			);
		}

		$this->add_data_section( 'cURL', $fields );
	}

	/**
	 * Add a section related to requests.
	 *
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	public function add_requests_section() {
		$requests = array(
			array(
				/* translators: %s is a WP filter name. */
				'label' => sprintf( __( 'Value of the filter %s', 'imagify-tools' ), '<code>https_ssl_verify</code>' ),
				'value' => apply_filters( 'https_ssl_verify', false ) ? 'true' : 'false',
			),
			array(
				/* translators: %s is a WP filter name. */
				'label' => sprintf( __( 'Value of the filter %s', 'imagify-tools' ), '<code>https_local_ssl_verify</code>' ),
				'value' => apply_filters( 'https_local_ssl_verify', false ) ? 'true' : 'false',
			),
		);

		// Try to contact our servers.
		$imagify_urls = array(
			'https://imagify.io',
			'https://app.imagify.io/api/version/',
			'https://s2-amz-par.imagify.io/wpm.png',
		);

		foreach ( $imagify_urls as $imagify_url ) {
			// The 2nd parameter in wp_parse_url() was introduced in WP 4.7, we can't use it.
			$url_domain = wp_parse_url( $imagify_url );
			$url_domain = $url_domain['host'];
			$requests[] = array(
				/* translators: 1 is $_GET or $_POST, 2 is a URL. */
				'label'     => sprintf( __( '%1$s requests to %2$s blocked', 'imagify-tools' ), '<code>$_GET</code>', '<code>' . $url_domain . '</code>' ),
				'value'     => (bool) $this->are_requests_blocked( $imagify_url, 'GET' ),
				'compare'   => false,
				'more_info' => $this->are_requests_blocked( $imagify_url, 'GET' ) . $this->get_clear_request_cache_link( $imagify_url, 'GET' ),
			);
		}

		// Test for local URLs: admin-ajax.php, admin-post.php, and wp-cron.php.
		$local_urls = array(
			admin_url( 'admin-ajax.php?action=' . IMGT_Admin_Post::get_action( 'test' ) ),
			admin_url( 'admin-post.php?action=' . IMGT_Admin_Post::get_action( 'test' ) ),
			site_url( 'wp-cron.php' ),
		);

		foreach ( $local_urls as $local_url ) {
			$test_urls = (array) $local_url;

			if ( $this->are_requests_blocked( $local_url, 'POST' ) ) {
				$test_urls[] = preg_match( '@^https://@', $local_url ) ? set_url_scheme( $local_url, 'http' ) : set_url_scheme( $local_url, 'https' );
			}

			foreach ( $test_urls as $test_url ) {
				$requests[] = array(
					/* translators: 1 is $_GET or $_POST, 2 is a URL. */
					'label'     => sprintf( __( '%1$s requests to %2$s blocked', 'imagify-tools' ), '<code>$_POST</code>', '<code>' . strtok( $test_url, '?' ) . '</code>' ),
					'value'     => (bool) $this->are_requests_blocked( $test_url, 'POST' ),
					'compare'   => false,
					'more_info' => $this->are_requests_blocked( $test_url, 'POST' ) . $this->get_clear_request_cache_link( $test_url, 'POST' ),
				);
			}
		}

		$this->add_data_section( __( 'Requests Tests', 'imagify-tools' ), $requests );
	}

	/**
	 * Add a section related to attachments and files.
	 *
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	public function add_files_section() {
		$attachments = array(
			array(
				'label'     => __( 'Attachments with invalid or missing WP metas', 'imagify-tools' ),
				'value'     => $this->count_medias_with_invalid_wp_metas(),
				'is_error'  => $this->count_medias_with_invalid_wp_metas() > 0,
				'more_info' => $this->get_clear_cache_link( 'imgt_medias_invalid_wp_metas', 'clear_medias_with_invalid_wp_metas_cache' ),
			),
			array(
				'label' => __( 'Number of thumbnail sizes', 'imagify-tools' ),
				'value' => count( IMGT_Tools::get_thumbnail_sizes() ),
			),
		);

		if ( class_exists( 'Imagify_Folders_DB', true ) ) {
			$attachments[] = array(
				'label'   => __( 'Folders table is ready', 'imagify-tools' ),
				'value'   => Imagify_Folders_DB::get_instance()->can_operate(),
				'compare' => true,
			);
		}

		if ( class_exists( 'Imagify_Files_DB', true ) ) {
			$attachments[] = array(
				'label'   => __( 'Files table is ready', 'imagify-tools' ),
				'value'   => Imagify_Files_DB::get_instance()->can_operate(),
				'compare' => true,
			);
		}

		if ( $this->count_orphan_files() !== false ) {
			$attachments[] = array(
				'label'     => __( 'Orphan files count', 'imagify-tools' ),
				'value'     => $this->count_orphan_files(),
				'is_error'  => $this->count_orphan_files() > 0,
				'more_info' => $this->get_clear_cache_link( 'imgt_orphan_files', 'clear_orphan_files_cache' ),
			);
		}

		if ( function_exists( 'get_imagify_bulk_buffer_size' ) && defined( 'IMAGIFY_VERSION' ) && version_compare( IMAGIFY_VERSION, '1.9' ) < 0 ) {
			// The function is deprecated in Imagify 1.9.
			$sizes = array(
				'wp'   => get_imagify_bulk_buffer_size(),
				'File' => get_imagify_bulk_buffer_size( 1 ),
			);
		} else {
			$sizes = array(
				'wp'   => 4,
				'File' => 4,
			);
		}

		if ( function_exists( 'wp_create_image_subsizes' ) ) {
			/** This filter is documented in wp-admin/includes/image.php. */
			$threshold = (int) apply_filters( 'big_image_size_threshold', 2560, array( 0, 0 ), '', 0 );

			$attachments[] = array(
				'label' => __( 'Resizing threshold', 'imagify-tools' ),
				'value' => $threshold,
			);
		}

		/** This filter is documented in /imagify/inc/functions/i18n.php. */
		$sizes['wp'] = apply_filters( 'imagify_bulk_buffer_size', $sizes['wp'] );

		/** This filter is documented in /imagify/inc/functions/i18n.php. */
		$sizes = apply_filters( 'imagify_bulk_buffer_sizes', $sizes );

		$attachments[] = array(
			'label' => __( 'Number of parallel optimizations in bulk optimizer', 'imagify-tools' ),
			'value' => $sizes,
		);

		$this->add_data_section( __( 'Media', 'imagify-tools' ), $attachments );
	}

	/**
	 * Add a "various" section.
	 *
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	public function add_various_section() {
		global $wpdb, $wp_object_cache, $wp_version;

		/**
		 * Table NGG.
		 */
		$ngg_table_engine_fix_link = '';
		$ngg_table_engine_compare  = 'InnoDB';
		$ngg_table_engine          = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$wpdb->prefix . 'ngg_imagify_data'
			)
		);

		if ( is_null( $ngg_table_engine ) ) {
			$ngg_table_engine_compare = __( 'The table doesn\'t exist.', 'imagify-tools' );
			$ngg_table_engine         = __( 'The table doesn\'t exist.', 'imagify-tools' );
		} elseif ( $ngg_table_engine !== $ngg_table_engine_compare ) {
			$ngg_table_engine_fix_link = IMGT_Admin_Post::get_action( 'fix_ngg_table_engine' );
			$ngg_table_engine_fix_link = wp_nonce_url( admin_url( 'admin-post.php?action=' . $ngg_table_engine_fix_link ), $ngg_table_engine_fix_link );
			$ngg_table_engine_fix_link = '<br/> <a class="imgt-button imgt-button-ternary imgt-button-mini" href="' . esc_url( $ngg_table_engine_fix_link ) . '">' . __( 'Fix it', 'imagify-tools' ) . '</a>';
		}

		/**
		 * $_SERVER.
		 */
		$this->add_data_section(
			__( 'Various Tests and Values', 'imagify-tools' ),
			array(
				array(
					'label' => __( 'Hosting Company', 'imagify-tools' ),
					'value' => $this->get_hosting_company(),
				),
				array(
					'label' => __( 'PHP version', 'imagify-tools' ),
					'value' => PHP_VERSION,
				),
				array(
					'label'    => __( 'WP version', 'imagify-tools' ),
					'value'    => $wp_version,
					'is_error' => version_compare( $wp_version, '4.0' ) < 0,
				),
				array(
					'label'    => __( 'Max execution time', 'imagify-tools' ),
					'value'    => @ini_get( 'max_execution_time' ),
					'is_error' => @ini_get( 'max_execution_time' ) < 30,
				),
				array(
					/* translators: 1 and 2 are constant names. */
					'label' => sprintf( __( 'Memory Limit (%1$s value / %2$s value / real value)', 'imagify-tools' ), '<code>WP_MEMORY_LIMIT</code>', '<code>WP_MAX_MEMORY_LIMIT</code>' ),
					'value' => WP_MEMORY_LIMIT . ' / ' . WP_MAX_MEMORY_LIMIT . ' / ' . @ini_get( 'memory_limit' ),
				),
				array(
					'label'     => __( 'Uses external object cache', 'imagify-tools' ),
					'value'     => wp_using_ext_object_cache() ? wp_using_ext_object_cache() : false,
					'more_info' => wp_using_ext_object_cache() ? get_class( $wp_object_cache ) : '',
				),
				array(
					'label'     => __( 'NGG table engine', 'imagify-tools' ),
					'value'     => $ngg_table_engine,
					'compare'   => $ngg_table_engine_compare,
					/* translators: %s is a value. */
					'more_info' => sprintf( __( 'If exists, should be %s.', 'imagify-tools' ), '<code>InnoDB</code>' ) . $ngg_table_engine_fix_link,
				),
				array(
					'label' => __( 'Is multisite', 'imagify-tools' ),
					'value' => is_multisite(),
				),
				array(
					'label'     => __( 'Is SSL', 'imagify-tools' ),
					'value'     => is_ssl(),
					'compare'   => $this->is_ssl(),
					/* translators: %s is a function name. */
					'more_info' => is_ssl() !== $this->is_ssl() ? sprintf( __( 'The function %s returns a wrong result, it could be a problem related with the way SSL is implemented.', 'imagify-tools' ), '<code>is_ssl()</code>' ) : '',
				),
				array(
					'label' => __( 'Your user ID', 'imagify-tools' ),
					'value' => get_current_user_id(),
				),
				array(
					'label' => __( 'Your IP address', 'imagify-tools' ),
					'value' => imagify_tools_get_ip(),
				),
				array(
					'label' => __( 'Settings', 'imagify-tools' ),
					'value' => get_site_option( 'imagify_settings' ),
				),
				array(
					'label'     => __( 'Imagify User', 'imagify-tools' ),
					'value'     => $this->get_imagify_user(),
					'more_info' => $this->get_clear_cache_link( 'imgt_user', 'clear_imagify_user_cache' ),
				),
				array(
					'label' => '$_SERVER',
					'value' => $this->sanitize( $_SERVER ),
				),
			)
		);
	}


	/** Data related ============================================================================ */

	/**
	 * Get the data.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return array.
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Add a section to the data.
	 *
	 * @since  1.0.3
	 * @author Grégory Viguier
	 *
	 * @param string $section_title  The section title.
	 * @param array  $section_fields The rows displayed in the section.
	 */
	public function add_data_section( $section_title, $section_fields ) {
		if ( $section_fields ) {
			$this->data[ $section_title ] = $section_fields;
		}
	}

	/**
	 * Sanitize some data.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  mixed $data The data to sanitize.
	 * @return mixed
	 */
	protected function sanitize( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'sanitize' ), $data );
		}

		if ( is_object( $data ) ) {
			foreach ( $data as $k => $v ) {
				$data->$k = $this->sanitize( $v );
			}
			return $data;
		}

		$data = wp_unslash( $data );

		if ( is_numeric( $data ) ) {
			return $data + 0;
		}

		if ( is_string( $data ) ) {
			return sanitize_text_field( $data );
		}

		return $data;
	}


	/** Request blockage related ================================================================ */

	/**
	 * Tell if requests to a given URL are blocked.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $url    An URL.
	 * @param  string $method The http method to use.
	 * @return string         An empty string if not blocked. A short information text if blocked.
	 */
	protected function are_requests_blocked( $url, $method = 'GET' ) {
		static $infos = array();
		static $hosts = array();

		$method         = strtoupper( $method );
		$transient_name = self::REQUEST_CACHE_PREFIX . substr( md5( "$url|$method" ), 0, 10 );

		if ( isset( $infos[ $transient_name ] ) ) {
			return $infos[ $transient_name ];
		}

		$infos[ $transient_name ] = imagify_tools_get_site_transient( $transient_name );

		if ( false !== $infos[ $transient_name ] ) {
			$infos[ $transient_name ] = 'OK' === $infos[ $transient_name ] ? '' : $infos[ $transient_name ];
			return $infos[ $transient_name ];
		}

		$infos[ $transient_name ] = array();

		// Blocked by constant or filter?
		$is_blocked = _wp_http_get_object()->block_request( $url );

		if ( $is_blocked ) {
			$infos[ $transient_name ][] = __( 'Blocked internally.', 'imagify-tools' );
		}

		if ( ! $hosts ) {
			$hosts    = array(
				wp_parse_url( admin_url() ),
				wp_parse_url( site_url() ),
			);
			$hosts[0] = $hosts[0]['host'];
			$hosts[1] = $hosts[1]['host'];
			$hosts    = array_flip( $hosts );
		}

		$url_host = wp_parse_url( $url );
		$url_host = $url_host['host'];

		if ( isset( $hosts[ $url_host ] ) ) {
			// The request is "local".
			$sslverify = apply_filters( 'https_local_ssl_verify', false );
		} else {
			$sslverify = apply_filters( 'https_ssl_verify', false );
		}

		// Blocked by .htaccess, firewall, or host?
		try {
			$is_blocked = wp_remote_request(
				$url,
				array(
					'method'     => $method,
					'user-agent' => 'Imagify Tools',
					'cookies'    => $_COOKIE, // WPCS: input var okay.
					'sslverify'  => $sslverify,
					'timeout'    => 10,
				)
			);
		} catch ( Exception $e ) {
			$is_blocked = new WP_Error( 'curl', $e->getMessage() );
		}

		if ( ! is_wp_error( $is_blocked ) ) {
			$http_code  = wp_remote_retrieve_response_code( $is_blocked );
			$is_blocked = 200 !== $http_code;
			$http_code .= ' ' . get_status_header_desc( $http_code );
		}

		if ( $is_blocked ) {
			if ( is_wp_error( $is_blocked ) ) {
				/* translators: 1 is an error code. */
				$infos[ $transient_name ][] = sprintf( __( 'Request returned an error: %s', 'imagify-tools' ), '<pre>' . $is_blocked->get_error_message() . '</pre>' );
			} elseif ( preg_match( '@^https?://([^/]+\.)?imagify\.io(/|\?|$)@', $url ) ) {
				/* translators: 1 is a file name, 2 is a HTTP request code. */
				$infos[ $transient_name ][] = sprintf( __( 'Blocked by %1$s file, a firewall, the host, or it could be down (http code is %2$s).', 'imagify-tools' ), '<code>.htaccess</code>', "<code>$http_code</code>" );
			} else {
				/* translators: 1 is a file name, 2 is a HTTP request code. */
				$infos[ $transient_name ][] = sprintf( __( 'Blocked by %1$s file, a firewall, or the host (http code is %2$s).', 'imagify-tools' ), '<code>.htaccess</code>', "<code>$http_code</code>" );
			}
		}

		$infos[ $transient_name ] = implode( ' ', $infos[ $transient_name ] );
		$infos[ $transient_name ] = '' === $infos[ $transient_name ] ? 'OK' : $infos[ $transient_name ];

		imagify_tools_set_site_transient( $transient_name, $infos[ $transient_name ], self::CACHE_DURATION * MINUTE_IN_SECONDS );

		$infos[ $transient_name ] = 'OK' === $infos[ $transient_name ] ? '' : $infos[ $transient_name ];
		return $infos[ $transient_name ];
	}

	/**
	 * Get the link to clear the request cache (delete the transient).
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $url    An URL.
	 * @param  string $method The http method to use.
	 * @return string
	 */
	protected function get_clear_request_cache_link( $url, $method = 'GET' ) {
		$line_break     = $this->are_requests_blocked( $url, $method ) ? '<br/>' : '';
		$method         = strtoupper( $method );
		$transient_name = substr( md5( "$url|$method" ), 0, 10 );

		return $line_break . $this->get_clear_cache_link( self::REQUEST_CACHE_PREFIX . $transient_name, 'clear_request_cache', array( 'cache' => $transient_name ) );
	}


	/** Specific tools ========================================================================== */

	/**
	 * Get the image editor.
	 *
	 * @since  1.0.3
	 * @author Grégory Viguier
	 *
	 * @return string|bool The class name. False on error.
	 */
	public function get_image_editor_class() {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

		if ( defined( 'IMAGIFY_PATH' ) ) {
			$image_path = IMAGIFY_PATH . 'assets/images/imagify-logo.png';
		} else {
			$image_path = admin_url( 'images/arrows.png' );
			$image_path = str_replace( site_url( '/' ), ABSPATH, $image_path );
		}

		$args = array(
			'path'       => $image_path,
			'mime_types' => IMGT_Tools::get_mime_types( 'image' ),
			'methods'    => $this->get_image_editor_methods(),
		);

		/** This filter is documented in /wp-includes/media.php. */
		$implementations = apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

		foreach ( $implementations as $implementation ) {
			if ( ! call_user_func( array( $implementation, 'test' ), $args ) ) {
				continue;
			}

			foreach ( $args['mime_types'] as $mime_type ) {
				if ( ! call_user_func( array( $implementation, 'supports_mime_type' ), $mime_type ) ) {
					continue 2;
				}
			}

			if ( array_diff( $args['methods'], get_class_methods( $implementation ) ) ) {
				continue;
			}

			return $implementation;
		}

		return false;
	}

	/**
	 * Get the image editor methods we will use.
	 *
	 * @since  1.0.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_image_editor_methods() {
		static $methods;

		if ( isset( $methods ) ) {
			return $methods;
		}

		$methods = array(
			'resize',
			'multi_resize',
			'generate_filename',
			'save',
		);

		if ( is_callable( 'exif_read_data' ) ) {
			$methods[] = 'rotate';
		}

		return $methods;
	}

	/**
	 * Try to get the hosting company.
	 *
	 * @since  1.0.5
	 * @access public
	 * @author Grégory Viguier
	 */
	public function get_hosting_company() {
		switch ( true ) {
			case defined( 'IS_PRESSABLE' ):
				return 'Pressable';

			case defined( 'FLYWHEEL_CONFIG_DIR' ):
				return 'FlyWheel';

			case class_exists( '\\WPaaS\\Plugin' ):
				return 'GoDaddy';

			case defined( 'DB_HOST' ) && strpos( DB_HOST, '.infomaniak.com' ) !== false:
				return 'Infomaniak';

			case isset( $_SERVER['KINSTA_CACHE_ZONE'] ):
				return 'Kinsta';

			case defined( 'O2SWITCH_VARNISH_PURGE_KEY' ):
				return 'o2switch';

			case isset( $_SERVER['ONECOM_DOCUMENT_ROOT'] ):
				return 'One.com';

			case class_exists( 'PagelyCachePurge' ):
				return 'Pagely';

			case defined( 'WP_NINUKIS_WP_NAME' ):
				return 'Pressidium';

			case class_exists( '\\Savvii\\Options' ):
				return 'Savvii';

			case class_exists( 'SG_CachePress_Environment' ):
				return 'SiteGround';

			case class_exists( 'WpeCommon' ):
				return 'WP Engine';

			case defined( 'DB_HOST' ) && strpos( DB_HOST, '.wpserveur.net' ) !== false:
				return 'WPServeur';

			case defined( 'WPCOMSH_VERSION' ):
				return 'wordpress.com';

			case ! empty( $_SERVER['SERVER_ADDR'] ) && ( '127.0.0.1' === $_SERVER['SERVER_ADDR'] || '::1' === $_SERVER['SERVER_ADDR'] ):
				return 'localhost';

			default:
				return 'Unknown';
		}
	}

	/**
	 * Get the number of attachment where the post meta '_wp_attached_file' can't be worked with.
	 *
	 * @since  1.0.2
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	protected function count_medias_with_invalid_wp_metas() {
		global $wpdb;
		static $transient_value;

		if ( isset( $transient_value ) ) {
			return $transient_value;
		}

		$transient_name  = 'imgt_medias_invalid_wp_metas';
		$transient_value = imagify_tools_get_site_transient( $transient_name );

		if ( false !== $transient_value ) {
			return (int) $transient_value;
		}

		if ( class_exists( 'Imagify_DB', true ) && method_exists( 'Imagify_DB', 'get_required_wp_metadata_where_clause' ) ) {
			$mime_types  = Imagify_DB::get_mime_types();
			$statuses    = Imagify_DB::get_post_statuses();
			$nodata_join = Imagify_DB::get_required_wp_metadata_join_clause( 'p.ID', false, false );

			if ( version_compare( IMAGIFY_VERSION, '1.7.1.2' ) < 0 ) {
				$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause( array(), false, false );
			} else {
				$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause(
					array(
						'matching' => false,
						'test'     => false,
					)
				);
			}
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$transient_value = $wpdb->get_var(
				"
				SELECT COUNT( p.ID )
				FROM $wpdb->posts AS p
					$nodata_join
				WHERE p.post_mime_type IN ( $mime_types )
					AND p.post_type = 'attachment'
					AND p.post_status IN ( $statuses )
					$nodata_where"
			);
		} else {
			$mime_types = IMGT_Tools::get_mime_types();
			$extensions = implode( '|', array_keys( $mime_types ) );
			$extensions = explode( '|', $extensions );
			$extensions = "OR ( LOWER( imrwpmt1.meta_value ) NOT LIKE '%." . implode( "' AND LOWER( imrwpmt1.meta_value ) NOT LIKE '%.", $extensions ) . "' )";
			$mime_types = esc_sql( $mime_types );
			$mime_types = "'" . implode( "','", $mime_types ) . "'";
			$statuses   = esc_sql( IMGT_Tools::get_post_statuses() );
			$statuses   = "'" . implode( "','", $statuses ) . "'";

			$transient_value = $wpdb->get_var(
				"
				SELECT COUNT( p.ID )
				FROM $wpdb->posts AS p
				LEFT JOIN $wpdb->postmeta AS imrwpmt1
					ON ( p.ID = imrwpmt1.post_id AND imrwpmt1.meta_key = '_wp_attached_file' )
				LEFT JOIN $wpdb->postmeta AS imrwpmt2
					ON ( p.ID = imrwpmt2.post_id AND imrwpmt2.meta_key = '_wp_attachment_metadata' )
				WHERE p.post_mime_type IN ( $mime_types )
					AND p.post_type = 'attachment'
					AND p.post_status IN ( $statuses )
					AND ( imrwpmt2.meta_value IS NULL OR imrwpmt1.meta_value IS NULL OR imrwpmt1.meta_value LIKE '%://%' OR imrwpmt1.meta_value LIKE '_:\\\\\%' $extensions )"
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		imagify_tools_set_site_transient( $transient_name, $transient_value, self::CACHE_DURATION * MINUTE_IN_SECONDS );

		return $transient_value;
	}

	/**
	 * Get the number of "custom files" that have no folder.
	 *
	 * @since  1.0.2
	 * @author Grégory Viguier
	 *
	 * @return int|bool The number of files. False if the tables are not ready.
	 */
	protected function count_orphan_files() {
		global $wpdb;
		static $transient_value;

		if ( isset( $transient_value ) ) {
			return $transient_value;
		}

		$folders_can_operate = class_exists( 'Imagify_Folders_DB', true ) && Imagify_Folders_DB::get_instance()->can_operate();
		$files_can_operate   = class_exists( 'Imagify_Files_DB', true ) && Imagify_Files_DB::get_instance()->can_operate();

		if ( ! $folders_can_operate || ! $files_can_operate ) {
			$transient_value = false;
			return $transient_value;
		}

		$transient_name  = 'imgt_orphan_files';
		$transient_value = imagify_tools_get_site_transient( $transient_name );

		if ( false !== $transient_value ) {
			return (int) $transient_value;
		}

		$folders_db      = Imagify_Folders_DB::get_instance();
		$folders_table   = $folders_db->get_table_name();
		$folders_key     = $folders_db->get_primary_key();
		$folders_key_esc = esc_sql( $folders_key );

		$files_db      = Imagify_Files_DB::get_instance();
		$files_table   = $files_db->get_table_name();
		$files_key_esc = esc_sql( $files_db->get_primary_key() );
		$folder_ids    = $wpdb->get_col( "SELECT $folders_key_esc FROM $folders_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $folder_ids ) {
			$folder_ids = $folders_db->cast_col( $folder_ids, $folders_key );
			$folder_ids = Imagify_DB::prepare_values_list( $folder_ids );

			$transient_value = (int) $wpdb->get_var( "SELECT COUNT( $files_key_esc ) FROM $files_table WHERE folder_id NOT IN ( $folder_ids )" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$transient_value = (int) $wpdb->get_var( "SELECT COUNT( $files_key_esc ) FROM $files_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		imagify_tools_set_site_transient( $transient_name, $transient_value, self::CACHE_DURATION * MINUTE_IN_SECONDS );

		return $transient_value;
	}

	/**
	 * Get (and cache) the Imagify user.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return object|string
	 */
	protected function get_imagify_user() {
		static $imagify_user;

		if ( ! function_exists( 'get_imagify_user' ) ) {
			return __( 'Needs Imagify to be installed', 'imagify-tools' );
		}

		if ( isset( $imagify_user ) ) {
			return $imagify_user;
		}

		$imagify_user = imagify_tools_get_site_transient( 'imgt_user' );

		if ( ! $imagify_user ) {
			$imagify_user = get_imagify_user();
			imagify_tools_set_site_transient( 'imgt_user', $imagify_user, self::CACHE_DURATION * MINUTE_IN_SECONDS );
		}

		return $imagify_user;
	}


	/** Cache related =========================================================================== */

	/**
	 * Get the link to clear a cache (delete the transient).
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $transient_name Name of the transient that stores the data.
	 * @param  string $clear_action   Admin post action.
	 * @param  array  $args           Parameters to add to the link URL.
	 * @return string
	 */
	protected function get_clear_cache_link( $transient_name, $clear_action, $args = array() ) {
		$link = ' <a class="imgt-button imgt-button-ternary imgt-button-mini" href="' . esc_url( $this->get_clear_cache_url( $clear_action, $args ) ) . '">' . __( 'Clear cache', 'imagify-tools' ) . '</a>';

		$transient_timeout = IMGT_Tools::get_transient_timeout( $transient_name );
		$current_time      = time();

		if ( ! $transient_timeout || $transient_timeout < $current_time ) {
			$time_diff = self::CACHE_DURATION;
		} else {
			$time_diff = $transient_timeout - $current_time;
			$time_diff = ceil( $time_diff / MINUTE_IN_SECONDS );
		}

		/* translators: %d is a number of minutes. */
		return $link .= ' <span class="imgt-small-info">(' . sprintf( _n( 'cache cleared in less than %d minute', 'cache cleared in less than %d minutes', $time_diff, 'imagify-tools' ), $time_diff ) . ')</span>';
	}

	/**
	 * Get the URL to clear a cache (delete the transient).
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  string $action Admin post action.
	 * @param  array  $args   Parameters to add to the link URL.
	 * @return string
	 */
	protected function get_clear_cache_url( $action, $args = array() ) {
		$action = IMGT_Admin_Post::get_action( $action );
		$url    = wp_nonce_url( admin_url( 'admin-post.php?action=' . $action ), $action );

		if ( empty( $args['_wp_http_referer'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$args['_wp_http_referer'] = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		return $args ? add_query_arg( $args, $url ) : $url;
	}


	/** Generic tools =========================================================================== */

	/**
	 * Tell if the site uses SSL.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	protected function is_ssl() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return true;
		}
		if ( preg_match( '@^https://@', admin_url( 'admin-ajax.php' ) ) ) {
			return true;
		}
		return is_ssl();
	}
}
