<?php
/**
 * Class autoloader.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * Maps class names to file paths and registers an autoloader.
 *
 * Classes follow the `class-{name}.php` filename convention. The map is
 * explicit rather than directory-scanned so only known classes are loaded.
 *
 * @since 0.1.0
 */
class IMGDT_Loader {

	/**
	 * Class name => file path, relative to IMGDT_CLASSES_PATH.
	 *
	 * @var array<string, string>
	 */
	private static array $map = array(
		'IMGDT_Capability'                  => 'class-imgdt-capability.php',
		'IMGDT_Admin_Pages'                  => 'class-imgdt-admin-pages.php',
		'IMGDT_Admin_Post'                   => 'Ajax/class-imgdt-admin-post.php',
		'IMGDT_Cache'                        => 'class-imgdt-cache.php',
		'IMGDT_Tools'                        => 'class-imgdt-tools.php',
		'IMGDT_View'                         => 'class-imgdt-view.php',
		'IMGDT_Report'                       => 'class-imgdt-report.php',
		'IMGDT_Attachment_Metabox'           => 'class-imgdt-attachment-metabox.php',

		'IMGDT_Diagnostic_Row'               => 'Diagnostics/class-imgdt-diagnostic-row.php',
		'IMGDT_Diagnostic_Section'           => 'Diagnostics/class-imgdt-diagnostic-section.php',
		'IMGDT_Diagnostics_Registry'         => 'Diagnostics/class-imgdt-diagnostics-registry.php',
		'IMGDT_Diagnostics_Provider'         => 'Diagnostics/class-imgdt-diagnostics-provider.php',

		'IMGDT_Provider_Filesystem'          => 'Diagnostics/Providers/class-imgdt-provider-filesystem.php',
		'IMGDT_Provider_Imagify_Filesystem'  => 'Diagnostics/Providers/class-imgdt-provider-imagify-filesystem.php',
		'IMGDT_Provider_Image_Editor'        => 'Diagnostics/Providers/class-imgdt-provider-image-editor.php',
		'IMGDT_Provider_Curl'                => 'Diagnostics/Providers/class-imgdt-provider-curl.php',
		'IMGDT_Provider_Requests'            => 'Diagnostics/Providers/class-imgdt-provider-requests.php',
		'IMGDT_Provider_Media'               => 'Diagnostics/Providers/class-imgdt-provider-media.php',
		'IMGDT_Provider_Media_Stats'         => 'Diagnostics/Providers/class-imgdt-provider-media-stats.php',
		'IMGDT_Provider_Various'             => 'Diagnostics/Providers/class-imgdt-provider-various.php',

		'IMGDT_Triage_Card'                  => 'Triage/class-imgdt-triage-card.php',
		'IMGDT_Triage_Builder'               => 'Triage/class-imgdt-triage-builder.php',

		'IMGDT_Live_Store'                   => 'Live/class-imgdt-live-store.php',
		'IMGDT_Live_Tracer'                  => 'Live/class-imgdt-live-tracer.php',
		'IMGDT_Live_Page'                    => 'Live/class-imgdt-live-page.php',

		'IMGDT_Queue_Status'                 => 'Queue/class-imgdt-queue-status.php',
		'IMGDT_Queue_Purge'                  => 'Queue/class-imgdt-queue-purge.php',

		'IMGDT_Image_Editor_Override'        => 'Overrides/class-imgdt-image-editor-override.php',
	);

	/**
	 * Register the autoloader.
	 *
	 * @since 0.1.0
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload callback.
	 *
	 * @since 0.1.0
	 *
	 * @param string $class_name Class being requested.
	 */
	public static function autoload( string $class_name ): void {
		if ( ! isset( self::$map[ $class_name ] ) ) {
			return;
		}

		$file = IMGDT_CLASSES_PATH . self::$map[ $class_name ];

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
