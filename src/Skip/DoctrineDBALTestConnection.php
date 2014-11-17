<?php
    
    namespace Skip;

    use Doctrine\DBAL\Connection;

    class DoctrineDBALTestConnection extends Connection{

        protected static $_test_conn;

        public function connect() {
            // enable sharing connection across multiple instances

            $return = parent::connect();

            if( DoctrineDBALTestConnection::$_test_conn ) {
                $this->_conn = DoctrineDBALTestConnection::$_test_conn;
            } else {
                DoctrineDBALTestConnection::$_test_conn = $this->_conn;
            }

            return $return;
        }

        public function getPDOConnection() {
            if( !$this->_conn ) {
                $this->connect();
            }
            return $this->_conn;
        }
    }