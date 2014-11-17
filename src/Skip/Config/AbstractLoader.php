<?php
        
    namespace Skip\Config;

    abstract class AbstractLoader{

        abstract public function load( $file_path, $values = array() );

        public function doReplace($data, $values)
        {
            // replace any patterns #..# with values
            $lowerCasedValues = array();
            foreach( $values as $k => $v ) {
                $lowerCasedValues[strtolower($k)] = $v;
            }

            $data = preg_replace_callback(
                '/#([\w]+)#/',
                function ($matches) use ( $data, $lowerCasedValues ) {
                    $lowerCaseKey = strtolower($matches[1]);
                    // simple hack to get class constants working .. but not entirely sure why this has to be :(
                    if (isset($lowerCasedValues[$lowerCaseKey])) {
                        return $lowerCasedValues[$lowerCaseKey];
                    }
                },
                $data
            );

            return $data;
        }
    }