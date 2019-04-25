<?php


namespace Jeylabs\SnsSqsPubSub\Queue;

use Illuminate\Contracts\Queue\Job;
use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;
use Jeylabs\SnsSqsPubSub\Queue\Jobs\SnsJob;

class SnsQueue extends SqsQueue
{
    /**
     * @var JobMap
     */
    private $map;

    /**
     * SnsQueue constructor.
     * @param SqsClient $sqs
     * @param string $default
     * @param JobMap $map
     */
    public function __construct(SqsClient $sqs, string $default, JobMap $map)
    {
        parent::__construct($sqs, $default);
        $this->map = $map;
    }

    /**
     * @param null $queue
     * @return Job|SnsJob|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (!is_null($response['Messages']) && count($response['Messages']) > 0) {
            return new SnsJob(
                $this->container,
                $this->sqs,
                $response['Messages'][0],
                $this->connectionName,
                $queue,
                $this->map
            );
        }
    }
}

