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

        sleep(2);
        echo ".";
        $this->q
            ->useTube('testtube')
            ->put("3", 10, 0, 2);
    }
}

?>