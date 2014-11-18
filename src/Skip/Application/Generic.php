<?php

    namespace Skip\Application;

    use Skip\Config;
    use Symfony\Component\Console\Application as SymfonyConsole;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class Generic extends \Pimple implements ApplicationInterface{

        const CACHE_KEY = 'skip_config_cache';

        protected $config;

        public function __construct( $appPath, $libPath, $env='', $user='', $sapi='', $cache=false)
        {
            parent::__construct();

            $this->config = self::configure( $this, $appPath, $libPath, $env, $user, $sapi? $sapi : 'generic', $cache );
        }

        public function getConfig() {
            return $this->config;
        }

        /**
         * {@inheritdoc}
         */
        public static function configure( \Pimple $app, $appPath, $libPath, $env='', $user='', $sapi='', $cache=false )
        {
            $configCacheHash = $cache? implode('.', array($appPath, $libPath, $env, $user, $sapi)) : '';
            $config = $cache ? Generic::getConfigCache($configCacheHash) : false;

            if(!$config) {

                //@TODO: allow not just json to be loaded
                $configPath = realpath( sprintf('%s/config/user/%s.json', $appPath, $user) );
                if( !$configPath ) {
                    $configPath = realpath( sprintf('%s/config/env/%s.json', $appPath, $env) );
                    if( !$configPath ) {
                        $configPath = realpath( sprintf('%s/config/app.json', $appPath, $sapi) );
                    }
                }

                $config = array(
                    'core' => array(
                        'user' => $user,
                        'env' => $env,
                        'sapi' => $sapi,
                        'appPath' => $appPath,
                        'libPath' => $libPath,
                        'configPath' => $configPath
                    )
                );

                if( file_exists($configPath) )
                {
                    $loadedConfig = Config::load( $configPath, $config['core'] );
                    $loadedConfig['core'] = array_merge( $loadedConfig['core'], $config['core'] );

                    $config = $loadedConfig;
                }

                if( $cache ) {
                    Generic::setConfigCache($configCacheHash, $config);
                } else {
                    Generic::clearConfigCache();
                }
            }

            Config::configurePimple($app, $config);

            return $config;

        }

        /**
         * {@inheritdoc}
         */
        public function getApp()
        {
            return $this;
        }

        public static function getConfigCache( $hash ) {
            $cache = apc_fetch(Generic::CACHE_KEY);
            if($cache[0] != $hash) {
                Generic::clearConfigCache();
                return null;
            }

            return $cache[1];
        }

        public static function setConfigCache( $hash, $config ) {
            Generic::clearConfigCache();
            return apc_store(Generic::CACHE_KEY, array($hash, $config));
        }

        public static function clearConfigCache() {
            return apc_delete(Generic::CACHE_KEY);
        }
    }
