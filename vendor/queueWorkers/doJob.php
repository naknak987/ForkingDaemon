<?php

use Pheanstalk\Pheanstalk;

class doJob
{
    protected $q = null;

    public function __construct()
    {
        $this->q = new Pheanstalk('localhost', '11300', null, true);
    }

    public function __destruct()
    {
        $this->q = null;
    }

    public function executeJob()
    {
        /**
         * This function will pull jobs out of the queue and complete them.
         * It should only run in the child loop above.
         */
        echo "_";
        $job = $this->q
            ->watch('testtube')
            ->ignore('default')
            ->reserve();
        
        if ($job !== false)
        {
            echo $job->getData();
            sleep($job->getData());
        
            $this->q->delete($job);
        }
    }

}

?>