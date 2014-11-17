<?php
    
    namespace Skip\Service;
    
    use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
    use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
    use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeSessionHandler;
    use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

    class MemcachedSessionHandlerService extends MemcachedSessionHandler
    {
        public function __construct( array $config )
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

                $port = !$port?:11211;
                $weight = !$weight?:1;
                $m->addServer( $host, $port, $weight );
            }

            //@TODO: Check if servers are active and log it somewhere

            $m = parent::__construct( $m, array(
                'prefix' => "session_"
            ));
            return $m;
        }

        //@TODO: Allow for logging of connections, maybe even an integration with the web profiler
    }