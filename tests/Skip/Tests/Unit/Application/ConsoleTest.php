<?php

namespace Skip\Tests\Unit\Util;

use Skip\Util\Environment;
use Skip\Config;
use Symfony\Component\Console\Output\NullOutput;
use Phake;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use Skip\Application\Console;
use Symfony\Component\Console\Tester\ApplicationTester;

class ConsoleTest extends \PHPUnit_Framework_TestCase {

    /**
     * create base definition for cli and stash SKIP_* env variables
     */
    protected function setUp() {

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
     * test console loads correct variables from the command line
     */
    public function testLoadFromEnvironment( ) {
    	$this->markTestSkipped('The ' . __CLASS__ .' Symfony ... Console App ... so difficult to extend and test :[');

    	$consoleApp = new Console();
    	$consoleApp->setAutoExit( false );
    	$appTester = new ApplicationTester( $consoleApp );

    	$appTester->run( array(
    		'--skip-user' => 'mudi',
    		'--skip-env' => 'test',
    		'--skip-app-path' => './tests/fixtures/app'
    	), array( 'verbosity' => 3 ));

    	$app = $consoleApp->getApp();


        $skipCore = $app['skip']['core'];

        $this->assertEquals( "mudi", $skipCore["user"]);
        $this->assertEquals( "test", $skipCore["env"]);
        
    }

    public function testConfigurationOptionLoadsCommands( ) {
    	$this->markTestSkipped('The ' . __CLASS__ .' Symfony ... Console App ... so difficult to extend and test :[');
        $consoleApp = new Console();
        $consoleApp->setAutoExit( false );
        $tester = new ApplicationTester( $consoleApp );
        $tester->run(array(
            'command' => 'test:command',
            '-h',
            '--skip-app-path' => './tests/fixtures/app'
        ));
        
        $this->assertContains('test:command', $tester->getDisplay() );

    }

    public function testLoadedCommandRuns( ) {
    	$this->markTestSkipped('The ' . __CLASS__ .' Symfony ... Console App ... so difficult to extend and test :[');
        $consoleApp = new Console();
        $consoleApp->setAutoExit( false );
        $tester = new ApplicationTester( $consoleApp );
        $tester->run(array( 
            'command' => 'test:command',
            'name' => 'Skip',
            '--skip-app-path' => './tests/fixtures/app'
        ));
        
        $this->assertContains('Nice name you got there, Skip.', $tester->getDisplay() );

    }

}