<?php

	namespace Skip\Console\Helpers;


	use Skip\SkipConsoleHelperInterface;
	use Symfony\Component\Console\Application;

	class SymfonyDialogHelper implements SkipConsoleHelperInterface{

		public function getHelper( \Pimple $pimple, Application $app ) {
			return new \Symfony\Component\Console\Helper\DialogHelper();
		}
	}