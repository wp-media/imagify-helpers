<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

global $wp_list_table;

$paged_page_url = $wp_list_table->paged_page_url();
$delete_url     = $this->delete_log_url( $this->current_log_id, $wp_list_table->page_url() );
?>
<div class="imgt-log-content">
	<div class="imgt-log-content-header imgt-section-primary">
		<div class="imgt-flex">
			<p class="imgt-log-title">
				<?php _e( 'Log Details', 'imagify-tools' ); ?>
			</p>
			<p class="imgt-action-links">
				<a class="imgt-action-link imgt-button imgt-button-secondary imgt-button-mini imgt-delete-log" href="<?php echo esc_url( $delete_url ); ?>">
					<span class="text"><?php _e( 'Delete log', 'imagify-tools' ); ?></span>
				</a>
			</p>
		</div>
		<a class="imgt-button imgt-button-ternary imgt-button-mini close" href="<?php echo esc_url( $paged_page_url ); ?>">
			<span class="text"><?php _e( 'Close', 'imagify-tools' ); ?></span>
		</a>
	</div>
	<div class="imgt-log-content-message">
		<?php echo $this->current_log->get_message(); ?>
	</div>
</div><!-- .imgt-log-content -->
