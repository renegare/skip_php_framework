<?php
    
    namespace Skip\Tests;

    use Silex\WebTestCase;
    use Skip\Application\Web as Application;
    use Skip\Utils;

    class ControllerTest extends WebTestCase {

        public function createApplication(){

            $app = new Application();
            $app->configure( './tests/config/web_app.json' );
            $app['debug'] = true;
            $app['exception_handler']->disable();
            return $app;

        }

        public function testInitialPage() {

            $client = $this->createClient();
            $crawler = $client->request('GET', '/mudi');

            $this->assertTrue($client->getResponse()->isOk());
            $this->assertRegExp('/mudi/', $client->getResponse()->getContent());

        }

        public function testUnderscorePage() {

            $client = $this->createClient();
            $crawler = $client->request('GET', '/underscore');

            $this->assertTrue($client->getResponse()->isOk());
            $this->assertRegExp('/underscore/', $client->getResponse()->getContent());

        }

    }