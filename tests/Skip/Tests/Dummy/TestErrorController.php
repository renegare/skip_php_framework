<?php
    
    namespace Skip\Tests\Dummy;

    use Symfony\Component\HttpFoundation\Response;
    use Skip\ServiceContainerInterface;

    class TestErrorController implements ServiceContainerInterface {

        protected $app;

        public function setContainer( \Pimple $app )
        {
            $this->app = $app;
        }

        public function errorAction( BadRequestException $e, $code ) {

            if( $this->app['debug'] ) return;

            return new Response( $e->getMessage(), 500, array('X-Status-Code' => 400));
        }
    }