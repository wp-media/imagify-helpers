<?php
/**
 * Optional override: prefer GD over ImageMagick.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Moves `WP_Image_Editor_Imagick` to the end of the `wp_image_editors`
 * list so GD is tried first. Imagick stays available as a fallback. Only
 * the core class name is matched; a third-party editor class is never
 * altered.
 *
 * Based on the `imagify-dont-use-imagemagick.php` helper plugin.
 *
 * @since 0.2.0
 * @author Saransh Jain
 */
class IMGDT_Image_Editor_Override {

	/**
	 * Option name storing whether this override is enabled.
	 *
	 * @var string
	 */
	public const OPTION = 'imgdt_prefer_gd';

	/**
	 * Init: apply the filter if the option is enabled.
	 *
	 * @since 0.2.0
	 */
	public function init(): void {
		if ( self::is_enabled() ) {
			add_filter( 'wp_image_editors', array( __CLASS__, 'reorder_editors' ) );
		}
	}

	/**
	 * Whether the override is currently enabled.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) get_option( self::OPTION, false );
	}

	/**
	 * Turn the override on.
	 *
	 * @since 0.2.0
	 */
	public static function enable(): void {
		update_option( self::OPTION, true );
	}

	/**
	 * Turn the override off.
	 *
	 * @since 0.2.0
	 */
	public static function disable(): void {
		delete_option( self::OPTION );
	}

	/**
	 * Move `WP_Image_Editor_Imagick` to the end of the list, if present.
	 *
	 * @since 0.2.0
	 *
	 * @param string[] $editors Image editor class names.
	 * @return string[]
	 */
	public static function reorder_editors( $editors ) {
		if ( ! is_array( $editors ) ) {
			return $editors;
		}

		$editors   = array_diff( $editors, array( 'WP_Image_Editor_Imagick' ) );
		$editors[] = 'WP_Image_Editor_Imagick';

		return $editors;
	}
}
