<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'setup.complete' => \App\Http\Middleware\EnsureUserSetupComplete::class,
            'default.user' => \App\Http\Middleware\EnsureDefaultUser::class,
            'correlation' => \App\Http\Middleware\InjectCorrelationId::class,
        ]);

        // Apply correlation middleware globally
        $middleware->append([
            \App\Http\Middleware\InjectCorrelationId::class,
        ]);

        // Apply middlewares to web routes
        $middleware->web(append: [
            \App\Http\Middleware\EnsureDefaultUser::class,
            \App\Http\Middleware\EnsureUserSetupComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
