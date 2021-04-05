<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that prints Imagify data related to NGG.
 *
 * @package Imagify Tools
 * @since   1.0.3
 * @author  Grégory Viguier
 */
class IMGT_Nextgen_Gallery {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @access protected
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	protected static $instance;

	/**
	 * The constructor.
	 *
	 * @access protected
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	protected function __construct() {}

	/**
	 * Get the main Instance.
	 *
	 * @access public
	 * @since  1.0.3
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
	 * @access public
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	public static function delete_instance() {
		unset( self::$instance );
	}

	/**
	 * Class init.
	 *
	 * @access public
	 * @since  1.0.3
	 * @author Grégory Viguier
	 */
	public function init() {
		if ( current_user_can( imagify_tools_get_capacity() ) ) {
			add_filter( 'ngg_manage_images_number_of_columns', array( $this, 'manage_images_number_of_columns' ) );
		}
	}

	/**
	 * Add "Imagify" column in admin.php?page=nggallery-manage-gallery.
	 *
	 * @access public
	 * @since  1.0.3
	 * @author Grégory Viguier
	 *
	 * @param  int $count Number of columns.
	 * @return int Incremented number of columns.
	 */
	public function manage_images_number_of_columns( $count ) {
		add_filter( 'ngg_manage_images_column_7_content', array( $this, 'manage_media_custom_column' ), 20, 2 );
		return $count;
	}

	/**
	 * Get the column content.
	 *
	 * @access public
	 * @since  1.0.3
	 * @author Grégory Viguier
	 *
	 * @param  string $output The column content.
	 * @param  object $image  An NGG Image object.
	 * @return string
	 */
	public function manage_media_custom_column( $output, $image ) {
		$output .= '<strong>' . __( 'NGG data:', 'imagify-tools' ) . '</strong>';
		$output .= '<div style="overflow-x: auto; margin-bottom: 200px;"><pre>' . esc_html( call_user_func( 'print_r', $image, 1 ) ) . '</pre></div>';

		if ( class_exists( '\\Imagify\\ThirdParty\\NGG\\Optimization\\Process\\NGG' ) ) {
			$process = '\\Imagify\\ThirdParty\\NGG\\Optimization\\Process\\NGG';
			$process = new $process( $image );
			$data    = $process->get_data()->get_row();
		} elseif ( class_exists( 'Imagify_NGG_Attachment' ) ) {
			$attachment = new Imagify_NGG_Attachment( $image );
			$data       = $attachment->get_row();
		} else {
			return $output;
		}

		$output .= '<strong>' . __( 'Imagify data:', 'imagify-tools' ) . '</strong>';
		$output .= '<div style="overflow-x: auto; margin-bottom: 200px;"><pre>' . esc_html( call_user_func( 'print_r', $data, 1 ) ) . '</pre></div>';

		return $output;
	}
}
