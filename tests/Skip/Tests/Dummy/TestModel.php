<?php
    
    namespace Skip\Tests\Dummy;

    class TestModel { 

        // stub
        public function getData() {}

        public function setDummyService( TestModel $service ) {
            // just called so this can be tested!
            $service->getData();
        }


    }