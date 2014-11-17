<?php
    
    namespace Skip\Tests\Dummy;

    use Silex\Application;
    use Symfony\Component\HttpFoundation\Request;

    class TestController {

        public function indexAction( Application $app ) {
            return "Welcome to Skip";
        }

        public function action( Application $app, $param ) {
            return "Parampassed: $param";
        }

        public function underscore_action( Application $app ) {
            return "underscore";
        }

        public function systemErrorAction( Application $app ) {
            throw new TestSystemException("This is a test system exception!", 500 );
        }

        public function systemErrorCustomCodeAction( Application $app ) {
            throw new BadRequestException("This is a test system exception!" );
        }

        public function multiMethodAction( Application $app, Request $request ) {
            return $request->getMethod();
        }

        public function convertAction( Application $app, Request $request, $number ) {
            return $number;
        }

        public function doubleNumber( $number ) {
            return $number * 2;
        }


    }