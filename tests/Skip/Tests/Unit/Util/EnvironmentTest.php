<?php

namespace Skip\Tests\Unit\Util;

use Skip\Util\Environment;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class EnvironmentTest extends \PHPUnit_Framework_TestCase {

    /**
     * create base definition for cli and stash SKIP_* env variables
     */
    protected function setUp() {

    	$this->cliInputDefinition = new InputDefinition(
    		array(
    			new InputOption('skip-user', null, InputOption::VALUE_REQUIRED ),
    			new InputOption('skip-env', null, InputOption::VALUE_REQUIRED ),
    			new InputOption('skip-app-path', null, InputOption::VALUE_REQUIRED )
    		)
    	);

        $this->rootPath = dirname(dirname(dirname(dirname(dirname(__DIR__)))));

    	$this->ENV_SKIP_APP_PATH = getenv('SKIP_APP_PATH');
    	putenv('SKIP_APP_PATH');
    	$this->SKIP_USER = getenv('SKIP_USER');
    	putenv('SKIP_USER');
    	$this->SKIP_ENV = getenv('SKIP_ENV');
    	putenv('SKIP_ENV');
    }

    /**
     * reset SKIP_* environment variables back to normal (provided they where set in the first instance :))
     */
    protected function tearDown() {

    	if( $this->ENV_SKIP_APP_PATH ) putenv("SKIP_APP_PATH=" . $this->ENV_SKIP_APP_PATH);
    	else putenv("SKIP_APP_PATH");

    	if( $this->SKIP_USER ) putenv("SKIP_USER=" . $this->SKIP_USER);
    	else putenv("SKIP_USER");

    	if( $this->SKIP_ENV ) putenv("SKIP_ENV=" . $this->SKIP_ENV);
    	else putenv("SKIP_ENV");
    }

    /**
     * test getUser Method
     */
    public function testGetUser( ) {

    	$cliInput = new StringInput('--skip-user=mudi');
    	$cliInput->bind( $this->cliInputDefinition );

    	$user = Environment::getUser($cliInput);

        $this->assertEquals( "mudi", $user);

    	putenv("SKIP_USER=dave");
    	$user = Environment::getUser();
        $this->assertEquals( "dave", $user);
    }

    /**
     * test getENV Method
     */
    public function testGetENV( ) {

    	putenv("SKIP_ENV=test");

        $cliInput = new StringInput('--skip-user=notenv');
        $cliInput->bind( $this->cliInputDefinition );
        $env = Environment::getENV($cliInput);
        $this->assertEquals( "test", $env);

    	$env = Environment::getENV();
        $this->assertEquals( "test", $env);

        $cliInput = new StringInput('--skip-env=development');
        $cliInput->bind( $this->cliInputDefinition );
        $env = Environment::getENV($cliInput);
        $this->assertEquals( "development", $env);

    }

    /**
     * test getAppPath Method
     * @expectedException Skip\Util\Exception\AppPathNotFoundException
     */
    public function testGetAppPath( ) {

        @rmdir("./app");
        @mkdir("./app");
    	$path = Environment::getAppPath();
        $this->assertEquals( $this->rootPath. "/app", $path);
        @rmdir("./app");

        @rmdir("./app");
        @mkdir("./app");
    	$cliInput = new StringInput('--skip-app-path=./app');
    	$cliInput->bind( $this->cliInputDefinition );
    	$path = Environment::getAppPath($cliInput);
        $this->assertEquals( $this->rootPath. "/app", $path);
        @rmdir("./app");

        @rmdir("./appPathTest");
        @mkdir("./appPathTest");
    	putenv("SKIP_APP_PATH=appPathTest");
    	$path = Environment::getAppPath();
        $this->assertEquals( $this->rootPath. "/appPathTest", $path);
        @rmdir("./appPathTest");


    	$config = Environment::getAppPath();
    }

    /**
     * test getLibPath Method
     */
    public function testGetLibPath( ) {

    	$path = Environment::getLibPath();

        $this->assertEquals( $this->rootPath, $path);
    }

    /**
     * test getLibPath Method
     */
    public function testGetVars( ) {

        @rmdir("./app");
        @mkdir("./app");
        $config = Environment::getVars();
        $this->assertEquals( $this->rootPath, $config['lib_path']);
        @rmdir("./app");
    }

}