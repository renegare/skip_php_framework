<?php

namespace Skip\Tests;

use Skip\Utils;
use Skip\Config;
use Skip\Application\Console;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Output\NullOutput;
use Phake;

class ConsoleTest extends \PHPUnit_Framework_TestCase {

    protected function setUp() {
        $app = new Console();
        $di = new \Pimple();

        // insert test service we want to ensure is set
        $dep_service = Phake::mock('Skip\Tests\Dummy\TestModel');
        $di['test'] = $dep_service;
        $this->dep_service = $dep_service;

        // insert di to be used instead of console creating its own
        $app->setApp( $di );

        // configure console as normal with a json config (take a look at if this test needs chaning!)
        $app->configure('./tests/config/console.json');
        $app->setAutoExit( false );
        $this->tester = new ApplicationTester( $app );
    }

    public function testLoadedCommandRuns( ) {
        $tester = $this->tester;
        $tester->run(array( 
            'command' => 'test:command',
            'name' => 'Skip'
        ));
        
        $this->assertContains('Nice name you got there, Skip.', $tester->getDisplay() );

    }

    public function testHelperCommandRuns( ) {
        $tester = $this->tester;

        $tester->run(array( 
            'command' => 'test:helper',
            'name' => 'Skipper'
        ));
        
        $this->assertContains('Nice name you got there, Skipper, The Great!!!.', $tester->getDisplay() );

    }

    public function testCommandDepsIsSet( ) {

        $this->tester->run(array( 
            'command' => 'test:set_test_service'
        ));

        // verify method was called ( see test command source for clarity on this test :( )
        Phake::verify( $this->dep_service, Phake::times(1))->getData();

    }

}