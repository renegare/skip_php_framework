<?php

/*
 * This file is part of the Skip package.
 *
 * (c) Mudi Ugbowanko <mudi@renegare.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// PHP 5.4 Dev Server Router - check for a file that already exists (no point processing that!)
$full_path = realpath( __DIR__ . $_SERVER["REQUEST_URI"] ); 
if ( $full_path && preg_match('/\.(?:[a-zA-Z0-9_-]+)$/', $full_path)) {
    return false;    // serve the requested resource as-is.
}




$SKIP_LIB_ROOT = dirname(dirname( __DIR__ )) . '/vendor/renegare/skip';
$SKIP_APP_ROOT = dirname(dirname( __DIR__ ));

if ( !$loader = (@include $SKIP_APP_ROOT . '/vendor/autoload.php') ) {
    die('You must set up the project\'s composer dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

$SKIP_ENV = null;
$SKIP_USER = null;

// auto check for user, if none ... set env from env variables
if( !($SKIP_USER = getenv('SKIP_USER')) )
{
    $SKIP_ENV = getenv('SKIP_ENV');
}

$SKIP_ENV = $SKIP_ENV?: 'production';

$config_path = realpath( $SKIP_APP_ROOT . '/app/config/user/' . $SKIP_USER . '.json');
if( !$config_path )
{
    $config_path = realpath( $SKIP_APP_ROOT . '/app/config/env/' . $SKIP_ENV . '.json');
}

$app = new Skip\Application\Web();

$app->configure( $config_path, array(
    'ENV' => $SKIP_ENV,
    'USER' => $SKIP_USER,
    'APP_ROOT' => $SKIP_APP_ROOT,
    'LIB_ROOT' => $SKIP_LIB_ROOT
));

$app->run();