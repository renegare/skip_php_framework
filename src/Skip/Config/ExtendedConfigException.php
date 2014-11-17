<?php

	namespace Skip\Config;

	class ExtendedConfigException extends \Exception{

		protected $config;

		protected $message;

		public function __construct( $message, $config ) {
			$this->message = $message;
			$this->config = $config;
		}

		public function getConfig() {
			return $this->config;
		}

	}