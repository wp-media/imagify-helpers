<?php
/**
 * Plugin Name: Imagify | Do not generate WebP (since v2.2)
 * Description: Skip generating WebP files in Imagify v2.2+
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/webp/imagify-dont-generate-webp
 * Author:      Imagify Support Team
 * Author URI:  http://imagify.io/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2024
 */

namespace ImagifyPlugin\Helpers\skipwebp;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) or die();

/**
 * Skip WebP files to be generated
 *
 * @author Marko Nikolic
 */
function imagify_dont_generate_webp() {

	add_filter( 'imagify_nextgen_images_formats', function( $formats ) {
		if ( isset( $formats['webp'] ) ) {
			unset( $formats['webp'] );
		}
	
		return $formats;
	} );

}
add_action( 'imagify_loaded', __NAMESPACE__ . '\imagify_dont_generate_webp' );