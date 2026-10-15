<?php
/**
 * Plugin Name:       Imagify DevTools
 * Plugin URI:        https://github.com/wp-media/imagify-helpers/tree/master/various/imagify-devtools
 * Description:       Diagnostics and a live optimization trace for debugging Imagify installs.
 * Version:           0.2.1
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            WP Media
 * Author URI:        https://wp-media.me/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       imagify-devtools
 * Domain Path:       /languages
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Plugin version. Also used as the enqueued assets' cache-busting suffix.
 */
define( 'IMGDT_VERSION', '0.2.1' );

/**
 * Absolute path to the plugin's main file.
 */
define( 'IMGDT_FILE', __FILE__ );

/**
 * Absolute path to the plugin directory, with a trailing slash.
 */
define( 'IMGDT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * URL to the plugin directory, with a trailing slash.
 */
define( 'IMGDT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Absolute path to the classes directory, with a trailing slash.
 */
define( 'IMGDT_CLASSES_PATH', IMGDT_PATH . 'classes/' );

/**
 * Absolute path to the views directory, with a trailing slash.
 */
define( 'IMGDT_VIEWS_PATH', IMGDT_PATH . 'views/' );

/**
 * Capability required to use this plugin.
 */
if ( ! defined( 'IMGDT_CAPABILITY' ) ) {
	define( 'IMGDT_CAPABILITY', 'manage_options' );
}

require_once IMGDT_CLASSES_PATH . 'class-imgdt-loader.php';

/**
 * Boot the plugin.
 *
 * @since 0.1.0
 */
function imgdt_init(): void {
	IMGDT_Loader::register();

	imgdt_safe( static fn() => IMGDT_Admin_Post::init() );
	imgdt_safe( static fn() => IMGDT_Admin_Pages::get_instance()->init() );
	imgdt_safe( static fn() => IMGDT_Attachment_Metabox::get_instance()->init() );
	imgdt_safe( static fn() => IMGDT_Live_Tracer::get_instance()->init() );
	imgdt_safe( static fn() => IMGDT_Live_Page::get_instance()->init() );
	imgdt_safe( static fn() => ( new IMGDT_Image_Editor_Override() )->init() );
}
add_action( 'plugins_loaded', 'imgdt_init' );

/**
 * Run a callback, catching and (when WP_DEBUG_LOG is on) logging anything
 * it throws instead of letting it propagate.
 *
 * @since 0.1.0
 *
 * @param callable $callback Code to run.
 * @param mixed    $fallback Value to return if it throws.
 * @return mixed The callback's return value, or $fallback.
 */
function imgdt_safe( callable $callback, $fallback = null ) {
	try {
		return $callback();
	} catch ( \Throwable $e ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( '[Imagify DevTools] %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return $fallback;
	}
}

/**
 * Load translations.
 *
 * @since 0.1.0
 */
function imgdt_load_textdomain(): void {
	load_plugin_textdomain( 'imagify-devtools', false, dirname( plugin_basename( IMGDT_FILE ) ) . '/languages' );
}
add_action( 'init', 'imgdt_load_textdomain' );

/**
 * Get the cache-busting version for one of this plugin's assets: the plugin
 * version plus the file's modification time, so an updated or reinstalled
 * file is never served from a stale browser, host, or CDN cache just because
 * the plugin version string stayed the same.
 *
 * @since 0.2.0
 *
 * @param string $relative_path Path relative to the plugin directory, e.g. 'assets/css/admin.css'.
 * @return string
 */
function imgdt_asset_version( string $relative_path ): string {
	$mtime = @filemtime( IMGDT_PATH . $relative_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing file just falls back to the plain version.

	return $mtime ? IMGDT_VERSION . '.' . $mtime : IMGDT_VERSION;
}

/**
 * Get the current request's IP address.
 *
 * @since 0.1.0
 *
 * @return string Empty string if none could be determined.
 */
function imgdt_get_ip(): string {
	foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
		if ( empty( $_SERVER[ $key ] ) ) {
			continue;
		}

		$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
		$ip = trim( explode( ',', $ip )[0] );

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	return '';
}

/**
 * Get a WP filesystem object.
 *
 * @since 0.1.0
 *
 * @return WP_Filesystem_Base
 */
function imgdt_get_filesystem() {
	static $filesystem;

	if ( function_exists( 'imagify_get_filesystem' ) ) {
		return imagify_get_filesystem();
	}

	if ( $filesystem ) {
		return $filesystem;
	}

	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

	$filesystem = new WP_Filesystem_Direct( new stdClass() );

	if ( ! defined( 'FS_CHMOD_DIR' ) ) {
		define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
	}
	if ( ! defined( 'FS_CHMOD_FILE' ) ) {
		define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
	}

	return $filesystem;
}

/**
 * Check whether the current user can use this plugin.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function imgdt_current_user_can(): bool {
	/**
	 * Filters the capability required to access Imagify DevTools.
	 *
	 * @since 0.1.0
	 *
	 * @param string $capability A capability or role name.
	 */
	$capability = apply_filters( 'imgdt_capability', IMGDT_CAPABILITY );

	return current_user_can( $capability );
}
