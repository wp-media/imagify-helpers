<?php
/**
 * Plugin Name: Imagify | Skip &lt;picture&gt; Tag Replacement
 * Description: Excludes &lt;img&gt; tags from &lt;picture&gt; tag replacement for WebP display if they have either a data-skip-picture-replacement="yes" attribute or "skip-picture-replacement" class. Can be edited to target other classes for exclusion.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers/tree/master/optimization/imagify-skip-picture-tag-replacement/
 * Author:      WP Media
 * Author URI:  https://wp-media.me/
 * Licence:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP Media 2020
 */

namespace Imagify\Helpers\webp\skip_picture_tag_replacement;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

/**
 * Excludes img tags with targeted attribute value or classes from 
 * picture tag replacement for WebP display
 *
 * @author Joe DiSalvo
 */
function find_images_to_skip( $images ) {

  // Classes to target for exclusion from picture tag replacement
  $classes_to_skip = array(
    // EDIT_HERE
    'skip-picture-replacement',
    // STOP_EDITING
  );

  foreach ( $images as $i => $image ) {
    if ( isset( $image[ 'attributes' ][ 'data-skip-picture-replacement' ] ) ) {
      if ( $image[ 'attributes' ][ 'data-skip-picture-replacement' ] === 'yes' ) {
        unset( $images[ $i ] );
      }
    }
    if ( isset( $image[ 'attributes' ][ 'class' ] ) ) {
      foreach( $classes_to_skip as $class_to_skip ) {
        if ( preg_match( "/\b{$class_to_skip}\b/", $image[ 'attributes' ][ 'class' ] ) ) {
          unset( $images[ $i ] );
        }
      }
    }
  }
  return $images;
}
add_filter( 'imagify_webp_picture_images_to_display', __NAMESPACE__ . '\find_images_to_skip' );