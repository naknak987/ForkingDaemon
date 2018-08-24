<?php 
/**
 * Queue Job
 *
 * This is the code that will send jobs to the queue. Build a json
 * string with your job definition in it using an array and the
 * json_encode function.
 *
 * PHP Version 7.1.19
 *
 * @category Job
 * @package  ForkingDaemon
 * @author   Matthew Goheen <matthew.goheen@guardianeldercare.net>
 * @license  MIT License (see https://www.tldrlegal.com/l/mit)
 * @link     https://github.com/naknak987/ForkingDaemon
 */
namespace Daemon\JobHandler;

use Pheanstalk\Pheanstalk;

/**
 * Queue Job
 *
 * Encode an array that has job definitions in it with json_encode
 * and put it into the queue.
 *
 * @category Job
 * @package  ForkingDaemon
 * @author   Matthew Goheen <matthew.goheen@guardianeldercare.net>
 * @license  MIT License (see https://www.tldrlegal.com/l/mit)
 * @link     https://github.com/naknak987/ForkingDaemon
 */
class QueueJob
{
    protected $queue = null;

    /**
     * Constructor
     *
     * Create new connection to the beanstalk server.
     *
     * @return null
     */
    public function __construct()
    {
        $this->queue = new Pheanstalk('localhost', '11300', null, true);
    }

    /**
     * Destructor
     *
     * Disconnect from the beanstalk server.
     *
     * @return null
     */
    public function __destruct()
    {
        unset($this->queue);
    }

    /**
     * Example Job
     *
     * This queues up an example job. The job will just wait for
     * three seconds to pass. 
     *
     * @return null
     */
    public function exampleJob()
    {
        /**
         * Parameter Definition for putting jobs in the queue
         *
         * @param string $data     The job data
         * @param int    $priority From 0 (most urgent) to 0xFFFFFFFF (least urgent)
         * @param int    $delay    Seconds to wait before job becomes ready
         * @param int    $ttr      Time To Run: seconds a job can be reserved for
         */

        $jobData = array(
            'Name' => 'Wait',
            'Time' => '3'
        );

        $this->queue
            ->useTube('testtube')
            ->put(
                json_encode($jobData),
                10,
                0,
                5
            );
    }
}

?>