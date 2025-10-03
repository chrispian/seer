<?php

namespace App\Listeners\Projectors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\Commands\CommandStarted;
use App\Events\Commands\CommandCompleted;

class CommandProjector
{
    public function onCommandStarted(CommandStarted $e): void
    {
        $id = (string) ($e->runId ?? Str::uuid());
        DB::table('command_runs')->insert([
            'id' => $id,
            'slug' => $e->slug,
            'status' => 'running',
            'workspace_id' => $e->workspaceId,
            'user_id' => $e->userId,
            'started_at' => now(),
        ]);

        DB::table('command_activity')->insert([
            'id' => (string) Str::uuid(),
            'slug' => $e->slug,
            'action' => 'started',
            'run_id' => $id,
            'workspace_id' => $e->workspaceId,
            'user_id' => $e->userId,
            'payload' => json_encode(null),
            'ts' => now(),
        ]);
    }

    public function onCommandCompleted(CommandCompleted $e): void
    {
        $id = $e->runId ?? null;
        if ($id) {
            DB::table('command_runs')->where('id',$id)->update([
                'status' => $e->status,
                'duration_ms' => (int) $e->durationMs,
                'finished_at' => now(),
            ]);
        }
        DB::table('command_activity')->insert([
            'id' => (string) Str::uuid(),
            'slug' => $e->slug,
            'action' => 'completed',
            'run_id' => $id,
            'workspace_id' => $e->workspaceId,
            'user_id' => $e->userId,
            'payload' => json_encode(['status'=>$e->status,'error'=>$e->error]),
            'ts' => now(),
        ]);
    }
}
