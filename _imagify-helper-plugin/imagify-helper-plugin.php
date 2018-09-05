<?php
/**
 * Plugin Name: Imagify | {What This Plugin Does}
 * Description: {What this plugin does in one clear sentence.}
 * Plugin URI:  {GitHub repo URL of this plugin}
 * Author:      Imagify Support Team
 * Author URI:  http://imagify.io/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2018
 */

// EDIT THIS: Replace `boilerplate` with your custom subnamespace.
// Namespaces must be declared before any other declaration.
namespace ImagifyPlugin\Helpers\boilerplate;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) or die();

/**
 * Adds customizations once Imagify has loaded.
 * HEADS UP: If you keep the deactivation hook further down this file,
 * you will have to edit it to remove_filter() this function.
 *
 * @author {Author Name}
 */
function do_stuff() {

	// Do something here.
	add_filter( 'example_filter', 'example_function' );

}
// Hooking into `imagify_loaded` is a safe way to make sure all Imagify
// features are available, however, it’s not required.
// Using other hooks directly will be just fine in most cases.
add_action( 'imagify_loaded', __NAMESPACE__ . '\do_stuff' );
