<?php
/**
 * One diagnostics section rendered for print (no collapsible markup).
 *
 * @var IMGDT_Diagnostic_Section $section
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;
?>
<h3><?php echo esc_html( $section->title ); ?></h3>
<table class="widefat imgdt-table">
	<tbody>
		<?php foreach ( $section->rows as $row ) : ?>
			<?php echo IMGDT_View::row( $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- IMGDT_View::row() escapes internally. ?>
		<?php endforeach; ?>
	</tbody>
</table>
