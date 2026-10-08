<?php
/**
 * Live optimization trace page.
 *
 * @var IMGDT_Live_Page $this
 * @var int[]            $recent
 * @var int              $media_id
 * @var bool|null         $api_key_valid
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

$queue = $this->get_queue_status();
?>
<div class="wrap imgdt-wrap">
	<h1>Imagify DevTools — Live</h1>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . IMGDT_Admin_Pages::SLUG ) ); ?>">
			&larr; <?php esc_html_e( 'Back to Imagify DevTools', 'imagify-devtools' ); ?>
		</a>
	</p>

	<?php if ( false === $api_key_valid ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Imagify API key is invalid. No optimization starts, so no events are recorded.', 'imagify-devtools' ); ?></p></div>
	<?php endif; ?>

	<p class="description">
		Queue:
		<?php if ( ! $queue['available'] ) : ?>
			unavailable
		<?php elseif ( $queue['paused'] ) : ?>
			paused
		<?php elseif ( $queue['active'] ) : ?>
			active
		<?php else : ?>
			idle
		<?php endif; ?>
	</p>

	<div style="display:flex;gap:24px;align-items:flex-start">
		<div style="width:280px;flex-shrink:0">
			<h2>Recently active</h2>
			<?php if ( ! $recent ) : ?>
				<p class="description">Nothing recorded yet. Trigger an optimization to see it here.</p>
			<?php else : ?>
				<ul style="margin:0">
					<?php foreach ( $recent as $id ) : ?>
						<li>
							<a href="<?php echo esc_url( IMGDT_Live_Page::url( array( 'media' => $id ) ) ); ?>">
								<?php echo esc_html( get_the_title( $id ) ?: '#' . $id ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div style="flex:1;min-width:0">
			<?php if ( ! $media_id ) : ?>
				<p class="description">Select a media on the left.</p>
			<?php else : ?>
				<h2><?php echo esc_html( get_the_title( $media_id ) ?: '#' . $media_id ); ?></h2>
				<table class="widefat striped imgdt-table">
					<thead>
						<tr>
							<th>Time</th>
							<th>Step</th>
							<th>Detail</th>
						</tr>
					</thead>
					<tbody id="imgdt-live-events"></tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ( $media_id ) : ?>
<script>
( function () {
	var url = <?php echo wp_json_encode( IMGDT_Live_Page::poll_url( $media_id ) ); ?>;
	var body = document.getElementById( 'imgdt-live-events' );

	function poll() {
		fetch( url, { credentials: 'same-origin' } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( data ) {
				body.innerHTML = ( data.events || [] ).map( function ( event ) {
					var detail = event.detail && Object.keys( event.detail ).length
						? JSON.stringify( event.detail )
						: '';
					return '<tr><td>' + event.time + '</td><td>' + event.step + '</td><td>' + detail + '</td></tr>';
				} ).join( '' );
			} )
			.catch( function () {} );
	}

	poll();
	setInterval( poll, 2000 );
} )();
</script>
<?php endif; ?>
