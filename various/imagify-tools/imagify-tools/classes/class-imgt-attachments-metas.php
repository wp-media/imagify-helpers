<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that prints attachments meta values.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 */
class IMGT_Attachments_Metas {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.2';

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	const METABOX_ID = 'imgt-attachment-metas';

	/**
	 * The single instance of the class.
	 *
	 * @access  protected
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
		if ( current_user_can( imagify_tools_get_capacity() ) ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), -10 );
		}
	}

	/**
	 * Add some meta boxes in attachment edition page.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function add_meta_boxes() {
		global $post;

		if ( ! imagify_tools_is_attachment_mime_type_supported( $post->ID ) ) {
			return;
		}

		// Add some CSS only on pages that will display a meta box.
		add_action( 'admin_print_styles-post.php', array( $this, 'print_styles' ) );

		$metas = get_post_meta( $post->ID );

		if ( ! $metas ) {
			// The attachment has no metas, that's a big problem.
			add_meta_box(
				self::METABOX_ID,
				_x( 'Metas', 'attachment meta data', 'imagify-tools' ),
				array( $this, 'print_meta_box_no_content' ),
				'attachment',
				'normal',
				'high'
			);

			add_filter( 'postbox_classes_attachment_' . self::METABOX_ID, array( $this, 'add_meta_box_class' ) );
			return;
		}

		foreach ( $metas as $meta_name => $values ) {
			$metas[ $meta_name ] = array_map( 'maybe_unserialize', $values );
		}

		// File infos metabox.
		add_meta_box(
			self::METABOX_ID . '-file-infos',
			__( 'File infos', 'imagify-tools' ),
			array( $this, 'print_meta_box_file_infos' ),
			'attachment',
			'normal',
			'high',
			array(
				'metas' => $metas,
			)
		);

		add_filter( 'postbox_classes_attachment_' . self::METABOX_ID . '-file-infos', array( $this, 'add_meta_box_class' ) );

		// Group metas in up to 4 meta boxes.
		$meta_groups = array(
			'wp'      => array(
				'title'      => _x( 'Mandatory WP metas', 'attachment meta data', 'imagify-tools' ),
				'skip_empty' => false,
				'required'   => array(
					'_wp_attached_file'       => 1,
					'_wp_attachment_metadata' => 1,
				),
			),
			'imagify' => array(
				'title'    => _x( 'Imagify metas', 'attachment meta data', 'imagify-tools' ),
				'required' => array(
					'_imagify_status'             => 1,
					'_imagify_optimization_level' => 1,
					'_imagify_data'               => 1,
				),
			),
			's3'      => array(
				'title'    => _x( 'Amazon S3 metas', 'attachment meta data', 'imagify-tools' ),
				'required' => array(
					'wpos3_filesize_total' => 1,
					'amazonS3_info'        => 1,
				),
			),
			'other'   => array(
				'title' => _x( 'Other metas', 'attachment meta data', 'imagify-tools' ),
			),
		);

		if ( ! isset( $metas['_wp_attachment_metadata'][0]['filesize'] ) ) {
			// The files are not removed from the server, so the meta should not be set.
			unset( $meta_groups['s3']['required']['wpos3_filesize_total'] );
		}

		// Add a meta box for each group.
		foreach ( $meta_groups as $box_id => $box_args ) {
			$box_args = array_merge(
				array(
					'title'      => _x( 'Metas', 'attachment meta data', 'imagify-tools' ),
					'skip_empty' => true,
					'required'   => array(),
				),
				$box_args
			);

			if ( $box_args['required'] ) {
				$tmp_metas = array_intersect_key( $metas, $box_args['required'] );
				$metas     = array_diff_key( $metas, $box_args['required'] );
			} else {
				$tmp_metas = $metas;
			}

			if ( ! $tmp_metas && $box_args['skip_empty'] ) {
				continue;
			}

			add_meta_box(
				self::METABOX_ID . '-' . $box_id,
				$box_args['title'],
				array( $this, 'print_meta_box_content' ),
				'attachment',
				'normal',
				'high',
				array(
					'metas'    => $tmp_metas,
					'required' => $box_args['required'],
				)
			);

			// Add a common HTML class to our meta boxes.
			add_filter( 'postbox_classes_attachment_' . self::METABOX_ID . '-' . $box_id, array( $this, 'add_meta_box_class' ) );
		}
	}

	/**
	 * Print the meta box content saying there are no metas.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function print_meta_box_no_content() {
		echo '<div class="row-error">' . esc_html_x( 'None!', 'attachment meta data', 'imagify-tools' ) . '</div>';
	}

	/**
	 * Print the meta box content for the file infos.
	 *
	 * @since  1.0.2
	 * @author Grégory Viguier
	 *
	 * @param object $post WP_Post object of the current Attachment post.
	 * @param array  $data An array of data related to the meta box.
	 */
	public function print_meta_box_file_infos( $post, $data ) {
		$path = get_attached_file( $post->ID, true );

		if ( ! $path ) {
			echo '<div class="row-error">' . esc_html__( 'Cannot retrieve file path.', 'imagify-tools' ) . '</div>';
			return;
		}

		$path     = wp_normalize_path( $path );
		$exists   = file_exists( $path );
		$is_image = (object) wp_check_filetype( $path, IMGT_Tools::get_mime_types( 'image' ) );
		$is_image = ! empty( $is_image->type );

		if ( $exists ) {
			/**
			 * Writable?
			 */
			$is_writable = wp_is_writable( $path );

			/**
			 * File perms, ownership, group.
			 */
			$files_chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : fileperms( ABSPATH . 'index.php' ) & 0777 | 0644;
			$file_chmod  = fileperms( $path ) & 0777 | 0644;
			$file_stats  = @stat( $path );
			$file_owner  = '';
			$files_owner = '';

			if ( $file_stats ) {
				$file_owner = $file_stats['uid'];

				if ( function_exists( 'posix_getpwuid' ) ) {
					$file_owner = posix_getpwuid( $file_owner );
					$file_owner = $file_owner['name'] . ' (' . $file_owner['uid'] . ')';
				}

				// `index.php`
				$files_stats = @stat( ABSPATH . 'index.php' );

				if ( $files_stats ) {
					$files_owner = $files_stats['uid'];

					if ( function_exists( 'posix_getpwuid' ) ) {
						$files_owner = posix_getpwuid( $files_owner );
						$files_owner = $files_owner['name'] . ' (' . $files_owner['uid'] . ')';
					}
				}
			}

			/**
			 * Weight.
			 */
			if ( $file_stats ) {
				$bytes = $file_stats['size'] ? @size_format( $file_stats['size'], 2 ) : '0';
				$bytes = str_replace( ' ', ' ', $bytes );
			} else {
				$bytes = '0';
			}

			/**
			 * Dimensions.
			 */
			if ( $is_image ) {
				$imgsize = @getimagesize( $path );
				$width   = $imgsize ? $imgsize[0] : 0;
				$height  = $imgsize ? $imgsize[1] : 0;
			}
		}

		/**
		 * Thumbnails.
		 */
		$sizes       = ! empty( $data['args']['metas']['_wp_attachment_metadata'][0]['sizes'] ) && is_array( $data['args']['metas']['_wp_attachment_metadata'][0]['sizes'] ) ? $data['args']['metas']['_wp_attachment_metadata'][0]['sizes'] : array();
		$thumb_error = true;

		if ( $is_image ) {
			$thumb_error           = false;
			$original_dirname      = trailingslashit( dirname( $path ) );
			$tmp_sizes             = array();
			$imagify_sizes         = ! empty( $data['args']['metas']['_imagify_data'][0]['sizes'] ) && is_array( $data['args']['metas']['_imagify_data'][0]['sizes'] ) ? $data['args']['metas']['_imagify_data'][0]['sizes'] : array();
			$disallowed_sizes      = get_site_option( 'imagify_settings' );
			$disallowed_sizes      = ! empty( $disallowed_sizes['disallowed-sizes'] ) && is_array( $disallowed_sizes['disallowed-sizes'] ) ? $disallowed_sizes['disallowed-sizes'] : array();
			$is_active_for_network = imagify_tools_imagify_is_active_for_network();

			if ( ! empty( $imagify_sizes['full@imagify-webp'] ) ) {
				if ( file_exists( $path . '.webp' ) ) {
					/* translators: 1 and 2 are whatever you like them to be. Even more. */
					$tmp_sizes['full@imagify-webp'] = sprintf( __( '%1$s: %2$s', 'imagify-tools' ), _x( 'Exists', 'File', 'imagify-tools' ), 'full' );
				} else {
					$thumb_error = true;
					/* translators: 1 and 2 are whatever you like them to be. Even more. */
					$tmp_sizes['full@imagify-webp'] = '☠️ ' . sprintf( __( '%1$s: %2$s', 'imagify-tools' ), _x( 'Does not exist', 'File', 'imagify-tools' ), 'full' );
				}
			}

			if ( $sizes ) {
				foreach ( $sizes as $size_name => $size_data ) {
					if ( file_exists( $original_dirname . $size_data['file'] ) ) {
						/* translators: 1 and 2 are whatever you like them to be. Even more. */
						$sizes[ $size_name ] = sprintf( __( '%1$s: %2$s', 'imagify-tools' ), _x( 'Exists', 'File', 'imagify-tools' ), $size_name );
					} else {
						$thumb_error = true;
						/* translators: 1 and 2 are whatever you like them to be. Even more. */
						$sizes[ $size_name ] = '☠️ ' . sprintf( __( '%1$s: %2$s', 'imagify-tools' ), _x( 'Does not exist', 'File', 'imagify-tools' ), $size_name );
					}

					// Webp.
					$webp_size_name = $size_name . '@imagify-webp';

					if ( empty( $imagify_sizes[ $webp_size_name ] ) ) {
						// Not created.
						continue;
					}

					if ( ! $is_active_for_network && isset( $disallowed_sizes[ $size_name ] ) ) {
						// Size is disabled.
						/* translators: 1 and 2 are whatever you like them to be. Even more. */
						$tmp_sizes[ $webp_size_name ] = sprintf( __( '%1$s: %2$s', 'imagify-tools' ), _x( 'Disabled', 'Thumbnail size', 'imagify-tools' ), $size_name );
					} elseif ( file_exists( $original_dirname . $size_data['file'] . '.webp' ) ) {
						/* translators: 1 and 2 are whatever you like them to be. Even more. */
						$tmp_sizes[ $webp_size_name ] = sprintf( __( '%1$s: %2$s', 'imagify-tools' ), _x( 'Exists', 'File', 'imagify-tools' ), $size_name );
					} else {
						$thumb_error = true;
						/* translators: 1 and 2 are whatever you like them to be. Even more. */
						$tmp_sizes[ $webp_size_name ] = '☠️ ' . sprintf( __( '%1$s: %2$s', 'imagify-tools' ), _x( 'Does not exist', 'File', 'imagify-tools' ), $size_name );
					}
				}
			}

			if ( $tmp_sizes ) {
				/* translators: 1 and 2 are whatever you like them to be. Even more. */
				$sizes = array_merge( $sizes, array( '→ ' . sprintf( __( '%1$s: %2$s', 'imagify-tools' ), 'Webp', '' ) ), $tmp_sizes );
			}
		} else {
			$sizes = array();
		}

		/**
		 * Backup file.
		 */
		$upload_basedir = wp_upload_dir();
		$upload_basedir = trailingslashit( wp_normalize_path( $upload_basedir['basedir'] ) );

		$backup_dir = $upload_basedir . 'backup/';
		/** This filter is documented in Imagify. */
		$backup_dir = apply_filters( 'imagify_backup_directory', $backup_dir );
		$backup_dir = trailingslashit( wp_normalize_path( $backup_dir ) );

		$backup_path = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $post->ID ) : $path;
		$backup_path = $backup_path ? $backup_path : $path;
		$backup_path = $upload_basedir ? str_replace( $upload_basedir, $backup_dir, $backup_path ) : '';
		$has_backup  = file_exists( $backup_path );

