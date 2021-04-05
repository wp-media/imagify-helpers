<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handle the data for the logs page.
 *
 * @package Imagify Tools
 * @since   1.0
 * @author  Grégory Viguier
 */
class IMGT_Admin_Model_Logs {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * The constructor.
	 *
	 * @since 1.0
	 * @author Grégory Viguier
	 */
	public function __construct() {
		global $wp_list_table;

		// Instantiate the list.
		$wp_list_table = new IMGT_Logs_List_Table( array( 'screen' => convert_to_screen( IMGT_Logs::POST_TYPE ) ) );

		// Query the Logs.
		$wp_list_table->prepare_items();
	}
}
