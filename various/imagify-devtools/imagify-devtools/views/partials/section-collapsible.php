<?php
/**
 * One collapsible diagnostics section.
 *
 * @var IMGDT_Diagnostic_Section $section
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

$error_count = $section->error_count();
?>
<details class="imgdt-section" id="<?php echo esc_attr( IMGDT_View::section_anchor( $section->id ) ); ?>" <?php echo $error_count > 0 ? 'open' : ''; ?>>
	<summary class="imgdt-section-summary">
		<span class="imgdt-section-title"><?php echo esc_html( $section->title ); ?></span>
		<?php if ( $error_count > 0 ) : ?>
			<span class="imgdt-badge imgdt-badge-error"><?php echo esc_html( $error_count ); ?></span>
		<?php endif; ?>
	</summary>

	<table class="widefat striped imgdt-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Test', 'imagify-devtools' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Current value', 'imagify-devtools' ); ?></th>
				<th scope="col"><?php esc_html_e( 'More info', 'imagify-devtools' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $section->rows as $row ) : ?>
				<?php echo IMGDT_View::row( $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- IMGDT_View::row() escapes internally. ?>
			<?php endforeach; ?>
		</tbody>
	</table>
</details>
