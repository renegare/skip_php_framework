<?php

    namespace Skip\Application;

    interface ApplicationInterface {
        

        /**
         * get configured application
         *
         * @return Generic  $app
         */
        public function getApp();
    }