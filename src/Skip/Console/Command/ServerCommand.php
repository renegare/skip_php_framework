<?php

    namespace Skip\Console\Command;
    
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use Skip\ServiceContainerInterface;

    class ServerCommand extends Command implements ServiceContainerInterface
    {

        protected $app;

        public function setContainer( \Pimple $app )
        {
            $this->app = $app;
        }
        protected function configure()
        {
            $this
                ->setName('server:skip')
                ->setDescription('Development server')
            ;
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $i = 0;

            $app = function ($request, $response) use (&$i, $output) {
                $i++;
                $text = "This is request number $i.\n";
                $headers = array('Content-Type' => 'text/plain');
                $output->writeln($text);
                $response->writeHead(200, $headers);
                $response->end($text);
            };

            $loop = \React\EventLoop\Factory::create();
            $socket = new \React\Socket\Server($loop);
            $http = new \React\Http\Server($socket);
            $http->on('request', $app);

            $socket->listen(1337);
            $loop->run();
        }
    }
