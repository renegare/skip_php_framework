<?php


namespace Skip\Tests\Dummy;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;

class TestProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Application $app)
    {

		$wtf = $app['testdump'];
		$wtf['serviceProviderLoaded'] = true;
		$app['testdump'] = $wtf;
    }

    public function boot(Application $app)
    {
    }


    public function connect(Application $app)
    {

        $controllers = $app['controllers_factory'];

        $controllers->get('/controllerProviderTest', function (Application $app) {
            return "Test Provider A-OK!";
        });

        return $controllers;
    }

}