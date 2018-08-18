<?php
    namespace Daemon\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument as InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface as OutputInterface;

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    use Daemon\QueueJob;
    use Daemon\DoJob;

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
                        $this->logg("I'm Number {$i} and I do work!");
                        $dowork = new doJob;
                        $dowork->executeJob();
                        $dowork = null;
                        pcntl_signal_dispatch();
                    } while ($this->continueFlag);
                    exit($i);
                }
            }
        
            do {
                /**
                 * This is the parent process loop.
                 */
                $this->logg("I'm handing out work!");
                $seek = new queueJob;
                $seek->jobSeek();
                $seek = null;
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
        }

        protected function sigusr2Handler($singal)
        {
            $this->logg("Caught SIGUSR2");
        }

        protected function sigchldHandler($singal)
        {
            $this->logg("Caught SIGCHLD");
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