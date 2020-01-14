<?php
/**
 * Plugin Name:  Imagify | No Auto-Optimization for PDFs
 * Description:  Excludes PDF files from being auto-optimized once they’re uploaded.
 * Plugin URI:   https://github.com/wp-media/imagify-helpers/tree/master/optimization/imagify-no-auto-optimize-pdf/
 * Version:      1.0.1
 * Requires PHP: 5.3
 * Author:       Imagify Support Team
 * Author URI:   http://imagify.io/
 * License:      GPLv2
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright 2020 WP Media
 *
 * @package WP-Media\ImagifyPluginHelpers\NoAutoOptimPDF
 */

namespace ImagifyPluginHelpers\Optimization\NoAutoOptimPDF;

defined( 'ABSPATH' ) || die();

add_filter( 'imagify_auto_optimize_attachment', __NAMESPACE__ . '\no_optimize_pdf', 10, 2 );
/**
 * Prevent automatic optimization for PDF.
 *
 * @since  1.0
 * @author Grégory Viguier
 * @author Caspar Hübinger
 *
 * @param  bool $optimize      True to optimize, false otherwise.
 * @param  int  $attachment_id Attachment ID.
 * @return bool
 */
function no_optimize_pdf( $optimize, $attachment_id ) {
	if ( ! $optimize ) {
		return false;
	}

	$mime_type = get_post_mime_type( $attachment_id );

	return 'application/pdf' !== $mime_type;
}
