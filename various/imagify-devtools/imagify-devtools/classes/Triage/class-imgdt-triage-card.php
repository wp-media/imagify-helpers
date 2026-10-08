<?php
/**
 * One triage checklist item.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.2.0
 */
class IMGDT_Triage_Card {

	/**
	 * Status meaning nothing wrong found. Rendered plainly, no badge.
	 *
	 * @var string
	 */
	public const STATUS_OK = 'ok';

	/**
	 * Status meaning a check failed. Rendered flagged, with detail.
	 *
	 * @var string
	 */
	public const STATUS_ERROR = 'error';

	/**
	 * @param string $label  Card title.
	 * @param string $status One of the STATUS_* constants.
	 * @param string $value  Current value, e.g. 'Blocked', '256M', 'Imagick'.
	 * @param string $detail Extra detail line, e.g. the blocked URLs and their HTTP codes.
	 * @param string $action Optional safe HTML for an action control shown under the card.
	 */
	public function __construct(
		public readonly string $label,
		public readonly string $status,
		public readonly string $value,
		public readonly string $detail = '',
		public readonly string $action = ''
	) {}
}
