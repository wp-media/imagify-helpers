<?php
/**
 * System tab: every diagnostic section, collapsible, error sections auto-expanded.
 *
 * @var IMGDT_Diagnostics_Registry $registry
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

$sections = $registry->get_sections();
?>
<p class="description imgdt-toolbar">
	<?php esc_html_e( 'Sections with a flagged row are expanded.', 'imagify-devtools' ); ?>
	<button type="button" class="button button-small imgdt-toggle-all" data-state="collapsed"><?php esc_html_e( 'Expand all', 'imagify-devtools' ); ?></button>
</p>

<?php foreach ( $sections as $section ) : ?>
	<?php require IMGDT_VIEWS_PATH . 'partials/section-collapsible.php'; ?>
<?php endforeach; ?>
