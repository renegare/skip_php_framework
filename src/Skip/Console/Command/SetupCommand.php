<?php

    namespace Skip\Console\Command;
    
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use Skip\ServiceContainerInterface;

    class SetupCommand extends Command implements ServiceContainerInterface
    {

        protected $app;

        public function setContainer( \Pimple $app )
        {
            $this->app = $app;
        }
        protected function configure()
        {
            $this
                ->setName('app:init')
                ->setDescription('Initialize application base structure. Use this only in development to get up and running. Not for production use!')
            ;
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $framework = $this->app['framework'];
            if( isset($framework['init']) )
            {

                $init = $framework['init'];

                if( isset($init['dirs']) )
                {
                    foreach( $init['dirs'] as $path )
                    {
                        if( !file_exists($path) )
                        {
                            mkdir($path, 0777, true);
                        }
                    }
                }

                if( isset($init['files']) )
                {
                    foreach( $init['files'] as $path )
                    {   
                        $src = $path['src'];
                        $dest = $path['dest'];
                        if( !file_exists($dest) && file_exists($src) )
                        {
                            copy($src, $dest);
                        }
                    }
                }
            }
        }
    }
