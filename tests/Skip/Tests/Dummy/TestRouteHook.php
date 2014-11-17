<?php

	namespace Skip\Tests\Dummy;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Skip\ServiceContainerInterface;

	class TestRouteHook implements ServiceContainerInterface {

        public function setContainer( \Pimple $app ) {

        }

		public function mwrAppBeforeA( Request $request ) {
			switch( $request->get('mwrMiddleWareTarget') ) {
				case 'mwrAppBeforeA':
					return new Response("mwrAppBeforeA");
					break;
			}
			
		}

		public function mwrAppBeforeB( Request $request ) {
			switch( $request->get('mwrMiddleWareTarget') ) {
				case 'mwrAppBeforeB':
					return new Response("mwrAppBeforeB");
					break;
			}
			
		}

		public function mwrAppAfterA( Request $request, Response $response ) {

			$headers = $response->headers;

			switch( $request->get('mwrMiddleWareTarget') ) {
				case 'mwrAppAfterA':
					$response->headers->set('mwrMiddleWareTarget', 'mwrAppAfterA' );
					break;
			}
			
		}

		public function mwrAppAfterB( Request $request, Response $response ) {

			$headers = $response->headers;

			switch( $request->get('mwrMiddleWareTarget') ) {
				case 'mwrAppAfterB':
					$headers->set('mwrMiddleWareTarget', 'mwrAppAfterB' );
					break;
			}
			
		}
	}