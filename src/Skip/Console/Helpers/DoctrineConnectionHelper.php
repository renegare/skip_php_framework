<?php

	namespace Skip\Console\Helpers;


	use Skip\SkipConsoleHelperInterface;
	use Symfony\Component\Console\Application;

	class DoctrineConnectionHelper implements SkipConsoleHelperInterface{

		public function getHelper( \Pimple $pimple, Application $app ) {
			$db = \Doctrine\DBAL\DriverManager::getConnection( $pimple['skip']['settings']['db.options'] );
			return new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($db);
		}
		
	}
