<?php
    
    namespace Skip\Service;
    
    use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
    use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
    use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeSessionHandler;
    use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
    use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

    class SessionService extends SymfonySession
    {
        public function __construct( array $config = array() )
        {
            $handler = isset( $config['handler'] )? $config['handler'] : 'native';
            $config = isset( $config['config'] )? $config['config'] : array();

            try{
                switch( $handler )
                {
                    case "memcached":
                        $storage = $this->configureMemcachedHandler( $config );
                        break;
                    default:
                        $storage = new NativeSessionHandler();
                        break;
                }
            } catch ( \Exception $e )
            {
                $storage = new NativeSessionHandler();
            }

            $storage = new MockArraySessionStorage();
            /*
            $storage = new NativeSessionStorage(array(
                "name"=> "hmmmm",
                "id"=> "hmmmm",
                "path"=> "/"
            ), $storage );
            */

            parent::__construct( $storage );
        }

        protected function configureMemcachedHandler( $config )
        {
            $servers = isset( $config['servers'] )? $config['servers'] : array();
            if( !count($servers) )
            {
                throw new \Exception("You need to set at least one memcached server.");
            }

            $m = new \Memcached();
            foreach( $servers as $server )
            {
                $host = $weight = $port = null;
                extract($server);
                if( !$host )
                {
                    throw new \Exception("You need to set at least the host of the memcached server.");
                }

                $port = $port? $port :11211;
                $weight = $weight? $weght:1;
                
                $m->addServer( $host, $port, $weight );
            }

            $m = new MemcachedSessionHandler( $m, array(
                'prefix' => "skip_session_"
            ));
            return $m;
        }

    }