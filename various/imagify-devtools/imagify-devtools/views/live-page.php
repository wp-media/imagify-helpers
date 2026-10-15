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

if ( ! $queue['available'] ) {
	$queue_label = __( 'unavailable', 'imagify-devtools' );
} elseif ( $queue['paused'] ) {
	$queue_label = __( 'paused', 'imagify-devtools' );
} elseif ( $queue['active'] ) {
	$queue_label = __( 'active', 'imagify-devtools' );
} else {
	$queue_label = __( 'idle', 'imagify-devtools' );
}
?>
<div class="wrap imgdt-wrap">
	<h1><?php esc_html_e( 'Imagify DevTools — Live', 'imagify-devtools' ); ?></h1>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . IMGDT_Admin_Pages::SLUG ) ); ?>">
			&larr; <?php esc_html_e( 'Back to Imagify DevTools', 'imagify-devtools' ); ?>
		</a>
	</p>

	<?php if ( false === $api_key_valid ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Imagify API key is invalid. No optimization starts, so no events are recorded.', 'imagify-devtools' ); ?></p></div>
	<?php endif; ?>

	<p class="description">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s is the queue state: unavailable, paused, active or idle. */
				__( 'Queue: %s', 'imagify-devtools' ),
				$queue_label
			)
		);
		?>
	</p>

	<div class="imgdt-live-layout">
		<div class="imgdt-live-list">
			<h2><?php esc_html_e( 'Recently active', 'imagify-devtools' ); ?></h2>
			<?php if ( ! $recent ) : ?>
				<p class="description"><?php esc_html_e( 'No optimizations recorded.', 'imagify-devtools' ); ?></p>
			<?php else : ?>
				<ul>
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

		<div class="imgdt-live-detail">
			<?php if ( ! $media_id ) : ?>
				<p class="description"><?php esc_html_e( 'Select an image.', 'imagify-devtools' ); ?></p>
			<?php else : ?>
				<h2><?php echo esc_html( get_the_title( $media_id ) ?: '#' . $media_id ); ?></h2>
				<table class="widefat striped imgdt-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Time', 'imagify-devtools' ); ?></th>
							<th><?php esc_html_e( 'Step', 'imagify-devtools' ); ?></th>
							<th><?php esc_html_e( 'Detail', 'imagify-devtools' ); ?></th>
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

	function cell( text ) {
		var td = document.createElement( 'td' );
		td.textContent = text;
		return td;
	}

	function poll() {
		fetch( url, { credentials: 'same-origin' } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( data ) {
				var rows = document.createDocumentFragment();

				( data.events || [] ).forEach( function ( event ) {
					var row = document.createElement( 'tr' );
					var detail = event.detail && Object.keys( event.detail ).length
						? JSON.stringify( event.detail )
						: '';

					row.appendChild( cell( event.time ) );
					row.appendChild( cell( event.step ) );
					row.appendChild( cell( detail ) );
					rows.appendChild( row );
				} );

				body.textContent = '';
				body.appendChild( rows );
			} )
			.catch( function () {} );
	}

	poll();
	setInterval( poll, 2000 );
} )();
</script>
<?php endif; ?>
