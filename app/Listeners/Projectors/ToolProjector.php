<?php

namespace App\Listeners\Projectors;

use App\Events\Tools\ToolCompleted;
use App\Events\Tools\ToolInvoked;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolProjector
{
    public function onToolInvoked(ToolInvoked $event): void
    {
        $today = now()->format('Y-m-d');

        $existing = DB::table('tool_metrics_daily')
            ->where('day', $today)
            ->where('tool', $event->tool)
            ->first();

        if ($existing) {
            DB::table('tool_metrics_daily')
                ->where('day', $today)
                ->where('tool', $event->tool)
                ->update(['invocations' => $existing->invocations + 1]);
        } else {
            DB::table('tool_metrics_daily')->insert([
                'day' => $today,
                'tool' => $event->tool,
                'invocations' => 1,
                'errors' => 0,
                'duration_ms_sum' => 0,
                'duration_ms_count' => 0,
            ]);
        }

        DB::table('tool_activity')->insert([
            'id' => (string) Str::uuid(),
            'tool' => $event->tool,
            'invocation_id' => $event->invocationId,
            'status' => 'running',
            'command_slug' => $event->commandSlug,
            'fragment_id' => $event->fragmentId,
            'user_id' => $event->userId,
            'ts' => now(),
        ]);
    }

    public function onToolCompleted(ToolCompleted $event): void
    {
        $today = now()->format('Y-m-d');

        $existing = DB::table('tool_metrics_daily')
            ->where('day', $today)
            ->where('tool', $event->tool)
            ->first();

        if ($existing) {
            $updateData = [
                'duration_ms_sum' => $existing->duration_ms_sum + $event->durationMs,
                'duration_ms_count' => $existing->duration_ms_count + 1,
            ];

            if ($event->status !== 'ok') {
                $updateData['errors'] = $existing->errors + 1;
            }

            DB::table('tool_metrics_daily')
                ->where('day', $today)
                ->where('tool', $event->tool)
                ->update($updateData);
        } else {
            DB::table('tool_metrics_daily')->insert([
                'day' => $today,
                'tool' => $event->tool,
                'invocations' => 0,
                'errors' => $event->status !== 'ok' ? 1 : 0,
                'duration_ms_sum' => $event->durationMs,
                'duration_ms_count' => 1,
            ]);
        }

        // Update the activity record if we have an invocation ID
        if ($event->invocationId) {
            DB::table('tool_activity')
                ->where('invocation_id', $event->invocationId)
                ->update([
                    'status' => $event->status,
                    'duration_ms' => $event->durationMs,
                ]);
        } else {
            // Create a new completion record
            DB::table('tool_activity')->insert([
                'id' => (string) Str::uuid(),
                'tool' => $event->tool,
                'invocation_id' => $event->invocationId,
                'status' => $event->status,
                'duration_ms' => $event->durationMs,
                'command_slug' => $event->commandSlug,
                'fragment_id' => $event->fragmentId,
                'user_id' => $event->userId,
                'ts' => now(),
            ]);
        }
    }
}
