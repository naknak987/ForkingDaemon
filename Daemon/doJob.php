<?php
/**
 * Do Job
 *
 * This is the code that will pull jobs from beanstalkd and 
 * execute the job. The code here depends a lot on the job
 * what you're trying to accoplish with the daemon.
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
 * Do Job
 *
 * This is where you would define methods for the jobs you
 * want the daemon to be able to handle.
 *
 * @category Job
 * @package  ForkingDaemon
 * @author   Matthew Goheen <matthew.goheen@guardianeldercare.net>
 * @license  MIT License (see https://www.tldrlegal.com/l/mit)
 * @link     https://github.com/naknak987/ForkingDaemon
 */
class DoJob
{
    protected $queue = null;

    /**
     * Constructor
     *
     * Create a connection to the beanstalk server.
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
     * Execute Job
     *
     * Fetch a job from the queue, and route it to the 
     * correct job handler.
     *
     * @return null
     */
    public function executeJob()
    {
        // Fetch a job
        echo "_";
        $job = $this->queue
            ->watch('testtube')
            ->ignore('default')
            ->reserve();
        // Ensure we got a job.
        if ($job !== false) {
            // Extract job data.
            $jobData = json_decode($job->getData());
            
            switch ($jobData->Name) {
            case 'Wait':
                $result = $this->wait($jobData->Time);
                break;
            
            default:
                // Job definition unknown.
                break;
            }
            
            if (isset($result) && $result === true) {
                // Job succeeded. Remove it.
                $this->queue->delete($job);
            } else {
                // Job Failed. Bury it. 
                $this->queue->bury($job);
            }
        }
    }

    /**
     * Wait
     *
     * This performs the actions described in our example job.
     *
     * @param array $ttw The time to wait.
     *
     * @return bool
     */
    public function wait($ttw)
    {
        try{
            sleep($ttw);
            return true;
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }
}

?>