<?php
/**
 * Page shell: title, tab nav, active tab content.
 *
 * Variables in scope (set by IMGDT_Admin_Pages::render_page()):
 *
 * @var IMGDT_Admin_Pages          $this
 * @var string                     $current     Active tab slug.
 * @var IMGDT_Diagnostics_Registry $registry
 * @var int                        $error_count
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;
?>
<div class="wrap imgdt-wrap">
	<h1 class="imgdt-title">
		<?php esc_html_e( 'Imagify DevTools', 'imagify-devtools' ); ?>
		<?php if ( $error_count > 0 ) : ?>
			<span class="imgdt-badge imgdt-badge-error"><?php echo esc_html( $error_count ); ?></span>
		<?php else : ?>
			<span class="imgdt-badge imgdt-badge-ok"><?php esc_html_e( 'All clear', 'imagify-devtools' ); ?></span>
		<?php endif; ?>
	</h1>

	<?php if ( isset( $_GET['imgdt_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag, no state change. ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'The action could not be completed.', 'imagify-devtools' ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper imgdt-tabs">
		<?php foreach ( $this->get_tabs() as $slug => $tab ) : ?>
			<a
				href="<?php echo esc_url( add_query_arg( array( 'page' => IMGDT_Admin_Pages::SLUG, 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"
				class="nav-tab<?php echo $current === $slug ? ' nav-tab-active' : ''; ?>"
			>
				<?php echo esc_html( $tab['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="imgdt-tab-content">
		<?php
		$imgdt_tab_view = IMGDT_VIEWS_PATH . 'tabs/' . $this->get_tabs()[ $current ]['view'];

		// A failing tab does not blank the title and tab nav.
		$imgdt_tab_ok = imgdt_safe(
			static function () use ( $imgdt_tab_view, $registry ) {
				require $imgdt_tab_view;
				return true;
			}
		);

		if ( ! $imgdt_tab_ok ) :
			?>
			<div class="imgdt-empty-state">
				<span class="dashicons dashicons-warning"></span>
				<p><?php esc_html_e( 'This tab could not be displayed.', 'imagify-devtools' ); ?></p>
			</div>
			<?php
		endif;
		?>
	</div>
</div>
