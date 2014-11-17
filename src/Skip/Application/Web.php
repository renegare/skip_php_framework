<?php

    namespace Skip\Application;

    use Skip\Config;
    use Skip\Utils;
    use Silex\Application;
    use Skip\Exceptions\InvalidRouteConfigurationException;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    use Skip\Util\Environment;
    
    class Web extends Application implements ApplicationInterface {

        /**
         * Instantiate a new Web.
         *
         * Objects and parameters can be passed as argument to the constructor.
         *
         * @param array $values The parameters or objects.
         */
        public function __construct(array $values = array(), $appPath=null, $user=null, $env=null, $cache=false) {

            parent::__construct($values);

            $appPath = Environment::getAppPath( null, $appPath );
            $env = Environment::getENV( null, $env );
            $user = Environment::getUser( null, $user );

            $this->configure( $appPath, $user, $env, $cache ? true : false );
        }

        /**
         * {@inheritdoc}
         */
        protected function configure( $appPath, $user, $env, $cache ) {

            $libPath = Environment::getLibPath();
            $sapi = 'public';

            $rawConfig = Generic::configure( $this, $appPath, $libPath, $env, $user, 'web', $cache );
            
            $sapiConfig = $rawConfig['web'];

            $hooks = null;
            // procedural configure after
            if( isset( $sapiConfig['middleware'] ) ) {
                $hooks = $sapiConfig['middleware'];
            }

            if( isset( $hooks['setup_before'] ) ) {
                try {
                    $method = Utils::getClosure( $hooks['setup_before'] );
                } catch ( \Exception $e ) {
                    throw new InvalidConfigurationException( sprintf('Your hooks configuration for \'setup_before\' is invalid. %s', $e->getMessage() ) );
                }

                $method( $this );
            }
            
            // configure providers
            if( isset( $sapiConfig['provider'] ) && is_array( $sapiConfig['provider'] ) ) {
    
                foreach( $sapiConfig['provider'] as $provider_name => $provider ) { 
                    ;
                    if( !isset( $provider['types'] ) ){
                        throw new InvalidRouteConfigurationException( sprintf('Your provider configuration for %s is invalid. You need to provide a \'types\' key to specify the types for the provider', $provider_name ) );
                    }

                    if( !is_array( $provider['types'] ) ){
                        $provider['types'] = array( $provider['types'] );
                    }

                    if (in_array('service', $provider['types'])) { 
                        if( !isset( $provider['class'] ) ){
                            throw new InvalidRouteConfigurationException( sprintf('Your provider configuration for %s is invalid. You need to provide a \'class\' key to specify the class for the provider', $provider_name ) );
                        }

                        $params = array(); 
                        if( isset( $provider['params'] ) 
                            && is_array( $provider['params'] ) 
                            && count( $provider['params'] ) > 0 ) { 
                            $params = $provider['params'];
                        }

                        $this->register(new $provider['class'], $params);
                    }

                    if (in_array('controller', $provider['types'])) {

                        if( !isset( $provider['class'] ) ){
                            throw new InvalidRouteConfigurationException( sprintf('Your provider configuration for %s is invalid. You need to provide a \'class\' key to specify the class for the provider', $provider_name ) );
                        }

                        if( !isset( $provider['path'] ) ){
                            $provider['path'] = '';
                        }

                        $this->mount($provider['path'], new $provider['class'], $params);

                    }

                }

            }

            // configure Application middleware
            if( isset( $hooks['webapp'] ) ) {
                if( !is_array( $hooks['webapp'] ) ) {
                    throw new InvalidConfigurationException( 'Your webapp hooks configuration is invalid. Please provide an array of functions to call.' );
                }

                foreach( $hooks['webapp'] as $hook ) {
                    $this->$hook['type']( Utils::getClosure( $hook['method'] ), isset( $hook['priority'] )? intval($hook['priority']) : null );
                }
            }

            if( isset($sapiConfig['controllers']) && count( $controllers = $sapiConfig['controllers'] ) > 0 )
            {
                // configure Application controllers
                $this->registerControllers( $controllers );
            }

            // catches all exception
            if( isset( $sapiConfig['error_controllers'] ) ) 
            {
                $app = $this;
                $error_configure_callback = function( $object ) use ( $app ) {
                    if( $object instanceof \Skip\ServiceContainerInterface ) {
                        $object->setContainer( $app );
                    }
                };

                foreach( $sapiConfig['error_controllers'] as $error_controller )
                {
                    try {
                        // create closure of controller class function 
                        $method = Utils::getClosure( $error_controller, $error_configure_callback );

                        $this->error($method);
                    } catch ( \Exception $e ) {
                        print_r( $e->getMessage() );
                        throw new InvalidRouteConfigurationException( sprintf('Your defined error controller class is invalid. %s', $error_controller ) );
                    }
                }
            }

            if( isset( $hooks['setup_after'] ) ) {
                try {
                    $method = Utils::getClosure( $hooks['setup_after'] );
                } catch ( \Exception $e ) {
                    throw new InvalidConfigurationException( sprintf('Your hooks configuration for \'setup_after\' is invalid. %s', $e->getMessage() ) );
                }

                $method( $this );
            }
        }

        protected function registerControllers( $controllers, $mount='', $preffixName='' ) {

            $preffixName = trim( $preffixName );
            $preffixName = $preffixName? $preffixName . '-' : '';
            foreach( $controllers as $name => $controller ) {
                if( isset( $controller['mount'] ) ) {
                    $this->registerControllers( $controller['controllers'], $mount . $controller['mount'] . '/', $name );
                } else {
                    $this->registerController( $preffixName . $name, $controller, $mount );
                }

            }
        }

        protected function registerController( $name, $controller, $mount='' )
        {

            if( !isset( $controller['match'] ) ){
                throw new InvalidRouteConfigurationException( sprintf('Your route confugiration for %s is invalid. You need to provide a \'match\' key to match a url', $name ) );
            }

            if( !isset( $controller['controller'] ) ){
                throw new InvalidRouteConfigurationException( sprintf('Your route confugiration for %s is invalid. You need to provide a \'controller\' key that maps to a function or class method', $name ) );
            }

            try {

                // create closure of controller class function 
                $method = Utils::getClosure( $controller['controller'] );

            } catch ( \Exception $e ) {
                throw new InvalidRouteConfigurationException( sprintf('Your route configuration %s is invalid. %s', $name, $e->getMessage() ) );
            }

            $route = preg_replace( array('/\\/+/', '/\\/$/'), array('/', ''), $mount.$controller['match'] );
            $route = preg_replace( '/^\\//', '', $route );

            $route = $this->match( $route, $method );

            // bind route to name
            $route->bind($name);

            // handle any defined request methods
            if( isset( $controller['request_methods'] ) ){
                
                if( is_array( $controller['request_methods'] ) ) {
                    $methods = implode('|', $controller['request_methods'] );
                } else {
                    $methods = $controller['request_methods'];
                }

                //@TODO: check format of methods string and that it is a string else throw InvalidRouteConfigurationException
                $route->method( $methods );
            }

            // handle any defined converts
            if( isset( $controller['convert'] ) ){

                if( !is_array( $controller['convert']) ) {
                    throw new InvalidRouteConfigurationException( sprintf("Problem with your defined converts in route '%s' config. Please provide a key pair array e.g. \"param1\": \"\Class::convertMethod\"", $name) );
                }

                foreach( $controller['convert'] as $param => $convert_method ) {

                    try {
                        // create closure of controller class function 
                        $method = Utils::getClosure( $convert_method );
                    } catch ( \Exception $e ) {
                        throw new InvalidRouteConfigurationException( sprintf('Your defined convert for param %s in route configuration %s is invalid. %s', $param, $name, $e->getMessage() ) );
                    }

                    $route->convert( $param, $method );

                }

            }

            // handle any defined assertions
            if( isset( $controller['assert'] ) ){

                if( !is_array( $controller['assert']) ) {
                    throw new InvalidRouteConfigurationException( sprintf("Problem with your defined assertions in route '%s' config. Please provide a key pair array e.g. \"param1\": \"\Class::assertMethod\"", $name) );
                }

                foreach( $controller['assert'] as $param => $assertion ) {

                    $route->assert( $param, $assertion );

                }
            }
                    
            // handle any defined default values
            if( isset( $controller['default_values'] ) ){

                if( !is_array( $controller['default_values']) ) {
                    throw new InvalidRouteConfigurationException( sprintf("Problem with your defined default values in route '%s' config. Please provide a key pair array e.g. \"param1\": \"value\"", $name) );
                }

                foreach( $controller['default_values'] as $param => $value ) {
                    $route->value( $param, $value );

                }
            }

            if( isset( $controller['response_headers'] ) && is_array( $controller['response_headers'] ) ) {
                $route->after( function( Request $request, Response $response ) use ( $controller ) {
                    foreach( $controller['response_headers'] as $key => $value ) {
                        $response->headers->set( $key, $value );
                    }
                });
            }

            $app = $this;
            // configure Route middleware
            if( isset( $controller['middleware'] ) ) {
                if( !is_array( $controller['middleware'] ) ) {
                    throw new InvalidConfigurationException( sprintf('The defined middleware hooks configuration for route \'%s\' config is invalid. Please provide an array of functions to call.', $name ) );
                }

                foreach( $controller['middleware'] as $hook ) {
                    $route->$hook['type']( Utils::getClosure( $hook['method'], function( $object ) use ( $app ) {
                        if( $object instanceof \Skip\ServiceContainerInterface ) {
                            $object->setContainer( $app );
                        }
                    }));
                }
            }
        }

        /**
         * {@inheritdoc}
         */
        public function getApp()
        {
            return $this;
        }

    }