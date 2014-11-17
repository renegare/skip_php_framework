<?php

    namespace Skip\Application;

    interface ApplicationWrapperInterface extends ApplicationInterface {

        /**
         * set pimple application to be used
         *
         * @param \Pimple    $app    useful when you want use another pimple instance to do stuff like testing!
         */
        public function setApp( \Pimple $app );
    }