<?php

    namespace Skip\Service;

    use Symfony\Component\Translation\Translator;
    use Symfony\Component\Translation\Loader\XliffFileLoader;

    class TranslatorService extends Translator
    {
        protected $config;

        public function __construct( $config=array() )
        {
            // @TODO: needs to be more configurable
            parent::__construct( $config['fallback'] );

            $this->addLoader('xlf', new XliffFileLoader());

            foreach( $config['resources'] as $resource )
            {
                extract($resource);
                $this->addResource($format, $resource, $locale, $domain);
            }

        }
    }