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
        protected $continueFlag     = true;
        protected $pid              = null;
        protected $pidFileLocation  = __DIR__ . '/../../pid.pid';

        protected function configure()
        {
            $this->setName('Start')
                ->setDescription('Start Command. Starts the Resident Discharge Booklet daemon.')
                ->setHelp('Start');
            return;
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $this->pid = getmypid();
            $this->savePID();

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
                    $this->savePID(getmypid());
                    do {
                        /**
                         * This is the child process loop.
                         */
                        echo "_";
                        $this->logg("{$i} Working on a job.");
                        $job = $pheanstalk
                            ->watch('testtube')
                            ->ignore('default')
                            ->reserve(2);
                        
                        if ($job !== false)
                        {
                            echo $job->getData();
                            sleep($job->getData());
                            $pheanstalk->delete($job);
                        }

                        sleep(2);
                        pcntl_signal_dispatch();
                    } while ($this->continueFlag);
                    exit($i);
                }
            }
        
            do {
                /**
                 * This is the parent process loop.
                 */
                sleep(6);
                $this->jobSeek();
                pcntl_signal_dispatch();
            } while ($this->continueFlag);

            $pheanstalk = null;

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
        }

        protected function sigusr2Handler($singal)
        {
            $this->logg("Caught SIGUSR2");
        }

        protected function sigchldHandler($singal)
        {
            $this->logg("Caught SIGCHLD");
        }

        protected function jobSeek()
        {
            /**
             * This function will search for new jobs to add to the queue.
             * It should only run in the parent loop above. 
             */

            echo ".";
            $this->logg("checking for new jobs that need done.");
            $pheanstalk
                ->useTube('testtube')
                ->put("2");
        }

        protected function executeJob()
        {
            /**
             * This function will pull jobs out of the queue and complete them.
             * It should only run in the child loop above.
             */
        }

        protected function savePID($pid = null)
        {
            if ($pid === null)
                $pid = $this->pid;
            file_put_contents($this->pidFileLocation, $pid . "\r\n", FILE_APPEND);
            unset($logLocation);
        }

        protected function shutdown()
        {
            /* if (false != $pidFile = file_get_contents($this->pidFileLocation))
            {
                $pids = explode("\r\n", $pidFile);
                for ($i = count($pids); $i > 0; $i--)
                {
                    posix_kill((int)$pids[$i-1], SIGINT);
                }
            } */

            unlink($this->pidFileLocation);

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
