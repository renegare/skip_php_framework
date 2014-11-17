<?php

    namespace Skip\Console\Command;
    
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use Skip\ServiceContainerInterface;

    class SmtpMailerSpoolCommand extends Command implements ServiceContainerInterface
    {

        protected $app;

        public function setContainer( \Pimple $app )
        {
            $this->app = $app;
        }
        protected function configure()
        {
            $this
                ->setName('smtp:send_spool')
                ->setDescription('Send emails from the spool')
                ->setDefinition(array(
                        new InputOption('message-limit', '', InputOption::VALUE_OPTIONAL, 'The maximum number of messages to send.'),
                        new InputOption('time-limit', 0, InputOption::VALUE_OPTIONAL, 'The time limit for sending messages (in seconds).'),
                    )
                )
            ;
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $mailer = $this->app["mailer"];
            $smtp_transport = $mailer->getSmtpTransport();
            $spool_transport = $mailer->getSmtpSpoolTransport();

            if ($spool_transport instanceof \Swift_Transport_SpoolTransport) {
                $spool = $spool_transport->getSpool();
                $spool->setMessageLimit($input->getOption('message-limit'));
                $spool->setTimeLimit($input->getOption('time-limit'));
                $sent = $spool->flushQueue( $smtp_transport );
                $output->writeln(sprintf('sent %s emails', $sent));
            }
        }
    }
