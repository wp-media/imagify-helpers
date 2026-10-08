/**
 * Imagify DevTools admin behaviour.
 *
 * No build step: plain ES2017+, runs as-is in every browser WP admin supports.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var toggle = document.querySelector( '.imgdt-toggle-all' );

		if ( ! toggle ) {
			return;
		}

		var labels = window.imgdtAdmin || {};

		toggle.addEventListener( 'click', function () {
			var sections = document.querySelectorAll( '.imgdt-section' );
			var expand   = 'collapsed' === toggle.getAttribute( 'data-state' );

			sections.forEach( function ( section ) {
				section.open = expand;
			} );

			toggle.setAttribute( 'data-state', expand ? 'expanded' : 'collapsed' );
			toggle.textContent = expand
				? ( labels.collapseAll || 'Collapse all' )
				: ( labels.expandAll || 'Expand all' );
		} );
	} );
} )();
