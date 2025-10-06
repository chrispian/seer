<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule sync of providers and models from models.dev API
Schedule::command('sync:providers-models --queue')
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->name('sync-providers-models')
    ->description('Sync providers and models from models.dev API')
    ->onSuccess(function () {
        info('Successfully synced providers and models from models.dev');
    })
    ->onFailure(function () {
        error('Failed to sync providers and models from models.dev');
    });

// Schedule sync of local Ollama models
Schedule::command('sync:ollama-local --queue')
    ->dailyAt('03:15')
    ->timezone('UTC')
    ->name('sync-ollama-local')
    ->description('Sync models from local Ollama installation')
    ->onSuccess(function () {
        info('Successfully synced local Ollama models');
    })
    ->onFailure(function () {
        error('Failed to sync local Ollama models');
    });


