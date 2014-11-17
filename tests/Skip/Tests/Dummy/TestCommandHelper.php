<?php

	namespace Skip\Tests\Dummy;


	use Skip\SkipConsoleHelperInterface;
	use Symfony\Component\Console\Application;
	use Symfony\Component\Console\Helper\Helper;

	class TestCommandHelper extends Helper implements SkipConsoleHelperInterface{


		public function getHelper( \Pimple $pimple, Application $app ) {
			return $this;
		}

		public function convert( $name ) {
			return sprintf("%s, The Great!!!", $name);
		}

		public function getName() {
			return 'test_helper';
		}
	}