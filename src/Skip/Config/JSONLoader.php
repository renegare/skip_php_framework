<?php
    
    namespace Skip\Config;

    use Skip\Utils;
    use Skip\Config\AbstractLoader;
    use Seld\JsonLint\JsonParser;

    class JSONLoader extends AbstractLoader{

        public function load( $file_path, $values = array() )
        {
            if ( !file_exists($file_path) || false === ( $data = file_get_contents($file_path) ) ) {
                throw new RuntimeException(sprintf(
                    'Unable to read file: %s',
                    $file_path
                ));
            }

            $parser = new JsonParser();

            try {

                $data =  Utils::toArray( $parser->parse( $this->doReplace($data, $values), true ) );

            } catch ( \Exception $e) {

                throw new \Exception(sprintf(
                    'Unable to parse file %s: %s',
                    $file_path,
                    $e->getMessage()
                ));

            }

            return $data;
        }

        public function doReplace($text, $values)
        {
            // remove block level comments
            // @credits: http://stackoverflow.com/questions/643113/regex-to-strip-comments-and-multi-line-comments-and-empty-lines
            $text = preg_replace('!/\*.*?\*/!s', '', $text);
            $text = preg_replace('/\n\s*\n/', "\n", $text);
            return parent::doReplace( $text, $values );
        }


    }