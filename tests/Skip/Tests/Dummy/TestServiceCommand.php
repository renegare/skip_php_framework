<?php

namespace Skip\Tests\Dummy;

use Symfony\Component\Console\Command\Command as Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Skip\ServiceContainerInterface;

class TestServiceCommand extends Command {

    protected $service_container;

    protected function configure()
    {
        $this
            ->setName('test:set_test_service')
            ->setDescription('Test Setting Service')
        ;
    }

    public function setTestService( $test_service ){
        $this->test_service = $test_service;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->test_service->getData();

        $output->writeln('#Fini!');
    }
}