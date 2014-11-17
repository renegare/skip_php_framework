<?php

	namespace Skip\Tests\Dummy;


	use Symfony\Component\Console\Command\Command as Command;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Output\OutputInterface;

	use Skip\ServiceContainerInterface;

	class TestHelperCommand extends Command implements ServiceContainerInterface {

		protected $service_container;

	    protected function configure()
	    {
	        $this
	            ->setName('test:helper')
	            ->setDescription('Greet someone (using a helper)')
	            ->addArgument(
	                'name',
	                InputArgument::OPTIONAL,
	                'Who do you want to greet?'
	            )
	        ;
	    }

		public function setContainer( \Pimple $pimple ) {
			$this->service_container = $pimple;
		}

	    protected function execute(InputInterface $input, OutputInterface $output)
	    {
	        $name = $this->getHelper('helper_1')->convert( $input->getArgument('name') );
	        $tmpl = isset( $this->service_container['settings']['hello_greeting'] )? $this->service_container['settings']['hello_greeting'] : 'Hello %s';

	        $text = sprintf( $tmpl, $name? $name : 'you!' );

	        $output->writeln($text);
	    }
	}