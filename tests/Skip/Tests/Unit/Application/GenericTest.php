<?php

namespace Skip\Tests\Unit\Util;

use Skip\Application\Generic;
use Symfony\Component\Stopwatch\Stopwatch;

class GenericTest extends \PHPUnit_Framework_TestCase {

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
    	$libPath = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
    	$appPath = $libPath . "/tests/fixtures/app";

    	$app = new Generic( $appPath, $libPath, '', '', '' );

        $this->assertEquals( true, $app["debug"]);
    }

    public function testApplicationConfigCache() {
        if( !extension_loaded('apc') || !ini_get('apc.enable_cli'))
        {
            $this->markTestSkipped('The ' . __CLASS__ .' requires apc extension with apc.enable_cli=1.');
        }

        $this->assertFalse(apc_exists(Generic::CACHE_KEY));

        $configHash = 'fake-hash';
        $config = array('variables');
        Generic::setConfigCache($configHash, $config);

        $this->assertTrue(apc_exists(Generic::CACHE_KEY));

        $cacheConfig = Generic::getConfigCache($configHash);

        $this->assertInternalType('array', $cacheConfig);
        $this->assertEquals($config[0], $cacheConfig[0]);

        Generic::clearConfigCache();
        $this->assertFalse(apc_exists(Generic::CACHE_KEY));

        Generic::setConfigCache($configHash, $config);
        $this->assertNull(Generic::getConfigCache('incorrect-fake-hash'));
        $this->assertFalse(apc_exists(Generic::CACHE_KEY));
    }

    public function testApplicationConfigCacheIsFaster() {
        if( !extension_loaded('apc') || !ini_get('apc.enable_cli'))
        {
            $this->markTestSkipped('The ' . __CLASS__ .' requires apc extension with apc.enable_cli=1.');
        }

        $libPath = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        $appPath = $libPath . "/tests/fixtures/app";
        Generic::clearConfigCache();
        $this->assertFalse(apc_exists(Generic::CACHE_KEY));

        $stopwatch = new Stopwatch();
        $stopwatch->start('noCache');
        $app = new Generic( $appPath, $libPath, '', '', '' );
        $noCache = $stopwatch->stop('noCache');
        $app = null;

        $app = new Generic( $appPath, $libPath, '', '', '', true );
        $app = null;
        $stopwatch->start('cache');
        $app = new Generic( $appPath, $libPath, '', '', '', true );
        $cache = $stopwatch->stop('cache');

        $this->assertLessThan( $noCache->getDuration(), $cache->getDuration() );

        $this->assertInstanceOf('Pimple', $app->getApp());

    }

}