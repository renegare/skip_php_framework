<?php


    // set environment and user consts
    define('APP_ROOT', dirname( dirname(__FILE__) ) );
    define( 'SKIP_ENV', 'test' );
    define( 'SKIP_USER', ($developer = getenv('SKIP_USER'))? $developer : FALSE );

    $loader = require __DIR__.'/../vendor/autoload.php';
    $loader->add('App\Test', __DIR__ . '/src');

    $console_app = new Skip\Application\Console();

    $config_path = realpath(APP_ROOT . '/server/config/' . ( SKIP_USER ? 'users/' . SKIP_USER : 'env/' . SKIP_ENV ) . '.json');

    $console_app->configure( $config_path );
    $app = $console_app->getApp();
