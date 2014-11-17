<?php

namespace Skip\Tests;

use Skip\Utils;
use Skip\Config;
use Phake;

class ConfigTest extends \PHPUnit_Framework_TestCase {

    public static $config_test_tmp_dir = './tests/config';


    public static function setUpBeforeClass()
    {
        define('SKIP_CONFIG_CONST', 'const');
    }

    public function testLoadingConfig( ) {
        $config = Config::load( self::$config_test_tmp_dir . '/base_config.json' );
        $this->assertInternalType('array', $config);
    }

    public function testValueReplacement( ) {

        $SKIP_REPLACE_VALUE = 'random_value';
        $config = Config::load( self::$config_test_tmp_dir . '/base_config.json', array(
            'SKIP_REPLACE_VALUE' => $SKIP_REPLACE_VALUE
        ));

        $this->assertInternalType('array', $config);

        $this->assertRegExp('/' . $SKIP_REPLACE_VALUE . '/', $config['const'] );

        $this->assertArrayHasKey('providers', $config);
        $this->assertInternalType('array', $config['providers']);

        $this->assertArrayHasKey('twig', $config['providers']);
        $this->assertInternalType('array', $config['providers']['twig']);

        $this->assertArrayHasKey('params', $config['providers']['twig']);
        $this->assertInternalType('array', $config['providers']['twig']['params']);

        $this->assertArrayHasKey('twig.path', $config['providers']['twig']['params']);
        $this->assertInternalType('string', $config['providers']['twig']['params']['twig.path']);

        $this->assertRegExp('/' . $SKIP_REPLACE_VALUE . '/', $config['providers']['twig']['params']['twig.path'] );
    }

    public function testConfigExtend( ) {

        $config = Config::load( self::$config_test_tmp_dir . '/extend_config.json' );

        $this->assertInternalType('array', $config);

        $this->assertArrayHasKey('setting', $config);
        $this->assertArrayHasKey('original', $config);

        $this->assertEquals('extend', $config['setting']);
        $this->assertEquals('base', $config['original']);

    }

    public function testConfigImport() {

        $config = Config::load( self::$config_test_tmp_dir . '/import_config.json' );

        $this->assertInternalType('array', $config);
        $this->assertInternalType('array', $config['extend_config']);

        $this->assertArrayHasKey('setting', $config['extend_config']);
        $this->assertArrayHasKey('original', $config['extend_config']);

        $this->assertEquals('extend', $config['extend_config']['setting']);
        $this->assertEquals('base', $config['extend_config']['original']);

    }
    
    /** 
     * @expectedException Skip\Config\InfiniteConfigIncludeDetectedException
     */
    public function testInfiniteImportConfig( ) {

        $config = Config::load( self::$config_test_tmp_dir . '/loop_error_import_config.json' );

    }

    /**
     * @expectedException Skip\Config\InfiniteConfigIncludeDetectedException
     */
    public function testInfiniteExtendConfig( ) {

        $config = Config::load( self::$config_test_tmp_dir . '/loop_error_extend_config.json' );

    }

    /**
     * @expectedException Skip\Config\SubLevelExtendConfigException
     */
    public function testSubLevelExendError( ) {

        $config = Config::load( self::$config_test_tmp_dir . '/sub_level_extend_error_config.json' );

    }

    /**
     * @expectedException Skip\Config\ConfigFileDoesNotExistException
     */
    public function testConfigFileDoesNotExistError( ) {

        $config = Config::load( self::$config_test_tmp_dir . '/non_existant_config.json' );

    }

    public function testSharedConfig() {

        $config = Config::load( self::$config_test_tmp_dir . '/base_shared_config.json' );

        $this->assertInternalType('array', $config);

        $this->assertArrayHasKey('original_value', $config);
        $this->assertArrayHasKey('clones', $config);


        $this->assertEquals($config['clones']['shared_value1'], $config['original_value']);
        $this->assertEquals($config['clones']['shared_value2'], $config['original_value']);
        $this->assertEquals($config['clones']['shared_value3'], 'base');

    }

    public function stestSettingsConfigAsServiceDependency() {

        $pimple = new \Pimple();
        $config = Config::load( self::$config_test_tmp_dir . '/settings.json' );
        Config::configurePimple($pimple, $config);

        $service = $pimple['dummy'];

        $this->assertEquals($service->value, $config['settings']['param']);

    }

    public function testServiceSetParameter() {

        $pimple = new \Pimple();
        Config::configureServices($pimple, array(
            "test_service1" => array(
                "class" => "Skip\\Tests\\Dummy\\TestModel",
                "set" => array(
                    "dummy_service" => "test_service2"
                )
            )
        ));

        // configure dependee service
        $dep_service = Phake::mock('Skip\Tests\Dummy\TestModel');
        $pimple['test_service2'] = $dep_service;

        // request service 
        $pimple['test_service1'];

        Phake::verify( $dep_service, Phake::times(1))->getData();
    }

    public function testAPCCache() {
        
    }

}