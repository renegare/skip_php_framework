<?php
	
	namespace Skip;

	interface TwigContextTransformerInterface {

		public static function transform( \Pimple $pimple, array $context );

	}
