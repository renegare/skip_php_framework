<?php

	namespace Skip\Tests\Dummy;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

	class TestHook {
		public function setupBefore( \Pimple $app ){
			$wtf = $app['testdump'];
			$wtf['setUpBeforeRun'] = true;
			$app['testdump'] = $wtf;
		}

		public function setupAfter( \Pimple $app ){
			$wtf = $app['testdump'];
			$wtf['setUpAfterRun'] = true;
			$app['testdump'] = $wtf;
		}

		public function silextAppBeforeA( Request $request ) {
			switch( $request->get('skipMiddleWareTarget') ) {
				case 'silextAppBeforeA'	:
				case 'priority'	:
					return new Response("silextAppBeforeA");
					break;
			}
			
		}

		public function silextAppBeforeB( Request $request ) {
			switch( $request->get('skipMiddleWareTarget') ) {
				case 'priority'	:
					return new Response("silextAppBeforeB");
					break;
			}
			
		}

		public function silextAppAfterA( Request $request, Response $response ) {

			$headers = $response->headers;

			switch( $request->get('skipMiddleWareTarget') ) {
				case 'silextAppAfterA'	:
					$response->headers->set('skipMiddleWareTarget', 'silextAppAfterA' );
					break;

				case 'priority':
					$headers->set('skipMiddleWareTarget', $headers->get('skipMiddleWareTarget', '') . 'silextAppAfterA' );
					break;
			}
			
		}

		public function silextAppAfterB( Request $request, Response $response ) {

			$headers = $response->headers;

			switch( $request->get('skipMiddleWareTarget') ) {
				case 'priority':
					$headers->set('skipMiddleWareTarget', $headers->get('skipMiddleWareTarget', '') . 'silextAppAfterB' );
					break;
			}
			
		}
	}