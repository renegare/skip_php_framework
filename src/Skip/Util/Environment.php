<?php

    namespace Skip\Util;

    use Symfony\Component\Console\Input\Input;

    class Environment {

        public static function getVars( Input $input=null ) {

            $config = array(
                'user' => self::getUser( $input, '' ),
                'env' => self::getENV( $input, 'production' ),
                'app_path' => self::getAppPath( $input, './app' ),
                'lib_path' => self::getLibPath()
            );

            return $config;
        }

        public static function getAppPath( Input $input=null, $default='./app') {
            $appPath = self::getVariable( $input, $default, 'skip-app-path', 'SKIP_APP_PATH');

            $realPath = realpath( $appPath );
            if( !$realPath || !is_dir($realPath) ) {
                throw new Exception\AppPathNotFoundException( sprintf("App path '%s' does not exist!", $appPath ) );
            }
            return $realPath;
        }

        public static function getUser( Input $input=null, $default='') {
            return self::getVariable( $input, $default, 'skip-user', 'SKIP_USER');
        }

        public static function getENV( Input $input=null, $default='') {
            return self::getVariable( $input, $default, 'skip-env', 'SKIP_ENV');
        }

        public static function getLibPath() {
        	return dirname(dirname(dirname(__DIR__)));
        }

        public static function getVariable( Input $input=null, $default='', $optionName=false, $envName=false) {
        	$value=null;

            if( $optionName && $input && $input->hasOption( $optionName ) ) {
                $value = $input->getOption( $optionName );
            }

            if( $value === null ) {
                if( $envName ) $value = getenv( $envName );
                if( !$value ) {
                    $value = $default;
                }
            }

        	return $value === null? '' : $value;
        }

    }