<?php
return [
    'default_topic' => env('SNS_SQS_PUB_SUB_AWS_SNS_DEFAULT_TOPIC'),
    'default_auth_driver' => null,
    'map' => [
        \App\Jobs\TestSQSJob::class => 'arn:aws:sns:ap-southeast-1:931616835216:modelEvent',
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
