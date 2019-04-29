<?php


namespace Jeylabs\SnsSqsPubSub\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Arr;
use Jeylabs\SnsSqsPubSub\Queue\JobMap;

class SnsJob extends SqsJob
{
    /** @var JobMap  */
    private $map;

    /**
     * SnsJob constructor.
     * @param Container $container
     * @param SqsClient $sqs
     * @param array $job
     * @param string $connectionName
     * @param string $queue
     * @param JobMap $map
     */
    public function __construct(Container $container, SqsClient $sqs, array $job, string $connectionName, string $queue, JobMap $map)
    {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);
        $this->map = $map;
    }

    /**
     * @return false|string
     * @throws Exception
     */
    public function getRawBody()
    {
        $realBody = json_decode(Arr::get($this->job, 'Body'), true);

        if (!isset($realBody['TopicArn'])){
            return $this->job['Body'];
        }

        $class = $this->map->fromTopic($realBody['TopicArn']);
        $message = json_decode(Arr::get($realBody, 'Message'), true);

        $transformedBody = json_encode([
            "job" => "Illuminate\Queue\CallQueuedHandler@call",
            "data" => [
                "commandName" => $class,
                "command" => serialize(new $class($message))
            ]
        ]);
        return $transformedBody;
    }
}

