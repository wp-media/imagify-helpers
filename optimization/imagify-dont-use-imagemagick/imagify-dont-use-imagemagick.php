<?php
/**
 * Plugin Name: Imagify | Don't use ImageMagick
 * Description: Don't use the ImageMagick library to manipulate images (poor performances).
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/optimization/imagify-dont-use-imagick/
 * Author:      WP Media
 * Author URI:  https://wp-media.me/
 * Licence:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP Media 2020
 */

namespace Imagify\Helpers\optimization\dont_use_imagick;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

add_filter(
	'wp_image_editors',
	function( $editors ) {
		$editors = array_diff(
			$editors,
			['WP_Image_Editor_Imagick']
		);
		$editors[] = 'WP_Image_Editor_Imagick';

		return $editors;
	}
);