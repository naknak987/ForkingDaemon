<?php
    /**
     * The daemon will check if new booklets need to be made every ten minutes. If a booklet does need made, it will queue up a worker to generate the booklet.
     */
    namespace appName\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument as InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface as OutputInterface;

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    use Pheanstalk\Pheanstalk;

    class StartCommand extends Command
    {
        protected $continueFlag = true;

        protected function configure()
        {
            $this->setName('Start')
                ->setDescription('Start Command. Starts the Resident Discharge Booklet daemon.')
                ->setHelp('Start');
            return;
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            declare(ticks = 10);

            pcntl_signal(SIGHUP, [$this, 'signalHandler']);
            pcntl_signal(SIGUSR1, [$this, 'sigusr1Handler']);
            pcntl_signal(SIGUSR2, [$this, 'sigusr2Handler']);
            pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
            pcntl_signal(SIGILL, [$this, 'signalHandler']);
            pcntl_signal(SIGABRT, [$this, 'signalHandler']);
            pcntl_signal(SIGFPE, [$this, 'signalHandler']);
            pcntl_signal(SIGSEGV, [$this, 'signalHandler']);
            pcntl_signal(SIGPIPE, [$this, 'signalHandler']);
            pcntl_signal(SIGTERM, [$this, 'signalHandler']);
            pcntl_signal(SIGCHLD, [$this, 'sigchldHandler']);
            pcntl_signal(SIGCONT, [$this, 'signalHandler']);
            pcntl_signal(SIGTSTP, [$this, 'signalHandler']);
            pcntl_signal(SIGTTIN, [$this, 'signalHandler']);
            pcntl_signal(SIGTTOU, [$this, 'signalHandler']);

            pcntl_signal(SIGINT, [$this, 'signalHandler']);
            pcntl_signal(SIGALRM, [$this, 'alarmHandler']);

            //pcntl_alarm(10);


            $pheanstalk = new Pheanstalk('localhost');

            for ($i = 1; $i <= 5; ++$i) {
                $pid = pcntl_fork();
        
                if (!$pid) {
                    sleep(10+($i*2));
                    $this->logg("In child" . $i);
                    do {
                        /**
                         * This is the child process loop.
                         */
                        echo "_";
                        $this->logg("{$i} Working on a job.");
                        sleep(5);
                        pcntl_signal_dispatch();
                    } while ($this->continueFlag);
                    exit($i);
                }
            }
        
            do {
                /**
                 * This is the parent process loop.
                 */
                echo ".";
                $this->logg("checking for new jobs that need done.");
                sleep(5);
                pcntl_signal_dispatch();
            } while ($this->continueFlag);

            while (pcntl_waitpid(0, $status) != -1) {
                $status = pcntl_wexitstatus($status);
                $this->logg("Child {$status} completed");
            }

            $output->writeLn("\r\nIt Worked!");
            return;
        }

        protected function signalHandler($signal)
        {
            echo "\r\nCaught a signal!";
            $this->continueFlag = false;
            return;
        }

        protected function alarmHandler($signal)
        {
            pcntl_alarm(10);
            return;
        }

        protected function sigusr1Handler($singal)
        {
            $this->logg("Caught SIGUSR1");

            for ($i = 1; $i <= 5; ++$i) {
                $pid = pcntl_fork();
        
                if (!$pid) {
                    sleep(10+($i*2));
                    $this->logg("In child" . $i);
                    exit($i);
                }
            }
        
            while (pcntl_waitpid(0, $status) != -1) {
                $status = pcntl_wexitstatus($status);
                $this->logg("Child {$status} completed");
            }
        }

        protected function sigusr2Handler($singal)
        {
            $this->logg("Caught SIGUSR2");
        }

        protected function sigchldHandler($singal)
        {
            $this->logg("Caught SIGCHLD");
        }

        protected function logg($msg)
        {
            $logLocation = __DIR__ . '/../../workLog.log';
            file_put_contents($logLocation, $msg . "\r\n", FILE_APPEND);
            unset($logLocation);
        }

        protected function changeLogg()
        {
            /**
             * Set date for file name.
             */
            $date = new DateTime;
            $date = date_sub($date,  new DateInterval("P1D"));
            /**
             * Move and rename file.
             */
            $dateSTR = $date->format('Y-m-d');
            rename(__DIR__ . "/../../workLog.log", __DIR__ . "/../../Logs/workLog_" . $dateSTR . ".log");
            /**
             * Set date for deletion of old files.
             */
            $date = date_sub($date, new DateInterval("P14D"));
            /**
             * Delete old files if they exist.
             */
            $dateSTR = $date->format('Y-m-d');
            if (file_exists(__DIR__ . "/../../Logs/workLog_" . $dateSTR . ".log"))
                unlink(__DIR__ . "/../../Logs/workLog_" . $dateSTR . ".log");
            /**
             * Clean up variables.
             */
            unset($date,$dateSTR);
        }
    }
?>
