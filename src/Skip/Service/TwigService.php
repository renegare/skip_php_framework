<?php

    namespace Skip\Service;

    use Symfony\Bridge\Twig\Form\TwigRendererEngine;
    use Symfony\Bridge\Twig\Extension\TranslationExtension;
    use Symfony\Bridge\Twig\Extension\FormExtension;
    use Symfony\Bridge\Twig\Form\TwigRenderer;
    use Symfony\Bridge\Twig\Extension\RoutingExtension;
    use Silex\Provider\TwigCoreExtension;
    use Skip\Exceptions\InvalidClassMethodException;

    class TwigService extends \Twig_Environment
    {
        protected $forms;
        protected $translator_service;
        protected $app;
        protected $context_transformers;

        public function __construct( $config, $app, $translator_service, $csrf_provider_service=null )
        {
            $this->translator_service = $translator_service;
            $this->csrf_provider_service = $csrf_provider_service;
            $this->app = $app;
            $this->context_transformers = isset( $config['context_transformers'] ) && is_array( $config['context_transformers'] )? $config['context_transformers'] : array();

            $cache_disabled = isset( $config['debug'] )? $config['debug'] : false;
            $cache_path = isset( $config['cache_path'] )? $config['cache_path'] : null;
            $paths = isset( $config['paths'] )? $config['paths'] : array();

            if( !$cache_disabled && !file_exists($cache_path) )
            {
                mkdir($cache_path, 0777, true);
            }

            $_config = array();

            if( !$cache_disabled && file_exists($cache_path) ) {
                $_config['cache'] = $config['cache_path'];
            }

            $loader = new \Twig_Loader_Filesystem($paths);
            parent::__construct($loader, $_config );

            // setup forms if form service is available in the app
            $this->initialiseExtensions( $config );
            $this->addGlobal('app', $app);
            $this->addExtension(new TwigCoreExtension());
            if (class_exists('Symfony\Bridge\Twig\Extension\RoutingExtension')) {
                if (isset($app['url_generator'])) {
                    $this->addExtension(new RoutingExtension($app['url_generator']));
                }
            }

            // setup filters defined in config
            if( isset( $config['filters'] ) ) {
                foreach( $config['filters'] as $name => $filter_config) {
                    $filter = $filter_config['function'];
                    if( !is_string( $filter ) ) {
                        try{
                            $function = Utils::getClosure( $filter );
                        } catch ( InvalidClassMethodException $e ) {
                            // noop: treat as native php function
                        }
                    }

                    $options = isset( $function_config['options'] )? $function_config['options'] : array();
                    $filter = new \Twig_SimpleFilter( $name, $filter, $options );
                    $this->addFilter($filter);
                }
            }

            // setup functions defined in config
            if( isset( $config['functions'] ) ) {
                foreach( $config['functions'] as $name => $function_config) {
                    $function = $function_config['function'];
                    if( !is_string( $function ) ) {
                        try{
                            $function = Utils::getClosure( $function );
                        } catch ( InvalidClassMethodException $e ) {
                            // noop: treat as native php function
                        }
                    }
                    $options = isset( $function_config['options'] )? $function_config['options'] : array();
                    $function = new \Twig_SimpleFunction( $name, $function, $options );
                    $this->addFunction($function);
                }
            }

        }

        protected function initialiseExtensions( $config )
        {
            if( isset($config['built_in_extentions']) )
            {
                $built_in_ext = $config['built_in_extentions'];
                if( in_array('form', $built_in_ext) )
                {
                    $formEngine = new TwigRendererEngine(array('form_div_layout.html.twig'));
                    $formEngine->setEnvironment($this);
                    $this->addExtension(new TranslationExtension($this->translator_service));
                    $this->addExtension(new FormExtension(new TwigRenderer($formEngine, $this->csrf_provider_service)));
                }
            }
        }

        protected function transformContext( $context ) {

            foreach( $this->context_transformers as $class ) {
                $method = \Skip\Utils::getClosure( $class . '::transform' );
                $context = $method( $this->app, $context );
            }

            return $context;
        }

        public function render($name, array $context = array())
        {

            return parent::render( $name, $this->transformContext( $context ) );
        }

        public function display($name, array $context = array())
        {
            return parent::display( $name, $this->transformContext( $context ) );
        }
    }
