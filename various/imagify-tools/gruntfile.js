/* globals module: true, require: false */

module.exports = function( grunt ) {
	grunt.initConfig( {
		// PostCSS: Autoprefixer.
		'postcss': {
			'options': {
				'processors': [
					require( 'autoprefixer' )( {
						'browsers': 'last 3 versions'
					} )
				]
			},
			'target': {
				'files': [ {
					'expand': true,
					'cwd':    'assets/css',
					'src':    [ '*.css', '!*.min.css' ],
					'dest':   'assets/css',
					'ext':    '.min.css'
				} ]
			}
		},
		// CSS minify.
		'cssmin': {
			'options': {
				'shorthandCompacting': false,
				'roundingPrecision':   -1
			},
			'target': {
				'files': [ {
					'expand': true,
					'cwd':    'assets/css',
					'src':    [ '*.min.css' ],
					'dest':   'assets/css',
					'ext':    '.min.css'
				} ]
			}
		}
	} );

	/**
	 * Allow local configuration. For example:
	 * {
	 *   "copy": {
	 *     "whatever": {
	 *       "files": [ { "cwd": "/absolute/path/to/a/local/directory" } ]
	 *     }
	 *   }
	 * }
	 */
	if ( grunt.file.exists( 'gruntlocalconf.json' ) ) {
		grunt.config.merge( grunt.file.readJSON( 'gruntlocalconf.json' ) );
	}

	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-postcss' );

	// Our custom tasks.
	grunt.registerTask( 'css',    [ 'postcss', 'cssmin' ] );
	grunt.registerTask( 'minify', [ 'postcss', 'cssmin' ] );
};
