<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

global $wp_list_table;

// Maybe display a Log infos.
$this->display_current_log();
?>

<div class="imgt-logs-list">

	<?php $wp_list_table->views(); ?>

	<form id="posts-filter" method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

		<?php $wp_list_table->display(); ?>

	</form>
</div><!-- .imgt-logs-list -->
