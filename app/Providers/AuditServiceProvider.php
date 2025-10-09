<?php

namespace App\Providers;

use App\Listeners\CommandLoggingListener;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(CommandStarting::class, [CommandLoggingListener::class, 'handleCommandStarting']);
        Event::listen(CommandFinished::class, [CommandLoggingListener::class, 'handleCommandFinished']);
    }
}
