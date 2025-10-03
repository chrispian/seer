<?php

namespace App\Listeners\Projectors;

use App\Events\Commands\CommandStarted;
use App\Events\Commands\CommandCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommandProjector
{
    public function onCommandStarted(CommandStarted $event): void
    {
        $runId = $event->runId && Str::isUuid($event->runId) ? $event->runId : (string) Str::uuid();
        
        DB::table('command_runs')->insert([
            'id' => $runId,
            'slug' => $event->slug,
            'status' => 'running',
            'user_id' => $event->userId,
            'started_at' => now(),
        ]);

        DB::table('command_activity')->insert([
            'id' => (string) Str::uuid(),
            'slug' => $event->slug,
            'action' => 'started',
            'run_id' => $runId,
            'user_id' => $event->userId,
            'ts' => now(),
        ]);
    }

    public function onCommandCompleted(CommandCompleted $event): void
    {
        if ($event->runId) {
            DB::table('command_runs')
                ->where('id', $event->runId)
                ->update([
                    'status' => $event->status,
                    'duration_ms' => $event->durationMs,
                    'finished_at' => now(),
                ]);
        }

        DB::table('command_activity')->insert([
            'id' => (string) Str::uuid(),
            'slug' => $event->slug,
            'action' => 'completed',
            'run_id' => $event->runId,
            'user_id' => $event->userId,
            'payload' => json_encode([
                'status' => $event->status,
                'duration_ms' => $event->durationMs,
            ]),
            'ts' => now(),
        ]);
    }
}