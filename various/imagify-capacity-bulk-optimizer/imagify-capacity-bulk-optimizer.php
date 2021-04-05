<?php
/**
 * Plugin Name: Imagify | Capacity Bulk Optimizer (Multisite)
 * Description: Bumps up the default user capacity required for bulk-optimization to Super Admin on multisite.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/settings/imagify-capacity-bulk-optimizer/
 * Author:      WP Media
 * Author URI:  https://wp-media.me/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2018
 */

// Namespaces must be declared before any other declaration.
namespace ImagifyPlugin\Helpers\settings\imagify_capacity_bulk_optimizer;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) or die();

/**
 * Bumps up capacity for bulk optimization to super admin on multisite.
 *
 * @author Caspar Hübinger
 * @see https://github.com/wp-media/imagify-plugin/blob/c5c004f79e39595098e7269baa704bfd06f727de/inc/functions/common.php#L4-L67
 *
 * @param  string $capacity  WordPress user capacity
 * @param  string $describer Contextual describer of context. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', 'auto-optimize', and 'optimize-file'.
 * @return string            Imagify capacity according to context
 */
function multisite_bulk_optimize( $capacity, $describer ) {

	return is_multisite() && 'bulk-optimize' === $describer ? 'manage_network_options' : $capacity;
}
add_action( 'imagify_capacity', __NAMESPACE__ . '\multisite_bulk_optimize', 10, 2 );