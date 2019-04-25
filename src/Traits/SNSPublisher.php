<?php

namespace Jeylabs\SnsSqsPubSub\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Jeylabs\SnsSqsPubSub\Publisher\SNSPublisher as SnsSqsPubSubSNSPublisher;

/**
 * Trait SNSPublisher
 * @package Jeylabs\SnsSqsPubSub\Traits
 */
trait SNSPublisher
{

    public static function bootSNSPublisher()
    {
        static::eventsToBePublish()->each(function ($eventName) {
            return static::$eventName(function (Model $model) use ($eventName) {
                if (!$model->shouldPublishEvent($eventName)) {
                    return;
                }
                try{
                    app(SnsSqsPubSubSNSPublisher::class)
                        ->performedOn($model)
                        ->withEvent($eventName)
                        ->withTopic($model->topicToPublish($eventName))
                        ->withProperties($model->attributeValuesToBePublish())
                        ->publish();
                }catch (\Exception $e){

                }
            });
        });
    }

    /**
     * @return Collection
     */
    protected static function eventsToBePublish(): Collection
    {
        if (isset(static::$publishEvents)) {
            return collect(static::$publishEvents);
        }
        $events = collect([
            'created',
            'updated',
            'deleted',
        ]);
        if (collect(class_uses(__CLASS__))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }
        return $events;
    }

    /**
     * @return array
     */
    public function attributesToBeIgnoredInMessage(): array
    {
        if (!isset(static::$ignorePublishedAttributes)) {
            return config('sns-sqs-sub-pub.ignore_attributes', []);
        }
        return array_merge(
            static::$ignorePublishedAttributes,
            config('sns-sqs-sub-pub.ignore_attributes', [])
        );
    }

    /**
     * @param $eventName
     * @return mixed|null
     */
    protected function topicToPublish($eventName)
    {
        if (isset(static::$publishTopic)) {
            if (is_string(static::$publishTopic)) {
                return static::$publishTopic;
            }
            if (is_array(static::$publishTopic)) {
                return Arr::get(static::$publishTopic, $eventName);
            }
        }
        return config('sns-sqs-sub-pub.default_topic', "");
    }

    /**
     * @return array
     */
    protected function attributeValuesToBePublish()
    {
        return Arr::except($this->getAttributes(), $this->attributesToBeIgnoredInMessage());
    }

    /**
     * @param string $eventName
     * @return bool
     */
    protected function shouldPublishEvent(string $eventName): bool
    {
        if (!in_array($eventName, ['created', 'updated'])) {
            return true;
        }
        if (Arr::has($this->getDirty(), 'deleted_at')) {
            if ($this->getDirty()['deleted_at'] === null) {
                return false;
            }
        }

        //do not published if only ignored attributes are changed
        return (bool)count(Arr::except($this->getDirty(), $this->attributesToBeIgnoredInMessage()));
    }
}
