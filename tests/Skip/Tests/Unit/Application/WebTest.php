<?php

namespace Skip\Tests\Unit\Application;

use Skip\Application\Web;
use Silex\WebTestCase;

class WebTest extends WebTestCase {

    /**
     * create base definition for cli and stash SKIP_* env variables
     */
    public function setUp() {

    	$this->ENV_SKIP_APP_PATH = getenv('SKIP_APP_PATH');
    	putenv('SKIP_APP_PATH');
    	$this->SKIP_USER = getenv('SKIP_USER');
    	putenv('SKIP_USER');
    	$this->SKIP_ENV = getenv('SKIP_ENV');
    	putenv('SKIP_ENV');

        parent::setUp();
    }

    public function createApplication()
    {
        putenv("SKIP_APP_PATH=./tests/fixtures/app");
        $app = new Web(array(
            'testdump' => array()
        ));
        $app['debug'] = true;
        $app['session.test'] = true;
        #$app['exception_handler']->disable();
        return $app;
    }
    /**
     * reset SKIP_* environment variables back to normal (provided they where set in the first instance :))
     */
    public function tearDown() {

        parent::tearDown();

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
    public function testDefaultHomePage( ) {

        $client = $this->createClient();
        $crawler = $client->request('GET', '');
        $this->assertTrue($client->getResponse()->isOk());

    }

    public function testMountableControllerConfig() {
        $this->app['exception_handler']->disable();
        $client = $this->createClient();
        $crawler = $client->request('GET', 'root/sub1/sub2');
        $this->assertTrue($client->getResponse()->isOk());

    }

    /**
     * test console loads correct variables from the command line
     */
    public function testSetupBeforeHook() {

        $this->assertTrue( $this->app['testdump']['setUpBeforeRun'] );

    }

    /**
     * test console loads correct variables from the command line
     */
    public function testSetupAfterHook() {

        $this->assertTrue( $this->app['testdump']['setUpAfterRun'] );

    }

    /**
     * test configured service provider is loaded
     */
    public function testServiceProviderLoaded() {

        $this->assertTrue( $this->app['testdump']['serviceProviderLoaded'] );

    }

    /**
     * test configured controller provider is loaded
     */
    public function testControllerProviderLoaded( ) {

        $client = $this->createClient();
        $crawler = $client->request('GET', '/controllerProviderTest');
        $this->assertTrue($client->getResponse()->isOk());

    }

    /**
     * test configured silex app middlewares
     */
    public function testSilexAppMiddlewares( ) {

        // before middle ware
        $client = $this->createClient();
        $crawler = $client->request('GET', '/', array(
            'skipMiddleWareTarget' => 'silextAppBeforeA'
        ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals($response->getContent(), "silextAppBeforeA" );

        // before priority
        $client = $this->createClient();
        $crawler = $client->request('GET', '/', array(
            'skipMiddleWareTarget' => 'priority'
        ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals($response->getContent(), "silextAppBeforeB" );



        // after middle ware
        $client = $this->createClient();
        $crawler = $client->request('GET', '/', array(
            'skipMiddleWareTarget' => 'silextAppAfterA'
        ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals($response->headers->get('skipMiddleWareTarget'), "silextAppAfterA" );

        // after priority
        $client = $this->createClient();
        $crawler = $client->request('GET', '/', array(
            'skipMiddleWareTarget' => 'priority'
        ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals($response->headers->get('skipMiddleWareTarget'), "silextAppAfterBsilextAppAfterA" );

    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @iThink this is testing silex ... but I like it :p
     */
    public function testDebugNotFoundErrorException() {

        $this->app['exception_handler']->disable();
        $client = $this->createClient();
        $crawler = $client->request('GET', '/non-existent-uri');
    }

    /**
     * @expectedException Skip\Tests\Dummy\TestSystemException
     * @iThink this is testing silex ... but I like it :p
     */
    public function testDebugSystemErrorException() {
        $this->app['exception_handler']->disable();
        $client = $this->createClient();
        $crawler = $client->request('GET', '/system-exception-uri');
    }

    /**
     * @iThink this is testing silex ... but I like it :p
     */
    public function testNotFoundExceptionCode() {

        $client = $this->createClient();
        $crawler = $client->request('GET', '/non-existent-uri');

        $response = $client->getResponse();
        $this->assertFalse($response->isOk());
        $this->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * @iThink this is testing silex ... but I like it :p
     */
    public function testSystemErrorExceptionCode() {

        $client = $this->createClient();
        $crawler = $client->request('GET', '/system-exception-uri');

        $response = $client->getResponse();
        $this->assertFalse($response->isOk());
        $this->assertEquals($response->getStatusCode(), 500);
    }

    public function testErrorHandlerConfiguration() {

        $this->app['debug'] = false;

        $client = $this->createClient();
        $crawler = $client->request('GET', '/system-exception-custom-code');

        $response = $client->getResponse();
        $this->assertFalse($response->isOk());
        $this->assertEquals($response->getStatusCode(), 400);

    }

    /**
     * test that request_methods for routes
     */
    public function testMultiMethodRouteConfig() {

        # Any Method: Allows any method (because nothing is specified in the config for the request method)
        $client = $this->createClient();
        $crawler = $client->request('RANDOM', '/any-method');

        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals( $response->getContent(), "RANDOM" );

        # Single Method: Allows only DELETE method
        $client = $this->createClient();
        $crawler = $client->request('DELETE', '/single-method');

        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals( $response->getContent(), "DELETE" );

        $client = $this->createClient();
        $crawler = $client->request('OPTIONS', '/single-method');

        $response = $client->getResponse();
        $this->assertFalse($response->isOk());

        # Multi Method: Allows GET and POST only
        $client = $this->createClient();
        $crawler = $client->request('GET', '/multi-methods');

        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals( $response->getContent(), "GET" );

        $client = $this->createClient();
        $crawler = $client->request('POST', '/multi-methods');

        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals( $response->getContent(), "POST" );

        $client = $this->createClient();
        $crawler = $client->request('PUT', '/multi-methods');

        $response = $client->getResponse();
        $this->assertFalse($response->isOk());
    }

    /**
     * test that convert and assertion params in route configuration
     */
    public function testConvertParamRouteConfig() {

        $this->app['exception_handler']->disable();

        $client = $this->createClient();
        $crawler = $client->request('GET', '/convert-param-route/2');

        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals( $response->getContent(), 4 );

        # test default value ... given default value in config is set to 10

        $client = $this->createClient();
        $crawler = $client->request('GET', '/convert-param-route');

        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals( $response->getContent(), 20 );

        #response headers

        $client = $this->createClient();
        $crawler = $client->request('GET', '/convert-param-route');

        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals( $response->headers->get("skip-developer"), "Mudi" );

    }

    /**
     * test configured route middlewares
     */
    public function testRouteMiddlewares( ) {

        $this->app['exception_handler']->disable();

        // before middle ware
        $client = $this->createClient();
        $crawler = $client->request('GET', '/middle-ware-route', array(
            'mwrMiddleWareTarget' => 'mwrAppBeforeA'
        ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals($response->getContent(), "mwrAppBeforeA" );

        $client = $this->createClient();
        $crawler = $client->request('GET', '/middle-ware-route', array(
            'mwrMiddleWareTarget' => 'mwrAppBeforeB'
        ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals($response->getContent(), "mwrAppBeforeB" );

        // after middle ware
        $client = $this->createClient();
        $crawler = $client->request('GET', '/middle-ware-route', array(
            'mwrMiddleWareTarget' => 'mwrAppAfterA'
        ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals($response->headers->get('mwrMiddleWareTarget'), "mwrAppAfterA" );

        // after priority
        $client = $this->createClient();
        $crawler = $client->request('GET', '/middle-ware-route', array(
            'mwrMiddleWareTarget' => 'mwrAppAfterB'
        ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals($response->headers->get('mwrMiddleWareTarget'), "mwrAppAfterB" );

    }


    /**
     * test configured route middlewares
     */
    public function testGetApp( ) {
        $this->assertTrue( $this->app->getApp() instanceof \Pimple );
    }

}