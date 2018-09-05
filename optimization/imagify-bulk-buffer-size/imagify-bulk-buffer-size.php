<?php
/**
 * Plugin Name: Imagify | Change Bulk Buffer Size
 * Description: Helps to avoid CPU issues during bulk optimization.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/optimization/imagify-bulk-buffer-size/
 * Author:      Imagify Support Team
 * Author URI:  http://imagify.io/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2018
 */
namespace ImagifyPlugin\Helpers\optimization\bulk;

defined( 'ABSPATH' ) or die();

add_filter( 'imagify_bulk_buffer_sizes', __NAMESPACE__ . '\buffer_sizes', 10, 3 );
/**
 * Prevent automatic optimization for PDF.
 *
 * @author Grégory Viguier
 * @author Caspar Hübinger
 *
 * @param  array $buffer_sizes An array of number of parallel queries
 * @return array               Modified array
 */
function buffer_sizes( $buffer_sizes ) {

	$buffer = array_keys( $buffer_sizes );

	return array_fill_keys( $buffer_sizes, 1 );
}
