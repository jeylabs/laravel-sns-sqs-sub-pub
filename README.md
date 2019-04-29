# AWS SQS SNS Subscription Queue and publisher

simple extension to the [Illuminate/Queue](https://github.com/illuminate/queue) queue system used in [Laravel](https://laravel.com) and [Lumen](https://lumen.laravel.com/).

Using this connector allows [SQS](https://aws.amazon.com/sqs/) messages originating from a [SNS](https://aws.amazon.com/sns/) subscription to be worked on with Illuminate\Queue\Jobs\SqsJob.

This is especially useful in a miroservice architecture where multiple services subscribe to a common topic with their queues and  publish an event to SNS.

## Requirements

-   Laravel (tested with version 5.8)
-   or Lumen (tested with version 5.8)

## Installation
You can install the package via composer
```bash
composer require jeylabs/laravel-sns-sqs-sub-pub 
```
You can optionally publish the config file with:
```bash
php artisan vendor:publish --provider="Jeylabs\SnsSqsPubSub\SnsSqsPubSubServiceProvider" --tag="config"
```
This is the contents of the published config file:

```php
<?php
return [
    'default_topic' => 'SampleTopic',
    'default_auth_driver' => null,
    'map' => [
        \App\Jobs\TestSQSJob::class => 'SampleTopic',
    ],
    'ignore_attributes' => [
        'created_at',
        'updated_at'
    ],
    'sns' => [
        'key' => env('SNS_SQS_PUB_SUB_AWS_ACCESS_KEY'),
        'secret' => env('SNS_SQS_PUB_SUB_AWS_SECRET_ACCESS_KEY'),
        'region' => env('SNS_SQS_PUB_SUB_AWS_DEFAULT_REGION', 'us-east-1'),
    ]
];

```

### Configuration

You'll need to configure the queue connection in your config/queue.php

```php
<?php
return [
    'connections' => [
      'sns-sqs-sub-pub' => [
          'driver' => 'sns-sqs-sub-pub',
          'key' => env('SNS_SQS_PUB_SUB_AWS_ACCESS_KEY'),
          'secret' => env('SNS_SQS_PUB_SUB_AWS_SECRET_ACCESS_KEY'),
          'prefix' => env('SNS_SQS_PUB_SUB_SQS_PREFIX', 'https://sqs.ap-southeast-1.amazonaws.com/your-account-id'),
          'queue' => env('SNS_SQS_PUB_SUB_QUEUE_URL', 'your-queue-name'),
          'region' => env('SNS_SQS_PUB_SUB_AWS_DEFAULT_REGION', 'us-east-1'),
      ],
    ],
];
```
Once the sns-sqs-sub-pub queue connector is configured you can start using it by setting queue driver to 'sns-sqs-sub-pub' in your .env file.

### Job class example

```php
<?php
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class TestSQSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $passedInData;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        // $data is array containing the msg content from SQS
        $this->passedInData = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info(json_encode($this->passedInData));
        // Check laravel.log, it should now contain msg string.
    }
}

```

### Published event
```php
<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Jeylabs\SnsSqsPubSub\Traits\SNSPublisher;

class Post extends Model
{
    use SNSPublisher;

    /**
     * @var array
     * Optional (default value is [] )
     * Witch are the attributes should ignore from SNS message
     */
    static $ignorePublishedAttributes = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array
     * Optional  (default value is [created','updated','deleted','restored'] )
     * Witch events should send to SNS
     */
    static $publishEvents = ['created', 'updated'];

    /**
     * @var string
     * Optional (default value is load from config )
     * Publish SNS topic
     */
    static $publishTopic = 'SampleTopic';
    /**
     * Or
     * static $publishTopic = [
     *  'created' => 'SampleTopic'
     * ];
     */
}
```

### You'll need to configure .env file
```dotenv
SNS_SQS_PUB_SUB_AWS_SNS_DEFAULT_TOPIC=sampletopic
SNS_SQS_PUB_SUB_AWS_ACCESS_KEY=
SNS_SQS_PUB_SUB_AWS_SECRET_ACCESS_KEY=
SNS_SQS_PUB_SUB_AWS_DEFAULT_REGION=ap-southeast-1
SNS_SQS_PUB_SUB_SQS_PREFIX=https://sqs.ap-southeast-1.amazonaws.com/your-account-id
SNS_SQS_PUB_SUB_QUEUE_URL=https://sqs.ap-southeast-1.amazonaws.com/your-account-id/your-queue-name
```
