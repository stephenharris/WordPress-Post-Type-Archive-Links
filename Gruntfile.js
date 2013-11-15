module.exports = function(grunt) {

  //Load all the tasks
  require('load-grunt-tasks')(grunt);
	
  // Project configuration.
  grunt.initConfig({
	pkg: grunt.file.readJSON('package.json'),
	copy: {
		// Copy the plugin to a versioned release directory
		main: {
			src:  [
				'**',
				'!node_modules/**',
				'!build/**',
				'!.git/**',
				'!vendor/**',
				'!Gruntfile.js',
				'!package.json',
				'!.gitignore',
				'!.gitmodules',
				'!*~',
				'!composer.lock',
				'!composer.phar',
				'!composer.json',
			],
			dest: 'build/<%= pkg.name %> /'
		}		
	},

	wp_readme_to_markdown: {
		convert:{
			files: {
				'readme.md': 'readme.txt'
			},
		},
	},
	
    checkrepo: {
    	deploy: {
            tag: {
                eq: '<%= pkg.version %>',    // Check if highest repo tag is equal to pkg.version
            },
            tagged: true, // Check if last repo commit (HEAD) is not tagged
            clean: true,   // Check if the repo working directory is clean
        }
    },

	clean: {
		//Clean up build folder
		main: ['build/<%= pkg.name %>']
	},

	copy: {
		// Copy the plugin to a versioned release directory
		main: {
			src:  [
				'**',
				'!node_modules/**',
				'!build/**',
				'!.git/**',
				'!tests/**',
				'!vendor/**',
				'!Gruntfile.js',
				'!package.json',
				'!.gitignore',
				'!.gitmodules',
				'!*~',
				'!composer.lock',
				'!composer.phar',
				'!composer.json',
			],
			dest: 'build/<%= pkg.name %>/'
		}		
	},

    wp_deploy: {
    	deploy:{
            options: {
        		svn_user: 'stephenharris',
        		plugin_slug: '<%= pkg.name %>',
        		build_dir: 'build/<%= pkg.name %>/'
            },
    	}
    }
});


grunt.registerTask( 'readme', [ 'wp_readme_to_markdown' ] );
  
grunt.registerTask( 'build', [ 'clean', 'copy' ] );

grunt.registerTask( 'deploy', [ 'checkbranch:master', 'checkrepo:deploy', 'build', 'wp_deploy'] ); //Deploy via svn

//TODO on pre-commit: test/uglify?
};
