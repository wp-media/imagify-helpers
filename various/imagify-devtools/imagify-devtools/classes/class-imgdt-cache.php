<?php
/**
 * Site-transient cache helper.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Site-transient wrapper: one prefix, one default duration.
 *
 * Uses site transients (not plain transients) so a cached value is shared
 * network-wide on multisite instead of duplicated per site.
 *
 * @since 0.1.0
 */
class IMGDT_Cache {

	/**
	 * Prefix applied to every transient name this plugin stores.
	 *
	 * @var string
	 */
	public const PREFIX = 'imgdt_';

	/**
	 * Default cache duration, in seconds.
	 *
	 * @var int
	 */
	public const DEFAULT_DURATION = 30 * MINUTE_IN_SECONDS;

	/**
	 * Get a cached value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key Unprefixed cache key.
	 * @return mixed False if not set.
	 */
	public static function get( string $key ) {
		return get_site_transient( self::PREFIX . $key );
	}

	/**
	 * Set a cached value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key      Unprefixed cache key.
	 * @param mixed  $value    Value to store.
	 * @param int    $duration Duration in seconds.
	 */
	public static function set( string $key, $value, int $duration = self::DEFAULT_DURATION ): void {
		set_site_transient( self::PREFIX . $key, $value, $duration );
	}

	/**
	 * Delete a cached value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key Unprefixed cache key.
	 */
	public static function delete( string $key ): void {
		delete_site_transient( self::PREFIX . $key );
	}

	/**
	 * Get the number of minutes remaining before a cached value expires.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key Unprefixed cache key.
	 * @return int Minutes remaining (rounded up). Falls back to the default duration in minutes.
	 */
	public static function minutes_remaining( string $key ): int {
		$timeout = get_site_option( '_site_transient_timeout_' . self::PREFIX . $key );

		if ( ! $timeout || $timeout < time() ) {
			return (int) ceil( self::DEFAULT_DURATION / MINUTE_IN_SECONDS );
		}

		return (int) ceil( ( $timeout - time() ) / MINUTE_IN_SECONDS );
	}
}
