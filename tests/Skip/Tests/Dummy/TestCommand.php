<?php

namespace Skip\Tests\Dummy;

use Symfony\Component\Console\Command\Command as Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Skip\ServiceContainerInterface;

class TestCommand extends Command implements ServiceContainerInterface {

    protected $service_container;

    protected function configure()
    {
        $this
            ->setName('test:command')
            ->setDescription('Greet someone')
            ->addArgument( 'name', InputArgument::OPTIONAL, 'Who do you want to greet?')
        ;
    }

    public function setContainer( \Pimple $pimple ) {
        $this->service_container = $pimple;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $settings = $this->service_container['skip']['settings'];
        $tmpl = isset( $settings['hello_greeting'] )? $settings['hello_greeting'] : 'Hello %s';

        $text = sprintf( $tmpl, $name? $name : 'you!' );

        $output->writeln($text);
    }
}