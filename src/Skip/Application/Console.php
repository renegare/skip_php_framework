<?php

    namespace Skip\Application;

    use Skip\Config;
    use Symfony\Component\Console\Application as SymfonyConsole;
    use Symfony\Component\Console\Input\StringInput;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use Skip\Utils;
    use Skip\Util\Environment;
    use Symfony\Component\Console\Input\ArgvInput;

    class Console extends SymfonyConsole implements ApplicationWrapperInterface {

        protected $app;

        // @TODO need to add ability to run console in test environment without change environment variables
        public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN', $configCache=false){
            parent::__construct($name, $version);
            $this->cacheConfig = $configCache ? true : false;

            $this->configure(new ArgvInput());

        }

        private function configure( InputInterface $input )
        {

            preg_match_all( '/(\\-\\-skip\\-[a-zA-Z0-9\\.\\-\\/=\\"\']+)/', (string) $input, $matches);

            $stringInput = new StringInput( implode(' ', $matches[0] ) );
            $stringInput->bind( $this->getDefinition() );
            extract( Environment::getVars( $stringInput ) );

            $app = new Generic($app_path, $lib_path, $env, $user, 'console', $this->cacheConfig);
            $config = $app->getConfig();
            
            // add helper sets
            if( isset( $config['console']['helpers'] ) && is_array($config['console']['helpers']) ) {

                $set_array = array();

                foreach( $config['console']['helpers'] as $name => $helper ) {
                    $helper = new $helper;
                    $set_array[$name] = $helper->getHelper( $app, $this );
                }

                $helperSet = new \Symfony\Component\Console\Helper\HelperSet($set_array);
                $this->setHelperSet($helperSet);
            }

            // add commands
            if( isset( $config['console']['commands'] ) && is_array($config['console']['commands']) ) {

                foreach( $config['console']['commands'] as $command ) {

                    $sets = array();
                    if( !is_string( $command ) ) {
                        if( !isset( $command['class'] ) ) {
                            throw new \Exception('Commands with extra configuration needs a `class` parameter. No class parameter found.');
                        }
                        $sets = isset( $command['set'] )? $command['set'] : $sets;
                        if( !is_array($sets) ) {
                            throw new \Exception('Commands with a `sets` parameter must be a hash array of paramerter => service, where parameter is the un-camelised key paramerter and service is the key identifier of the dependant service.');
                        }
                        $command = $command['class'];
                    }

                    $command = new $command;

                    Utils::setParametersOn( $command, $sets, $app );

                    if( $command instanceof \Skip\ServiceContainerInterface ) {
                        $command->setContainer( $app );
                    }

                    $this->add( $command );

                }
            }

            $this->app = $app;
        }

        /**
         * {@inheritdoc}
         */
        public function getApp()
        {
            return $this->app;
        }

        /**
         * {@inheritdoc}
         */
        public function setApp( \Pimple $app ) {
            $this->app = $app;
        }

        /**
         * {@inheritdoc}
         */
        protected function getDefaultInputDefinition()
        {
            $definition = parent::getDefaultInputDefinition();
            $definition->addOptions(array(
                new InputOption('skip-user', '', InputOption::VALUE_REQUIRED, "Set skip user variable to run under." ),
                new InputOption('skip-env', '', InputOption::VALUE_REQUIRED, "Set skip environment variable to run under." ),
                new InputOption('skip-app-path', '', InputOption::VALUE_REQUIRED, "Set base path of app." )
            ));
            return $definition;
        }

        /**
         * {@inheritdoc}
         */
        public function doRun(InputInterface $input, OutputInterface $output)
        {
            return parent::doRun( $input, $output );
        }
        
    }