		/**
		 * Print out.
		 */
		echo '<table><tbody>';

		echo '<tr>';
			echo '<th>' . esc_html__( 'Path', 'imagify-tools' ) . '</th><td>' . esc_html( $path ) . '</td>';
		echo '</tr>';

		echo '<tr' . ( $exists ? '' : ' class="row-error"' ) . '>';
			echo '<th>' . esc_html__( 'File exists', 'imagify-tools' ) . '</th><td>' . esc_html( $exists ? __( 'Yes', 'imagify-tools' ) : __( 'No', 'imagify-tools' ) ) . '</td>';
		echo '</tr>';

		if ( $exists ) {
			echo '<tr' . ( $is_writable ? '' : ' class="row-error"' ) . '>';
				echo '<th>' . esc_html__( 'File is writable', 'imagify-tools' ) . '</th><td>' . esc_html( $is_writable ? __( 'Yes', 'imagify-tools' ) : __( 'No', 'imagify-tools' ) ) . '</td>';
			echo '</tr>';

			echo '<tr' . ( $file_chmod && $file_chmod === $files_chmod ? '' : ' class="row-error"' ) . '>';
				echo '<th>' . esc_html__( 'File permissions', 'imagify-tools' ) . '</th>';
				echo '<td>';
					echo esc_html( IMGT_Tools::to_octal( $file_chmod ) . ' (' . $file_chmod . ').' );
					echo ' <code>index.php</code>: ' . esc_html( IMGT_Tools::to_octal( $files_chmod ) . ' (' . $files_chmod . ').' );
				echo '</td>';
			echo '</tr>';

			echo '<tr' . ( $file_owner && $file_owner === $files_owner ? '' : ' class="row-error"' ) . '>';
				echo '<th>' . esc_html__( 'File owner', 'imagify-tools' ) . '</th>';
				echo '<td>' . esc_html( $file_owner ) . '. <code>index.php</code>: ' . esc_html( $files_owner ) . '.</td>';
			echo '</tr>';

			echo '<tr' . ( $bytes ? '' : ' class="row-error"' ) . '>';
				echo '<th>' . esc_html__( 'Weight', 'imagify-tools' ) . '</th><td>' . esc_html( $bytes ) . '</td>';
			echo '</tr>';

			if ( $is_image ) {
				echo '<tr' . ( $imgsize ? '' : ' class="row-error"' ) . '>';
					echo '<th>' . esc_html__( 'Dimensions', 'imagify-tools' ) . '</th><td>' . (int) $width . '&nbsp;&times;&nbsp;' . (int) $height . '</td>';
				echo '</tr>';
			}
		}

