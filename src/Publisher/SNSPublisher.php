<?php


namespace Jeylabs\SnsSqsPubSub\Publisher;

use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Jeylabs\SnsSqsPubSub\SNS\Client;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Class SNSPublisher
 * @package Jeylabs\SnsSqsPubSub\Publisher
 */
class SNSPublisher
{
    /** @var AuthManager */
    protected $auth;
    /** @var Collection */
    protected $properties;

    /** @var Model */
    protected $performedOn;

    /** @var string */
    protected $topicArn;

    /** @var string */
    protected $event;

    /** @var Authenticatable|null  */
    protected $authUser;

    /** @var Client */
    protected $SNSClient;

    /**
     * SNSPublisher constructor.
     * @param AuthManager $auth
     * @param Repository $config
     * @param Client $SNSClient
     */
    public function __construct(AuthManager $auth, Repository $config, Client $SNSClient)
    {
        $this->auth = $auth;
        $this->topicArn = $config->get('sns-sqs-sub-pub.default_topic');
        $this->SNSClient = $SNSClient;

        $authDriver = $config->get('sns-sqs-sub-pub.default_auth_driver') ?? $auth->getDefaultDriver();
        if (Str::startsWith(app()->version(), '5.1')) {
            $this->authUser = $auth->driver($authDriver)->user();
        } else {
            $this->authUser = $auth->guard($authDriver)->user();
        }
    }

    /**
     * @param Model $model
     * @return $this
     */
    public function performedOn(Model $model)
    {
        $this->performedOn = $model;
        return $this;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function withProperties(array $properties = [])
    {
        if (!count($properties)) return $this;
        $this->properties = $properties;
        return $this;
    }

    /**
     * @param string $topic
     * @return $this
     */
    public function withTopic(string $topic = null)
    {
        if (!$topic) return $this;
        $this->topicArn = $topic;
        return $this;
    }

    /**
     * @param string $event
     * @return $this
     */
    public function withEvent(string $event)
    {
        if (!$event) return $this;
        $this->event = $event;
        return $this;
    }

    /**
     * @param string $topicArn
     * @param array $properties
     * @throws Exception
     */
    public function publish(string $topicArn = '', array $properties = [])
    {
        $this->withTopic($topicArn);
        $this->withProperties($properties);
        if (!$this->topicArn) {
            throw new Exception('Message does not have Topic ARN');
        }
        $this->SNSClient->publish($this->topicArn, $this->generateMessage());
    }

    /**
     * @return false|string
     */
    private function generateMessage(){
        $messageArray = ['data' => $this->properties];
        if ($this->event) $messageArray['model_event'] = $this->event;
        if ($this->performedOn) $messageArray['model'] = class_basename($this->performedOn);
        if ($this->authUser){
            $messageArray['user'] = ['id' => $this->authUser->id];
        }
        return json_encode($messageArray);
    }
}
