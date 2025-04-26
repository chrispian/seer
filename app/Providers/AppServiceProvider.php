<?php

namespace App\Providers;

use App\Broadcasting\CustomPusherBroadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::extend('pusher', function ($app, $config) {
            return new CustomPusherBroadcaster(
                new \Pusher\Pusher(
                    $config['key'],
                    $config['secret'],
                    $config['app_id'],
                    $config['options'] ?? []
                )
            );
        });    }
}
