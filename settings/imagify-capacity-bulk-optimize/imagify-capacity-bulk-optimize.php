<?php
/**
 * Plugin Name:  Bulk Optimize only for Super Admins (Multisite)
 * Description:  Bumps up the default user capacity required for bulk-optimization to Super Admin on multisite.
 * Plugin URI:   https://github.com/wp-media/imagify-helpers/tree/master/settings/imagify-capacity/
 * Version:      1.1
 * Requires PHP: 5.3
 * Author:       Imagify Support Team
 * Author URI:   http://imagify.io/
 * License:      GPLv2
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright 2020 WP Media
 */

namespace WPMedia\ImagifyPluginHelpers\Settings\BulkOptimUserCapacity;

defined( 'ABSPATH' ) || exit;

add_action( 'imagify_loaded', __NAMESPACE__ . '\init' );
/**
 * Plugin init.
 *
 * @since  1.1
 * @author Grégory Viguier
 */
function init() {
	if ( is_multisite() ) {
		add_action( 'imagify_capacity', __NAMESPACE__ . '\multisite_bulk_optimize', 10, 2 );
	}
}

/**
 * Bumps up capacity for bulk optimization to super admin on multisite.
 *
 * @since  1.0
 * @author Caspar Hübinger
 * @see    https://github.com/wp-media/imagify-plugin/blob/cd17020d26c12d24fbca59455e87255a3e6de2dc/classes/Context/AbstractContext.php#L275-L300
 *
 * @param  string $capacity  WordPress user capacity.
 * @param  string $describer Contextual describer of context. Possible values are like 'manage', 'bulk-optimize', 'manual-optimize', 'auto-optimize'.
 * @return string            Imagify capacity according to context.
 */
function multisite_bulk_optimize( $capacity, $describer ) {
	return 'bulk-optimize' === $describer ? 'manage_network_options' : $capacity;
}
