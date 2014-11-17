<?php
    
    namespace Skip;

    use Skip\Exceptions\InvalidClassMethodException;
    use Silex\Application as WebApplication;

    class Utils {

        public static function recursiveRemoveDirectory($directory, $empty=FALSE)
        {
            if(substr($directory,-1) == '/')
            {
                $directory = substr($directory,0,-1);
            }

            if (!file_exists($directory) || !is_dir($directory))
            {
                return FALSE;
            } elseif (is_readable($directory))
            {
                $handle = opendir($directory);

                while (FALSE !== ($item = readdir($handle)))
                {
                    if($item != '.' && $item != '..')
                    {
                        $path = $directory.'/'.$item;
                        if(is_dir($path)) 
                        {
                            recursive_remove_directory($path);
                        } else {
                            unlink($path);
                        }
                    }
                }
                closedir($handle);
                if($empty == FALSE)
                {
                    if(!rmdir($directory))
                    {
                        return FALSE;
                    }
                }
            }
            return TRUE;
        }

        public static function toArray($obj) {
            if(is_object($obj)) $obj = (array) $obj;
            if(is_array($obj)) {
                $new = array();
                foreach($obj as $key => $val) {
                    $new[$key] = self::toArray($val);
                }
            } else { 
                $new = $obj;
            }
            return $new;
        }

        public static function traverseArray( array $array, \Closure $callback, $level = 0, $base_path = '', $delimiter='.' ) {

            foreach($array as $key => &$value) {
                if( is_array( $value ) ) {
                    $array[$key] = self::traverseArray( $value, $callback, $level + 1, ( $base_path?  $base_path.$delimiter : '') . $key );
                } else {
                    try{
                        $array[$key] = $callback( $key, $value, $level, $base_path );   
                    } catch ( \Exception $e ) {
                        throw $e;
                    }
                }
            }

            return $array;
        }

        public static function arrayExtend ( &$first_array ) {

            $arrays = func_get_args();

            if( !count($arrays) ) \Exception('You must pass in at least one array');

            $array = count( $arrays ) ? array_shift( $arrays ) : array() ;

            $is_assoc = function ($a){
                if( is_array($a) && count( $keys = array_keys($a) ) ) {
                    return (bool) ( implode(',',$keys) !==  implode(',', array_keys($keys) ) );
                }
                return false;
            };


            $extender = function( $a1, $a2 ) use ($is_assoc) {

                if( $is_assoc($a2) ) {
                    foreach( $a2 as $key => $value ) {

                        if( isset($a1[$key]) && $is_assoc( $a1[$key] ) && $is_assoc( $value ) ){

                            $a1[$key] = Utils::arrayExtend( $a1[$key], $value );


                        } else {
                            $a1[$key] = $value;
                        }

                    }
                } else {
                    $a1 = $a2;
                }

                return $a1;
            };

            foreach( $arrays as $extendee ) {

                $array = $extender( $array, $extendee );

            }

            return $array;
        }

        public static function getClosure( $_method, \Closure $intialise_callback = null ) {

            $method = trim( $_method );

            if( preg_match('/^([A-Za-z0-9_\\\\]+)::([A-Za-z0-9_\\\\]+)$/', $method, $matches ) ) {
                // handle class method
                // @TODO: handle possible fatal errors
                // @TODO: currently wasteful creating a new instance of an object. Should cache object and method
                // if they happen to be used again for something else
                $object = new $matches[1];
                if( $intialise_callback )
                {
                    $intialise_callback( $object );
                }
                $method = new \ReflectionMethod( $object, $matches[2]);
                $method = $method->getClosure( $object );

            }

            if( !is_object($method) ) {
                throw new InvalidClassMethodException( sprintf("Cannot find class method '%s", $_method ) );
            }

            return $method;

        }

        public static function getArrayValue( &$array, $path, $delimiter = '.' ) {
            $steps = explode( $delimiter, $path );
            $value = $array;
            $key = '';

            $debug=false;
            if( $path == 'sskip.settings.db.options' ) {
                $debug = true;
            }

            foreach( $steps as $step ) {
                $key .= $step;
                if( $debug ) echo "Step: $key\n";
                if( isset($value[$key]) ) {
                    $value = $value[$key];
                    $key = '';
                } else {
                    if( $debug ) echo "UnFound Step: $key\n";
                    $key .= $delimiter;
                }
            }

            if( $debug ) {
                print_r( $value );
            }

            //@TODO: throw exception if no value found
            return $value;
            return $key? null : $value;
        }

        public static function setArrayValue( array &$array, $path, $value, $delimiter='.', $silent = false ) {
            $path = "['" . implode( "']['", explode( $delimiter, $path ) ) . "']";

            //@TODO is there a better way to do this?
            if( eval( 'return isset($array' . $path . ');' ) ) {
                eval( '$array' . $path . ' = $value;' );
            } else {
                if( $silent ) return null;
                throw new \Exception( sprintf('The path "%s" does not exist.', $path) );
            }

        }

        public static function setParametersOn( $object, $mappings, $hash_stack ) {
            $reflect = new \ReflectionClass($object);
            $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC );

            foreach( $mappings as $param => $dep_key ) {
                // check if $dep_key actually exists!

                // check if its a public parameter
                try{
                    $prop = $reflect->getProperty( $param );
                } catch ( \ReflectionException $e ) { 
                    $prop = null;
                }

                try{
                    $magic_prop = $reflect->getMethod( '__set' );
                } catch ( \ReflectionException $e ) { 
                    $magic_prop = null;
                }

                if( ($prop && $prop->isPublic()) || ( $magic_prop && $magic_prop->isPublic() ) ) {
                    $object->$param = Utils::getArrayValue( $hash_stack, $dep_key);
                    continue;
                }

                // check if its a public set* or magic __call method
                $call = 'set'.ucwords( preg_replace('/_+/', ' ', strtolower($param) ) );
                $call = preg_replace('/\s+/', '', $call);

                try{
                    $method = $reflect->getMethod( $call);
                } catch ( \ReflectionException $e ) { 
                    $method = null;
                }

                try{
                    $magic_method = $reflect->getMethod( '__call' );
                } catch ( \ReflectionException $e ) { 
                    $magic_method = null;
                }

                if( ( $method && $method->isPublic() ) || ( $magic_method && $magic_method->isPublic() ) ) {
                    $object->$call( Utils::getArrayValue( $hash_stack, $dep_key) );
                    continue;
                }

                // else throw error!
                throw new \Exception( sprintf('Unable to set param %s on object of type `%s`', $param, get_class( $object ) ) );
            }
        }

    }