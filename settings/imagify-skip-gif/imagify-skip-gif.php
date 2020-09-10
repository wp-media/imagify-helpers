<?php
/**
 * Plugin Name: Imagify | No Auto-Optimization for GIFs
 * Description: Excludes GIF files from being auto-optimized once they’re uploaded.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/optimization/imagify-no-auto-optimize-gif/
 * Author:      Imagify Support Team
 * Author URI:  http://imagify.io/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2018
 */
namespace ImagifyPlugin\Helpers\optimization\auto;

defined( 'ABSPATH' ) or die();

add_filter( 'imagify_auto_optimize_attachment', __NAMESPACE__ . '\no_optimize_gif', 10, 3 );
/**
 * Prevent automatic optimization for GIF.
 *
 * @author Grégory Viguier
 * @author Caspar Hübinger
 *
 * @param  bool  $optimize      True to optimize, false otherwise.
 * @param  int   $attachment_id Attachment ID.
 * @param  array $metadata      An array of attachment meta data.
 * @return bool
 */
function no_optimize_gif( $optimize, $attachment_id, $metadata ) {
	if ( ! $optimize ) {
		return false;
	}

	$mime_type = get_post_mime_type( $attachment_id );

	return 'image/gif' !== $mime_type;
}