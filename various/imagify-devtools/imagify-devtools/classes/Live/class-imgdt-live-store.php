<?php
/**
 * Storage for live optimization trace events.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Short-lived, per-media event log used by the Live view.
 *
 * @since 0.2.0
 */
class IMGDT_Live_Store {

	/**
	 * How long a media's events and the recent-ids index are kept.
	 *
	 * @var int
	 */
	private const TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Maximum number of media IDs kept in the recent-ids index.
	 *
	 * @var int
	 */
	private const MAX_RECENT = 20;

	/**
	 * Maximum number of events kept per media.
	 *
	 * @var int
	 */
	private const MAX_EVENTS = 50;

	/**
	 * Record one event for a media.
	 *
	 * @since 0.2.0
	 *
	 * @param int    $media_id Media (attachment) ID.
	 * @param string $step     Short step name, e.g. 'started', 'size', 'done'.
	 * @param array  $detail   Extra data for this step.
	 */
	public function record( int $media_id, string $step, array $detail = array() ): void {
		if ( $media_id <= 0 ) {
			return;
		}

		$events   = $this->get_events( $media_id );
		$events[] = array(
			'time'   => microtime( true ),
			'step'   => $step,
			'detail' => $detail,
		);

		if ( count( $events ) > self::MAX_EVENTS ) {
			$events = array_slice( $events, -self::MAX_EVENTS );
		}

		IMGDT_Cache::set( 'live_' . $media_id, $events, self::TTL );
		$this->touch_recent( $media_id );
	}

	/**
	 * Get the events recorded for a media.
	 *
	 * @since 0.2.0
	 *
	 * @param int $media_id Media (attachment) ID.
	 * @return array
	 */
	public function get_events( int $media_id ): array {
		$events = IMGDT_Cache::get( 'live_' . $media_id );

		return is_array( $events ) ? $events : array();
	}

	/**
	 * Get the most recently active media IDs, newest first.
	 *
	 * @since 0.2.0
	 *
	 * @return int[]
	 */
	public function get_recent_ids(): array {
		$ids = IMGDT_Cache::get( 'live_recent' );

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	/**
	 * Move a media to the front of the recent-ids index.
	 *
	 * @since 0.2.0
	 *
	 * @param int $media_id Media (attachment) ID.
	 */
	private function touch_recent( int $media_id ): void {
		$ids = $this->get_recent_ids();
		$ids = array_diff( $ids, array( $media_id ) );
		array_unshift( $ids, $media_id );
		$ids = array_slice( $ids, 0, self::MAX_RECENT );

		IMGDT_Cache::set( 'live_recent', $ids, self::TTL );
	}
}
