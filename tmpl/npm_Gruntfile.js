'use strict'

var path = require('path');
var request = require('request');
var lrSnippet = require('grunt-contrib-livereload/lib/utils').livereloadSnippet;
var folderMount = function folderMount(connect, point) {
    return connect.static(path.resolve(point));
};

module.exports = function(grunt) {

    var skipConfig = {
        app_public: 'app/public',
        app_public_build: 'app/public/assets/build',
        app_public_src: 'app/public/assets/src',

        app_views: 'app/views',
        dev_server_port: 3000
    };

    // Project configuration.
    grunt.initConfig({

        skip: skipConfig,

        watch: {
            livereload: {
                files: [
                    skipConfig.app_public + '/assets/.tmp/assets/src/css/**/*.css',
                    skipConfig.app_public + '/**/*.html',
                    skipConfig.app_views + '/**/*.twig'
                ],
                tasks: ['livereload']
            },

            compass: {
                files: [
                    skipConfig.app_public_src + '/sass/**/*.scss',
                    skipConfig.app_public_src + '/sass/**/*.sass',
                ],
                tasks: ['compass:dev']
            }
        },

        bgShell: {
            php_dev_server: {
                cmd: 'php -S 0.0.0.0:<%= connect.dev.options.port %> -t <%= skip.app_public %> <%= skip.app_public %>/index.php',
                bg: true
            },

            migration: {
                cmd: 'bin/skip migrations:generate --editor-cmd=subl'
            },

            migration_migrate: {
                cmd: 'bin/skip migrations:migrate -n' 
            }
        },

        compass: {
            dev: {
                options: {
                    basePath: '<%= skip.app_public_src %>',
                    sassDir: 'sass',
                    imagesDir: 'images',
                    cssDir: 'css',
                    relativeAssets: true,
                    debugInfo: true
                }
            },
            dev_optimized: {
                options: {
                    basePath: '<%= skip.app_public_src %>',
                    sassDir: 'sass',
                    imagesDir: 'images',
                    cssDir: 'css',
                    relativeAssets: true,
                    debugInfo: false,
                    outputStyle: 'compressed'
                }
            },
            build: {
                options: {
                    sassDir: '<%= skip.app_public_src %>/sass',
                    imagesDir: '../images',
                    cssDir: '<%= skip.app_public_build %>/css',
                }
            }
        },

        requirejs: {
            build:{
                options: {
                    mainConfigFile: "<%= skip.app_public_src %>/js/require_config.js",
                    removeCombined: true,
                    almond: true,
                    wrap: true,
                    baseUrl: "<%= skip.app_public_src %>/js/lib",
                    modules: [], // this will be populated before test is run
                    dir: "<%= skip.app_public_build %>/js",
                    fileExclusionRegExp: /require\.js/
                }
            }
        },

        imagemin: {                          // Task
            build: {                            // Target
                options: {                       // Target options
                    optimizationLevel: 3
                },

                files: [
                    {
                        expand: true,
                        cwd: '<%= skip.app_public_src %>',
                        src: ['**/*.png'],
                        dest: '<%= skip.app_public_build %>'
                    },
                    {
                        expand: true,
                        cwd: '<%= skip.app_public_src %>',
                        src: ['**/*.jpg'],
                        dest: '<%= skip.app_public_build %>'
                    }
                ]
            }
        },

        revPackage: {
            build: '<%= skip.app_public_build %>',
        },

        clean: {
            build: ['<%= skip.app_public_build %>']
        },

        copy: {
            fonts: {
                files: [
                    {
                        expand: true,
                        cwd: '<%= skip.app_public_src %>',
                        src: ['fonts/**'],
                        dest: "<%= skip.app_public_build %>/"
                    },
                ]
            },

            build: {
                files: [
                    {
                        expand: true,
                        cwd: '<%= skip.app_public_build %>',
                        src: ['**'],
                        dest: "<%= skip.app_public_build + '.' + grunt.file.readJSON('package.json').version %>/"
                    }
                ]
            }
        },

        open: {
            server: {
                path: 'http://127.0.0.1:'+skipConfig.dev_server_port
            }
        }
    });

    // used for the dev server
    grunt.loadNpmTasks('grunt-contrib-connect');
    grunt.loadNpmTasks('grunt-contrib-livereload');
    grunt.loadNpmTasks('grunt-contrib-compass');
    grunt.loadNpmTasks('grunt-contrib-imagemin');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-regarde');
    grunt.loadNpmTasks('grunt-bg-shell');
    grunt.loadNpmTasks('grunt-requirejs');
    grunt.loadNpmTasks('grunt-bump');
    grunt.loadNpmTasks('grunt-rev-package');
    grunt.loadNpmTasks('grunt-open');


    // Default task(s).
    grunt.registerTask('default', ['server']);

    grunt.registerTask('noop', function(){
        console.log('YOU CHANGED SOMETHING!!!');
    });

    grunt.renameTask('regarde', 'watch');

    grunt.registerTask('server', 'Run a development server locally', function(){

        console.log('STARTING DEV SERVER');

        grunt.task.run([
            'bgShell:php_dev_server',
            'compass:dev',
            // 'livereload-start',
            'open:server',
            'watch'
        ]);
    });


    grunt.registerTask('migration', 'Quick migration commands', function( target ) {
        switch ( target ) {
            case 'migrate':
                grunt.task.run([
                    'bgShell:migration_migrate'
                ]);
                break;
            default:
                grunt.task.run([
                    'bgShell:migration'
                ]);
        }
    });

    grunt.registerTask('build', 'Build assets for production', function( bump_target ){

        var requirejs_modules = grunt.util._.map( grunt.file.expand({
            cwd: skipConfig.app_public_src + '/js',
        }, 'app/pages/*.js' ), function( module ) {
            var module_id = module.replace(/\.js$/, '');
            return {
                    name: module_id,
                    insertRequire: [ module_id ],
                };
        });

        grunt.config.set('requirejs.build.options.modules', requirejs_modules );

        grunt.task.run([
            'requirejs:build', 
            'compass:build', 
            'imagemin:build',
            'copy:fonts',
            'copy:build',
            'clean:build'
        ]);

    });
};