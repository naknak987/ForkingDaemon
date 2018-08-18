<?php 

use Pheanstalk\Pheanstalk;

class queueJob
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

    public function jobSeek()
    {
        /**
         * This function will search for new jobs to add to the queue.
         * It should only run in the parent loop above. 
         */
            /**
             * Puts a job on the queue.
             *
             * @param string $data     The job data
             * @param int    $priority From 0 (most urgent) to 0xFFFFFFFF (least urgent)
             * @param int    $delay    Seconds to wait before job becomes ready
             * @param int    $ttr      Time To Run: seconds a job can be reserved for
             */

        sleep(2);
        echo ".";
        $this->q
            ->useTube('testtube')
            ->put("3", 10, 0, 5);
    }
}

?>