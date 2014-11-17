<?php

    namespace Skip\Tests\Dummy;

    class TestService
    {
        public $value = '';

        public function __construct( $value ) {
            $this->value = $value;
        }

    }