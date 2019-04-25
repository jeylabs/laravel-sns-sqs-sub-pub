<?php

namespace Jeylabs\SnsSqsPubSub;

use Illuminate\Support\ServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Queue\QueueManager;
use Jeylabs\SnsSqsPubSub\Queue\Connectors\SnsConnector;
use Jeylabs\SnsSqsPubSub\Queue\JobMap;

class SnsSqsPubSubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * @return void
     */
    public function register()
    {
        $this->publishConfig();
    }

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot()
    {
        $this->app->afterResolving(QueueManager::class, function (QueueManager $manager) {
            $config = $this->app->make(Repository::class);
            $manager->addConnector('sns-sqs-sub-pub', function () use ($config) {
                $map = new JobMap($config->get('sns-sqs-sub-pub.map'));
                return new SnsConnector($map);
            });
        });
    }

    /**
     * publish the configuration file
     */
    private function publishConfig()
    {
        $this->publishes([
            __DIR__.'/../config/sns-sqs-sub-pub.php' => config_path('sns-sqs-sub-pub.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../config/sns-sqs-sub-pub.php', 'sns-sqs-sub-pub');
    }
}
