<?php
/**
 * Plugin Name: Imagify | On Low Memory
 * Description: Filters the chunk size of data fetching requests to reduce the value.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/various/imagify-on-low-memory/
 * Author:      WP Media
 * Author URI:  https://wp-media.me/
 * Licence:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP Media 2019
 */

namespace ImagifyPlugin\Helpers\various\on_low_memory;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

/**
 * Filter the chunk size of the requests fetching the data: reduce the value.
 *
 * @author Grégory Viguier
 * @return int The maximum number of elements per chunk.
 */
function count_saving_data_limit() {
	return 10000;
}
add_filter( 'imagify_count_saving_data_limit', __namespace__ . '\count_saving_data_limit' );