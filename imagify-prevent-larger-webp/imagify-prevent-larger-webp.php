<?php
/**
 * Plugin Name: Imagify | Prevent Larger WebP
 * Description: Prevents WebP conversion if larger than original format image.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/webp/imagify-prevent-larger-webp/
 * Author:      WP Media
 * Author URI:  https://wp-media.me/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2019
 */

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

add_filter( 'imagify_keep_large_webp', '__return_false' );