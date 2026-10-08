<?php
/**
 * Capability helper.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Thin wrapper so every screen/action in the plugin checks access the same way.
 *
 * @since 0.1.0
 */
class IMGDT_Capability {

	/**
	 * Check access, and stop the request with a 403 page if it fails.
	 *
	 * Use in places where a hard stop is appropriate (form handlers). Screens
	 * rendered through the admin menu are already gated by the capability
	 * passed to `add_menu_page()`, so this is mostly for admin-post handlers.
	 *
	 * @since 0.1.0
	 */
	public static function require_or_die(): void {
		if ( imgdt_current_user_can() ) {
			return;
		}

		wp_die(
			esc_html__( 'Sorry, you are not allowed to access this page.', 'imagify-devtools' ),
			403
		);
	}
}
