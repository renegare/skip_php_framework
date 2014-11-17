<?php
    
    namespace Skip\Service;

    use Doctrine\DBAL\Configuration;
    use Doctrine\DBAL\DriverManager;
    use Skip\ServiceContainerInterface;

    class DatabaseService implements ServiceContainerInterface{

        protected $db;
        protected $model_lookup;
        protected $params;
        protected $config;

        public function __construct( $params, $model_lookup ) {
            $this->model_lookup = $model_lookup;

            $logger = new \Doctrine\DBAL\Logging\DebugStack();

            $this->config = new Configuration();
            $this->config->setSQLLogger( $logger );
            
            $this->db = DriverManager::getConnection($params, $this->config);
            $this->db->setFetchMode( \PDO::FETCH_OBJ );
            $this->params = $params;
        }

        public function setContainer( \Pimple $di )
        {
            $this->pimple = $di;
        }
        
        public function getConnection( ) {
            return $this->db;
        }

        public function getSQLLogger( ) {
            return $this->config->getSQLLogger();
        }

        public function prepare( $sql ) {
            if( $this->db->isConnected() ) {
                $this->db->connect();
            }

            return $this->db->prepare( $sql );
        }

        public function lastInsertId(){
            return $this->db->lastInsertId();
        }

        public function getModel( $key )
        {
            if( isset( $this->model_lookup[$key] ) )
            {
                $model = $this->model_lookup[$key];
                if( is_string($model) )
                {
                    $model = new $model( $this );
                    if( $model instanceof \Skip\ServiceContainerInterface ) {
                        $model->setContainer( $this->pimple );
                    }
                }

                if( is_object( $model ) )
                {
                    return $model;
                }
            }

            throw new \Exception( sprintf('Invalid model key %s could not be found or be instantiated', $key ) );
        }

        public function createQueryBuilder()
        {
            return $this->db->createQueryBuilder();
        }
    }