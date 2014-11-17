<?php
    
    namespace Skip;

    use Skip\Config\JSONLoader;
    use Skip\Config\ExtendedConfigException;
    use Skip\Config\InfiniteConfigIncludeDetectedException;
    use Skip\Config\ConfigFileDoesNotExistException;
    use Skip\Config\SubLevelExtendConfigException;

    class Config {

        private static $registered_loaders = array();

        public static function load( $_file_path, $values = array(), $extra_config = array() ) {

            // load previous config, and merge them together :)
            $base_config = array();
            $shared_config = array();
            foreach( $extra_config as $config_path )
            {
                $base_config = Config::realLoad( $config_path, $values, array(), $shared_config, $base_config );
            }

            if( $_file_path ) {
                $data = self::realLoad($_file_path, $values, array(), $shared_config, $base_config);
            } else {
                $data = $base_config;
            }

            if( count( $shared_config ) ) {
                foreach( $shared_config as $path_to => $path_from ) {
                    Utils::setArrayValue( $data, $path_to,  Utils::getArrayValue( $data, $path_from ) );
                }
            }
            
            return $data;
        }

        public static function traverseConfig( $original_file_path, $data, $values, $imported_config, &$shared_config=array(), $basePath='' ) {

            $imported_config = array( $original_file_path );

            try {
                $data = Utils::traverseArray( $data, function( $key, $value, $level = 0, $base_path = '') use ( $values, $original_file_path, $data, $imported_config, &$shared_config ) {

                    $value = is_string( $value)? trim($value) : $value;
                    $key = is_string( $key)? trim($key) : $key;
                    if( $key === '@extend' ) {
                        if( $level !== 0 ) {
                            throw new SubLevelExtendConfigException( sprintf('You can only use @extend once in the first level of your config: %s', $original_file_path ) );
                        }
                        // construct path and resolve properly if relative
                        if( substr($value, 0,1) !== '/') {
                            $file_path = dirname( $original_file_path ) . '/' . $value;
                        } else {
                            $file_path = $value;
                        }

                        // load referenced config
                        $extend_data = Config::realLoad( $file_path, $values, $imported_config );

                        // merge current data
                        $extended_data = Utils::arrayExtend( $extend_data, $data );
                        
                        // remove @extend ... we don't want an infinite loop
                        if( isset( $extended_data['@extend'] ) ) {
                            unset( $extended_data['@extend'] );
                        }

                        // traverse new extended array 
                        $extended_data = Config::traverseConfig( $original_file_path, $extended_data, $values, $imported_config, $shared_config, $base_path );


                        // throw exception
                        throw new ExtendedConfigException('Config has been extended and traversed', $extended_data );

                    }

                    if( preg_match( '/^@import\s+(.+)$/', $value, $matches ) ) {
                        if( substr($matches[1], 0,1) !== '/') {
                            $file_path = dirname( $original_file_path ) . '/' . $matches[1];
                        } else {
                            $file_path = $matches[1];
                        }

                        $newBasePath = ( $base_path?  $base_path . '.' : '' ) . $key;

                        return Config::realLoad( $file_path, $values, $imported_config, $shared_config, array(), $newBasePath );

                    }

                    if(preg_match( '/^@clone\s+(.+)$/', $value, $matches )) {
                        //@TODO: figure out why a sinlge clone value is called mutiple times
                        //var_dump($matches[0]);
                        $shared_config[ ( $base_path?  "$base_path." : '') . $key ] = $matches[1];

                    }

                    return $value;

                }, 0, $basePath );
            } catch ( ExtendedConfigException $e ) {
                return $e->getConfig();
            }

            return $data;
        }

        public static function realLoad( $_file_path, $values = array(), $imported_config = array(), &$shared_config = array(), $base_config = array(), $basePath='' ) {

            self::registerDefaultLoaders();

            $file_path = realpath( $_file_path );

            if( !$file_path ) {
                throw new ConfigFileDoesNotExistException( sprintf('The following configuration file could not be found: %s', $_file_path) );
            }

            if( in_array( $file_path, $imported_config ) ){
                throw new InfiniteConfigIncludeDetectedException( sprintf('The following configuration file has already been included: %s', $file_path) );
            }

            $imported_config[] = $file_path;

            // detect loader type required
            $ext = preg_replace('/^.*\.([a-z]+)$/', '$1', $file_path );

            if( !isset( self::$registered_loaders[$ext] ) ) {
                throw new \Exception( sprintf('Unable to find appropriate loader for config file of type %s: %s', $ext, $file_path ) );
            }

            // load it 
            $data = self::$registered_loaders[$ext]->load( $file_path, $values );
            $extended_data = Utils::arrayExtend( $base_config, $data );

            // loop through each attribute and resolve any special attrs e.g @extend, @import, %...%
            $data = self::traverseConfig( $file_path, $data, $values, $imported_config, $shared_config, $basePath );

            return $data;
        }

        public static function registerDefaultLoaders() {

            // register JSON Loader
            if( !isset( self::$registered_loaders['json'] ) ) {
                self::$registered_loaders['json'] = new JSONLoader;
            }

            // @TODO: register YML Loader

            // @TODO: register XML Loader

            // @TODO: register INI Loader
        }

        public static function configurePimple( \Pimple $app, array $config ) {

            $skip = array();

            $skip['settings'] = isset( $config['settings'] )? $config['settings'] : array();    
            $skip['core'] = $config['core'];
            if( !isset($skip['core']['debug']) ) $skip['core']['debug'] = false;
            $app['debug'] = $skip['core']['debug'];
            
            // so lazy developers can do what they want #notElegant #butIEmpathise
            $skip['di'] = $app;
            
            // namespaced configuration
            $app['skip'] = $skip;

            // handy bits for the frame work to know about itself!
            if( isset( $config['framework'] ) && is_array( $config['framework'] ) )
            {
                $app['framework'] = $config['framework'];
            }

            // configure services
            if( isset( $config['services'] ) && is_array( $config['services'] ) ) {

                Config::configureServices( $app, $config['services'] );
            }
        }

        public static function configureServices( \Pimple $app, array $config ) {

            foreach( $config as $service_name => $service ) {

                $setup = function ( $service_name, $service ) use ( $app ) {

                    $arguments = '';
                    $deps = array();
                    if( isset( $service['dependencies'] ) 
                        && is_array( $service['dependencies'] ) 
                        && count($service['dependencies']) > 0 ) {

                        $deps = $service['dependencies'];
                    }

                    $class = $service['class'];

                    $sets = isset( $service['set'] )? $service['set'] : array();
                    if( !is_array($sets) ) {
                        throw new \Exception('Service with a `sets` parameter must be a hash array of paramerter => service, where parameter is the un-camelised key and service is the key identifier of the dependant service.');
                    }

                    $service_closure = function( \Pimple $app ) use ( $class, $deps, $sets, $service_name ) {
                        // instantiate
                        // $object = eval( $string );
                        $class = new \ReflectionClass( $class );
                        $arguments = array();

                        foreach( $deps as $key ) {
                            $arguments[] = Utils::getArrayValue( $app, $key );
                        }
                        $instance = $class->newInstanceArgs( $arguments );

                        if( $instance instanceof \Skip\ServiceContainerInterface ) {
                            $instance->setContainer( $app );
                        }
                        // handle any sets
                        try {
                            Utils::setParametersOn( $instance, $sets, $app );
                        } catch ( \Exception $e ) {
                            // make it clearer
                            throw new \Exception( 'Web Application initialisation of \'' . $service_name . '\' service: ' . $e->getMessage() );
                        }
                        return $instance;
                    };

                    $type = isset( $service['type'] ) ? $service['type'] : null;

                    switch ( $type ) {
                        case 'protect':
                            $app[ $service_name ] = $app->protect( $service_closure );
                            break;
                        case 'factory':
                            $app[ $service_name ] = $service_closure ;
                            break;
                        case 'share':
                        default:
                            $app[ $service_name ] = $app->share( $service_closure );
                            break;
                    }

                };

                $setup( $service_name, $service );

            }
        }

    }