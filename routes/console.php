<?php

use App\Models\User;
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

// Schedule the fragment scheduler to process user-created schedules
Schedule::command('frag:scheduler:tick')
    ->everyMinute()
    ->name('fragment-scheduler')
    ->description('Process user-created scheduled tasks')
    ->withoutOverlapping()
    ->onSuccess(function () {
        // Only log when schedules are actually processed
    })
    ->onFailure(function () {
        error('Fragment scheduler tick failed');
    });

// Schedule Readwise sync (runs only when token configured)
Schedule::command('readwise:sync')
    ->dailyAt('02:30')
    ->timezone('UTC')
    ->name('readwise-sync')
    ->description('Ingest Readwise highlights into Fragments Engine')
    ->when(function () {
        $user = User::query()->first();
        if (! $user) {
            return false;
        }

        $settings = $user->profile_settings['integrations']['readwise'] ?? [];

        return ! empty($settings['api_token']) && ($settings['sync_enabled'] ?? false);
    });

// Schedule Obsidian vault sync (runs only when vault path configured)
Schedule::command('obsidian:sync')
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->name('obsidian-sync')
    ->description('Sync Obsidian vault notes into Fragments Engine')
    ->when(function () {
        $user = User::query()->first();
        if (! $user) {
            return false;
        }

        $settings = $user->profile_settings['integrations']['obsidian'] ?? [];

        return ! empty($settings['vault_path']) && ($settings['sync_enabled'] ?? false);
    });

// Schedule Hardcover sync (runs only when token configured)
Schedule::command('hardcover:sync')
    ->dailyAt('03:30')
    ->timezone('UTC')
    ->name('hardcover-sync')
    ->description('Ingest Hardcover book library into Fragments Engine')
    ->when(function () {
        $user = User::query()->first();
        if (! $user) {
            return false;
        }

        $settings = $user->profile_settings['integrations']['hardcover'] ?? [];

        return ! empty($settings['bearer_token']) && ($settings['sync_enabled'] ?? false);
    });

// Schedule Readwise Reader sync (runs only when token configured)
Schedule::command('readwise:reader:sync')
    ->dailyAt('02:00')
    ->timezone('UTC')
    ->name('readwise-reader-sync')
    ->description('Ingest Readwise Reader documents (articles, RSS, etc.) into Fragments Engine')
    ->when(function () {
        $user = User::query()->first();
        if (! $user) {
            return false;
        }

        $settings = $user->profile_settings['integrations']['readwise'] ?? [];

        return ! empty($settings['api_token']) && ($settings['reader_sync_enabled'] ?? false);
    });

// Legacy alias support for orchestration task listing
Artisan::command('orch:task-list
        {--sprint=* : Filter by sprint codes or numbers}
        {--delegation-status=* : Filter by delegation status (completed, in_progress, assigned, blocked, unassigned)}
        {--status=* : Filter by work item status}
        {--agent= : Filter by recommended agent slug/name}
        {--search= : Match task code or description text}
        {--limit=20 : Maximum number of tasks to display}
        {--json : Output JSON instead of a table}', function () {
    $options = $this->options();

    unset($options['command']);

    foreach ($options as $key => $value) {
        if (is_bool($value) && $value === false) {
            unset($options[$key]);

            continue;
        }

        if ($value === null) {
            unset($options[$key]);

            continue;
        }

        if (is_array($value) && $value === []) {
            unset($options[$key]);
        }
    }

    return $this->call('orchestration:tasks', $options);
})->purpose('Alias for orchestration:tasks to retain legacy namespace compatibility');
