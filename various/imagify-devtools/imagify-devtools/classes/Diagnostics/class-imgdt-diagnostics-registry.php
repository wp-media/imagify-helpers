<?php
/**
 * Collects diagnostic sections from every registered provider.
 *
 * @package Imagify_DevTools
 */

defined( 'ABSPATH' ) || die;

/**
 * @since 0.1.0
 */
class IMGDT_Diagnostics_Registry {

	/**
	 * @var IMGDT_Diagnostic_Section[]|null Lazily built, cached for the request.
	 */
	private ?array $sections = null;

	/**
	 * The default set of providers, in display order.
	 *
	 * @since 0.1.0
	 *
	 * @return IMGDT_Diagnostics_Provider[]
	 */
	private function default_providers(): array {
		$providers = array(
			new IMGDT_Provider_Filesystem(),
			new IMGDT_Provider_Imagify_Filesystem(),
			new IMGDT_Provider_Image_Editor(),
			new IMGDT_Provider_Curl(),
			new IMGDT_Provider_Requests(),
			new IMGDT_Provider_Media(),
			new IMGDT_Provider_Media_Stats(),
			new IMGDT_Provider_Various(),
		);

		/**
		 * Filters the list of diagnostics providers.
		 *
		 * Add a class implementing `IMGDT_Diagnostics_Provider` here to add a
		 * new section to the dashboard without touching this plugin's code.
		 *
		 * @since 0.1.0
		 *
		 * @param IMGDT_Diagnostics_Provider[] $providers
		 */
		return apply_filters( 'imgdt_diagnostics_providers', $providers );
	}

	/**
	 * Get every section, building them on first call.
	 *
	 * @since 0.1.0
	 *
	 * @return IMGDT_Diagnostic_Section[]
	 */
	public function get_sections(): array {
		if ( null !== $this->sections ) {
			return $this->sections;
		}

		$this->sections = array();

		foreach ( $this->default_providers() as $provider ) {
			// Each provider runs in isolation: one throwing (an Imagify API
			// that changed shape, an unusual host environment) must not
			// blank out every other section.
			$section = imgdt_safe( static fn() => $provider->get_section() );

			if ( ! $section instanceof IMGDT_Diagnostic_Section ) {
				$section = new IMGDT_Diagnostic_Section(
					strtolower( str_replace( array( 'IMGDT_Provider_', '_' ), array( '', '-' ), get_class( $provider ) ) ),
					get_class( $provider ),
					array(
						new IMGDT_Diagnostic_Row(
							__( 'This section could not be loaded', 'imagify-devtools' ),
							'',
							true,
							defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG
								? __( 'See the PHP error log for details.', 'imagify-devtools' )
								: __( 'Enable WP_DEBUG_LOG for details.', 'imagify-devtools' )
						),
					)
				);
			}

			if ( $section->rows ) {
				$this->sections[ $section->id ] = $section;
			}
		}

		return $this->sections;
	}

	/**
	 * Get every row currently flagged as an error, across all sections.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array{section: IMGDT_Diagnostic_Section, rows: IMGDT_Diagnostic_Row[]}>
	 */
	public function get_errors(): array {
		$errors = array();

		foreach ( $this->get_sections() as $section ) {
			$rows = $section->error_rows();

			if ( $rows ) {
				$errors[ $section->id ] = array(
					'section' => $section,
					'rows'    => $rows,
				);
			}
		}

		return $errors;
	}

	/**
	 * Count every error row, across all sections.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function get_error_count(): int {
		$count = 0;

		foreach ( $this->get_sections() as $section ) {
			$count += $section->error_count();
		}

		return $count;
	}
}
