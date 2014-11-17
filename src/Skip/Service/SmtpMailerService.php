<?php
    
    namespace Skip\Service;

    class SmtpMailerService extends \Swift_Mailer{

        protected $smtp_transport;
        protected $spool_transport;

        public function __construct( $config ) {

            if( isset( $config['spool_dir'] ) ) {
                if( !file_exists($config['spool_dir']) ) {
                    mkdir( $config['spool_dir'] );
                }
                $spooler = new \Swift_FileSpool( $config['spool_dir'] );
                $spool_event_dispatcher = new \Swift_Events_SimpleEventDispatcher();
                $this->spool_transport = new \Swift_Transport_SpoolTransport( $spool_event_dispatcher, $spooler );
            }
            $this->smtp_transport = \Swift_SmtpTransport::newInstance( $config['host'], $config['port'] )
                    ->setUsername( $config['username'] )
                    ->setPassword( $config['password'] );

            parent::__construct( $this->spool_transport ? $this->spool_transport : $this->smtp_transport );
        }

        public function getSmtpTransport() {
            return $this->smtp_transport;
        }

        public function getSmtpSpoolTransport() {
            return $this->spool_transport;
        }

    }