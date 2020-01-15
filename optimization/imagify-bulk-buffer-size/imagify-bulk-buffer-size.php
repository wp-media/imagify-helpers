<?php
/**
 * Plugin Name:  Imagify | Change Bulk Buffer Size
 * Description:  Helps to avoid CPU issues during bulk optimization.
 * Plugin URI:   https://github.com/wp-media/imagify-helpers/tree/master/optimization/imagify-bulk-buffer-size/
 * Version:      1.1
 * Requires PHP: 5.3
 * Author:       Imagify Support Team
 * Author URI:   http://imagify.io/
 * License:      GPLv2
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright 2020 WP Media
 */

namespace WPMedia\ImagifyPluginHelpers\Optimization\BulkBufferSize;

defined( 'ABSPATH' ) || exit;

add_filter( 'imagify_bulk_buffer_sizes', __NAMESPACE__ . '\buffer_sizes' );
/**
 * Allow only one optimization at the same time during bulk optimization, for all contexts.
 *
 * @since  1.0
 * @author Grégory Viguier
 * @author Caspar Hübinger
 *
 * @param  array $buffer_sizes An array of number of parallel queries. Array keys are contexts, like 'wp' and 'custom-folders'.
 * @return array               Modified array.
 */
function buffer_sizes( $buffer_sizes ) {
	$contexts = array_keys( $buffer_sizes );
	return array_fill_keys( $contexts, 1 );
}
