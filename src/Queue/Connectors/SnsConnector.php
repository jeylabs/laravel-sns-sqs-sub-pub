<?php

namespace Jeylabs\SnsSqsPubSub\Queue\Connectors;

use Aws\Credentials\Credentials;
use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;
use Jeylabs\SnsSqsPubSub\Queue\JobMap;
use Jeylabs\SnsSqsPubSub\Queue\SnsQueue;
use Illuminate\Contracts\Queue\Queue;

class SnsConnector extends SqsConnector implements ConnectorInterface
{
    /**
     * @var JobMap
     */
    private $map;

    /**
     * SnsConnector constructor.
     * @param JobMap $map
     */
    public function __construct(JobMap $map)
    {
        $this->map = $map;
    }

    /**
     * @param array $config
     * @return Queue|SnsQueue
     */
    public function connect(array $config)
    {
        return new SnsQueue(
            $this->createClient($config), $config['queue'], $this->map
        );
    }

    /**
     * @param $config
     * @return SqsClient
     */
    private function createClient($config)
    {
        return new SqsClient([
            'version' => '2012-11-05',
            'region' => Arr::get($config, 'region'),
            'credentials' => new Credentials(
                Arr::get($config, 'key'),
                Arr::get($config, 'secret')
            )
        ]);
    }
}