		if ( $is_image ) {
			echo '<tr' . ( ! $thumb_error ? '' : ' class="row-error"' ) . '>';
				echo '<th>' . esc_html__( 'Thumbnails', 'imagify-tools' ) . '</th><td>' . implode( '<br/>', array_map( 'esc_html', $sizes ) ) . '</td>';
			echo '</tr>';
		}

		echo '<tr' . ( $has_backup ? '' : ' class="row-error"' ) . '>';
			echo '<th>' . esc_html__( 'Has backup', 'imagify-tools' ) . '</th><td>' . esc_html( $has_backup ? __( 'Yes', 'imagify-tools' ) : __( 'No', 'imagify-tools' ) ) . '</td>';
		echo '</tr>';

		echo '</tbody></table>';
	}

	/**
	 * Print the meta box content.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param object $post WP_Post object of the current Attachment post.
	 * @param array  $data An array of data related to the meta box.
	 */
	public function print_meta_box_content( $post, $data ) {
		$metas     = $data['args']['metas'];
		$required  = $data['args']['required'];
		$all_metas = array_merge( $required, $metas );

		echo '<table><tbody>';

		foreach ( $all_metas as $meta_name => $meta_values ) {
			if ( isset( $required[ $meta_name ] ) && ! isset( $metas[ $meta_name ] ) ) {
				echo '<tr class="row-error"><th>' . esc_html( $meta_name ) . '</th><td>' . esc_html__( 'The meta is missing!', 'imagify-tools' ) . '</td></tr>';
				continue;
			}

			$multiple_metas = false;

			echo '<tr>';
			echo '<th>' . esc_html( $meta_name ) . '</th>';
			echo '<td>';

			$separator = '';

			foreach ( $meta_values as $meta_value ) {
				if ( is_numeric( $meta_value ) || is_null( $meta_value ) || is_bool( $meta_value ) ) {
					ob_start();
					call_user_func( 'var_dump', $meta_value );
					$meta_value = trim( wp_strip_all_tags( ob_get_clean() ) );
					$meta_value = preg_replace( '@^.+\.php:\d+:@', '', $meta_value );
					$meta_value = preg_replace( '@\(length=\d+\)$@', '<em><small>\0</small></em>', $meta_value );
				} else {
					$meta_value = esc_html( call_user_func( 'print_r', $meta_value, 1 ) );
				}

				if ( $multiple_metas ) {
					echo '<hr/>';
				}
				$multiple_metas = true;

				call_user_func( 'printf', '<pre>%s</pre>',  $meta_value );
			}

			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Add a common HTML class to our meta boxes.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 *
	 * @param  array $classes An array of postbox classes.
	 * @return array
	 */
	public function add_meta_box_class( $classes ) {
		$classes[] = self::METABOX_ID;
		return $classes;
	}

	/**
	 * Print some CSS.
	 *
	 * @since  1.0
	 * @author Grégory Viguier
	 */
	public function print_styles() {
		?>
		<style>
		.<?php echo self::METABOX_ID; ?> .inside {
			margin-top: 0;
		}
		.<?php echo self::METABOX_ID; ?> .inside table {
			width: 100%;
			border-spacing: 0;
			border-collapse: collapse;
		}
		.<?php echo self::METABOX_ID; ?> .inside th,
		.<?php echo self::METABOX_ID; ?> .inside td {
			padding-top: .5em;
			padding-bottom: .5em;
			vertical-align: top;
		}
		.<?php echo self::METABOX_ID; ?> .inside th {
			width: 15em;
			padding-right: 1em;
			text-align: right;
		}
		.<?php echo self::METABOX_ID; ?> .inside tr + tr th,
		.<?php echo self::METABOX_ID; ?> .inside tr + tr td {
			border-top: solid 1px rgb(238, 238, 238);
		}
		.<?php echo self::METABOX_ID; ?> .inside pre {
			width: 100%;
			margin: .1em 0 0;
			overflow-x: auto;
		}
		.<?php echo self::METABOX_ID; ?> .row-error th,
		.<?php echo self::METABOX_ID; ?> .row-error td {
			font-weight: normal;
			color: #fff;
			background: red;
		}
		.<?php echo self::METABOX_ID; ?> div.row-error {
			font-weight: normal;
			color: #fff;
			background: red;
			padding-left: 1em;
		}
		@media only screen and (max-width: 1500px) {
			.<?php echo self::METABOX_ID; ?> .inside th,
			.<?php echo self::METABOX_ID; ?> .inside td {
				display: block;
				width: 100%;
			}
			.<?php echo self::METABOX_ID; ?> .inside th {
				padding: .5em 1px;
				text-align: inherit;
			}
			.<?php echo self::METABOX_ID; ?> .inside td {
				border-top: solid 1px rgb(238, 238, 238);
			}
		}
		</style>
		<?php
	}
}
