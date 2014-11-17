<?php
	
	namespace Skip;

	use Symfony\Component\Console\Application;

	interface SkipConsoleHelperInterface {

		public function getHelper( \Pimple $pimple, Application $app );
	}
