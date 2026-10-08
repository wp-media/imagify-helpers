<?php
/**
 * Triage tab: the fixed checklist, in priority order.
 *
 * @var IMGDT_Diagnostics_Registry $registry
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

$builder     = new IMGDT_Triage_Builder( $registry );
$cards       = $builder->get_cards();
$other_count = $builder->get_other_count();
$queue       = new IMGDT_Queue_Status();
?>
<?php if ( isset( $_GET['imgdt_purged'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag, no state change. ?>
	<div class="notice notice-success"><p><?php esc_html_e( 'Queue cleared.', 'imagify-devtools' ); ?></p></div>
<?php endif; ?>

<div class="imgdt-toolbar">
	<p class="description"><?php esc_html_e( 'Checked in this order for optimization stuck/slow cases.', 'imagify-devtools' ); ?></p>
	<div>
		<a class="button" href="<?php echo esc_url( IMGDT_Admin_Post::refresh_url() ); ?>"><?php esc_html_e( 'Refresh', 'imagify-devtools' ); ?></a>
		<button type="button" class="button" id="imgdt-copy-report"><?php esc_html_e( 'Copy report', 'imagify-devtools' ); ?></button>
		<button type="button" class="button" id="imgdt-print-report"><?php esc_html_e( 'Download PDF', 'imagify-devtools' ); ?></button>
	</div>
</div>

<div class="imgdt-triage-cards">
	<?php foreach ( $cards as $card ) : ?>
		<div class="imgdt-triage-card imgdt-status-<?php echo esc_attr( $card->status ); ?>">
			<div class="imgdt-triage-card-label"><?php echo esc_html( $card->label ); ?></div>
			<div class="imgdt-triage-card-value"><?php echo esc_html( $card->value ); ?></div>
			<?php if ( $card->detail ) : ?>
				<div class="imgdt-triage-card-detail"><?php echo nl2br( esc_html( $card->detail ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nl2br() on already-escaped text. ?></div>
			<?php endif; ?>
			<?php if ( $card->action ) : ?>
				<div class="imgdt-triage-card-action"><?php echo $card->action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in IMGDT_Triage_Builder. ?></div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>

<p>
	<?php if ( $other_count > 0 ) : ?>
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => IMGDT_Admin_Pages::SLUG, 'tab' => 'diagnostics' ), admin_url( 'admin.php' ) ) ); ?>">
			<?php
			printf(
				/* translators: %d is a number of flagged checks. */
				esc_html( _n( '%d other check flagged — view full diagnostics', '%d other checks flagged — view full diagnostics', $other_count, 'imagify-devtools' ) ),
				(int) $other_count
			);
			?>
		</a>
	<?php else : ?>
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => IMGDT_Admin_Pages::SLUG, 'tab' => 'diagnostics' ), admin_url( 'admin.php' ) ) ); ?>">
			<?php esc_html_e( 'View full diagnostics', 'imagify-devtools' ); ?>
		</a>
	<?php endif; ?>
</p>

<h2><?php esc_html_e( 'Current Optimization Pipeline State', 'imagify-devtools' ); ?></h2>
<p class="description"><?php esc_html_e( 'Monitor and terminate stuck queues or background processes in real time.', 'imagify-devtools' ); ?></p>

<table class="widefat striped imgdt-table">
	<tbody>
		<tr>
			<th scope="row"><?php esc_html_e( 'Pending batches', 'imagify-devtools' ); ?></th>
			<td><?php echo esc_html( $queue->get_batch_count() ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Background actions (Action Scheduler)', 'imagify-devtools' ); ?></th>
			<td><?php echo esc_html( $queue->get_scheduler_count() ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Imagify transients', 'imagify-devtools' ); ?></th>
			<td><?php echo esc_html( $queue->get_transient_count() ); ?></td>
		</tr>
	</tbody>
</table>

<p class="description"><?php esc_html_e( 'If the counts above stay stuck despite no active optimization running, use the force purge option below. This will clear the pipeline but will not alter your optimized images.', 'imagify-devtools' ); ?></p>

<p>
	<a
		class="button"
		href="<?php echo esc_url( IMGDT_Admin_Post::purge_queue_url() ); ?>"
		onclick="return confirm('<?php echo esc_js( __( 'Clear the pending queue and stale scheduler state? This does not change any already-optimized images.', 'imagify-devtools' ) ); ?>');"
	><?php esc_html_e( 'Purge queue', 'imagify-devtools' ); ?></a>
</p>

<textarea id="imgdt-report-text" style="position:absolute;left:-9999px" tabindex="-1" aria-hidden="true"><?php echo esc_textarea( ( new IMGDT_Report( $registry ) )->get_text() ); ?></textarea>

<div class="imgdt-print-only">
	<h1>Imagify DevTools report</h1>
	<p><?php echo esc_html( home_url( '/' ) ); ?> — <?php echo esc_html( gmdate( 'Y-m-d H:i:s' ) ); ?> UTC</p>

	<h2><?php esc_html_e( 'Triage', 'imagify-devtools' ); ?></h2>
	<table class="widefat imgdt-table">
		<tbody>
			<?php foreach ( $cards as $card ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $card->label ); ?></th>
					<td><?php echo esc_html( $card->value ); ?></td>
					<td><?php echo nl2br( esc_html( $card->detail ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nl2br() on already-escaped text. ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Full diagnostics', 'imagify-devtools' ); ?></h2>
	<?php foreach ( $registry->get_sections() as $section ) : ?>
		<?php require IMGDT_VIEWS_PATH . 'partials/section-print.php'; ?>
	<?php endforeach; ?>
</div>

<script>
document.getElementById( 'imgdt-copy-report' ).addEventListener( 'click', function () {
	var field = document.getElementById( 'imgdt-report-text' );
	var text = field.value;

	if ( navigator.clipboard && window.isSecureContext ) {
		navigator.clipboard.writeText( text ).catch( function () { copyFallback( field ); } );
	} else {
		copyFallback( field );
	}
} );

function copyFallback( field ) {
	field.style.position = 'static';
	field.select();
	document.execCommand( 'copy' );
	field.style.position = 'absolute';
	field.style.left = '-9999px';
}

document.getElementById( 'imgdt-print-report' ).addEventListener( 'click', function () {
	window.print();
} );
</script>
