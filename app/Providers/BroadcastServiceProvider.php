<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Pusher\Pusher;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make(BroadcastManager::class)->extend('pusher', function ($app, $config) {
            return new \Illuminate\Broadcasting\Broadcasters\PusherBroadcaster(
                new Pusher(
                    $config['key'],
                    $config['secret'],
                    $config['app_id'],
                    [
                        'cluster' => $config['options']['cluster'],
                        'encrypted' => $config['options']['encrypted'] ?? false,
                        'useTLS' => $config['options']['useTLS'] ?? false,
                        'host' => $config['options']['host'] ?? '127.0.0.1',
                        'port' => $config['options']['port'] ?? 6001,
                        'scheme' => $config['options']['scheme'] ?? 'http',
                        'curl_options' => $config['options']['curl_options'] ?? [],
                    ]
                )
            );
        });
    }
}
