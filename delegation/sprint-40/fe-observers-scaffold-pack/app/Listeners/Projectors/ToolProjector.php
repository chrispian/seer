<?php

namespace App\Listeners\Projectors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\Tools\ToolInvoked;
use App\Events\Tools\ToolCompleted;

class ToolProjector
{
    public function onToolInvoked(ToolInvoked $e): void
    {
        DB::table('tool_activity')->insert([
            'id' => (string) Str::uuid(),
            'tool' => $e->tool,
            'invocation_id' => $e->invocationId,
            'status' => 'running',
            'duration_ms' => null,
            'command_slug' => $e->commandSlug,
            'fragment_id' => $e->fragmentId,
            'workspace_id' => $e->workspaceId,
            'user_id' => $e->userId,
            'ts' => now(),
        ]);
    }

    public function onToolCompleted(ToolCompleted $e): void
    {
        $day = date('Y-m-d');
        $row = DB::table('tool_metrics_daily')->where(['day'=>$day,'tool'=>$e->tool])->first();
        $data = [
            'invocations' => ($row->invocations ?? 0) + 1,
            'errors' => ($row->errors ?? 0) + ($e->status === 'ok' ? 0 : 1),
            'duration_ms_sum' => ($row->duration_ms_sum ?? 0) + (int) $e->durationMs,
            'duration_ms_count' => ($row->duration_ms_count ?? 0) + 1,
        ];
        if ($row) DB::table('tool_metrics_daily')->where(['day'=>$day,'tool'=>$e->tool])->update($data);
        else DB::table('tool_metrics_daily')->insert(array_merge(['day'=>$day,'tool'=>$e->tool], $data));

        DB::table('tool_activity')->insert([
            'id' => (string) Str::uuid(),
            'tool' => $e->tool,
            'invocation_id' => $e->invocationId,
            'status' => $e->status,
            'duration_ms' => (int) $e->durationMs,
            'command_slug' => $e->commandSlug,
            'fragment_id' => $e->fragmentId,
            'workspace_id' => $e->workspaceId,
            'user_id' => $e->userId,
            'ts' => now(),
        ]);
    }
}
