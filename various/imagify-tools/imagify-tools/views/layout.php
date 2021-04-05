<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
<div class="wrap imgt-wrap"<?php $this->render_language_attributes(); ?>>
	<?php
	// Page title.
	$this->render_title();

	// Page content.
	$this->render_content();

	// Area to test things.
	$this->render_tests_area();
	?>
</div><!-- .wrap -->
