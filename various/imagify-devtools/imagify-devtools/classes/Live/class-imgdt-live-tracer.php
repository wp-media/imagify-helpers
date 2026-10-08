<?php
/**
 * Captures optimization lifecycle events for the Live view.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Hooks into Imagify's optimization process to record a per-media event
 * timeline: job started, each thumbnail size finished, job finished.
 *
 * @since 0.2.0
 */
class IMGDT_Live_Tracer {

	/**
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * @var IMGDT_Live_Store
	 */
	private IMGDT_Live_Store $store;

	/**
	 * Constructor (private, singleton).
	 */
	private function __construct() {
		$this->store = new IMGDT_Live_Store();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.2.0
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.2.0
	 */
	public function init(): void {
		add_filter( 'imagify_before_optimize', array( $this, 'on_before_optimize' ), 10, 4 );
		add_action( 'imagify_after_optimize_size', array( $this, 'on_after_optimize_size' ), 10, 6 );
		add_action( 'imagify_after_optimize', array( $this, 'on_after_optimize' ), 10, 2 );
	}

	/**
	 * Fires before a media starts optimizing.
	 *
	 * This is a filter and must return `$data` unchanged; only used here to
	 * observe the item, not to alter it.
	 *
	 * @since 0.2.0
	 *
	 * @param array  $data   Filtered data, unused.
	 * @param mixed  $error  Unused.
	 * @param mixed  $process Optimization process instance, unused here.
	 * @param mixed  $item   Job item, expected to contain an 'id' key.
	 * @return array
	 */
	public function on_before_optimize( $data, $error, $process, $item ) {
		imgdt_safe(
			function () use ( $item ) {
				$media_id = is_array( $item ) && isset( $item['id'] ) ? (int) $item['id'] : 0;

				if ( $media_id > 0 ) {
					$this->store->record( $media_id, 'started' );
				}
			}
		);

		return $data;
	}

	/**
	 * Fires after one thumbnail size finishes optimizing.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $process    Optimization process instance.
	 * @param mixed $file       File data, unused.
	 * @param mixed $thumb_size Size name.
	 * @param mixed $level      Optimization level, unused.
	 * @param mixed $next_gen   Whether this is a next-gen (WebP/AVIF) pass, unused.
	 * @param mixed $disabled   Whether this size is disabled, unused.
	 */
	public function on_after_optimize_size( $process, $file, $thumb_size, $level, $next_gen, $disabled ): void {
		imgdt_safe(
			function () use ( $process, $thumb_size ) {
				$media_id = $this->get_media_id( $process );

				if ( $media_id > 0 ) {
					$this->store->record(
						$media_id,
						'size',
						array( 'size' => is_string( $thumb_size ) ? $thumb_size : '' )
					);
				}
			}
		);
	}

	/**
	 * Fires after a media finishes optimizing.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $process Optimization process instance.
	 * @param mixed $item    Job item, expected to contain an 'id' key and optionally an 'error'.
	 */
	public function on_after_optimize( $process, $item ): void {
		imgdt_safe(
			function () use ( $process, $item ) {
				$media_id = is_array( $item ) && isset( $item['id'] ) ? (int) $item['id'] : $this->get_media_id( $process );

				if ( $media_id <= 0 ) {
					return;
				}

				$error = is_array( $item ) && isset( $item['error'] ) && is_wp_error( $item['error'] )
					? $item['error']->get_error_message()
					: '';

				$this->store->record( $media_id, 'done', array( 'error' => $error ) );
			}
		);
	}

	/**
	 * Get a media ID from an optimization process instance.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $process Optimization process instance.
	 * @return int
	 */
	private function get_media_id( $process ): int {
		if ( is_object( $process ) && method_exists( $process, 'get_media' ) ) {
			$media = $process->get_media();

			if ( is_object( $media ) && method_exists( $media, 'get_id' ) ) {
				return (int) $media->get_id();
			}
		}

		return 0;
	}
}
