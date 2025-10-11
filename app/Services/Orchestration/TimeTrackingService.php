<?php

namespace App\Services\Orchestration;

use App\Models\SessionActivity;
use App\Models\WorkItem;
use App\Models\WorkSession;
use Carbon\Carbon;

class TimeTrackingService
{
    public function startTracking(string $sessionId, string $taskId): void
    {
        $session = WorkSession::findOrFail($sessionId);

        SessionActivity::create([
            'session_id' => $sessionId,
            'activity_type' => 'command',
            'command' => 'time:start',
            'description' => 'Started time tracking for task',
            'task_id' => $taskId,
            'metadata' => [
                'started_at' => now()->toIso8601String(),
            ],
            'occurred_at' => now(),
        ]);
    }

    public function stopTracking(string $sessionId, string $taskId): array
    {
        $session = WorkSession::findOrFail($sessionId);

        $startActivity = SessionActivity::where('session_id', $sessionId)
            ->where('task_id', $taskId)
            ->where('command', 'time:start')
            ->latest('occurred_at')
            ->first();

        if (!$startActivity) {
            return [
                'tracked' => false,
                'reason' => 'No start activity found',
            ];
        }

        $startedAt = Carbon::parse($startActivity->metadata['started_at'] ?? $startActivity->occurred_at);
        $stoppedAt = now();
        $durationSeconds = $startedAt->diffInSeconds($stoppedAt);
        $durationHours = round($durationSeconds / 3600, 2);

        SessionActivity::create([
            'session_id' => $sessionId,
            'activity_type' => 'command',
            'command' => 'time:stop',
            'description' => 'Stopped time tracking for task',
            'task_id' => $taskId,
            'metadata' => [
                'started_at' => $startedAt->toIso8601String(),
                'stopped_at' => $stoppedAt->toIso8601String(),
                'duration_seconds' => $durationSeconds,
                'duration_hours' => $durationHours,
            ],
            'occurred_at' => now(),
        ]);

        $task = WorkItem::find($taskId);
        if ($task) {
            $currentActual = $task->actual_hours ?? 0;
            $task->update([
                'actual_hours' => $currentActual + $durationHours,
            ]);
        }

        return [
            'tracked' => true,
            'started_at' => $startedAt->toIso8601String(),
            'stopped_at' => $stoppedAt->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'duration_hours' => $durationHours,
            'total_actual_hours' => ($task->actual_hours ?? 0),
        ];
    }

    public function pauseTracking(string $sessionId, string $taskId): array
    {
        return $this->stopTracking($sessionId, $taskId);
    }

    public function getTotalTimeForTask(string $taskId, ?string $sessionId = null): array
    {
        $query = SessionActivity::where('task_id', $taskId)
            ->where('command', 'time:stop');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $stopActivities = $query->get();

        $totalSeconds = $stopActivities->sum(function ($activity) {
            return $activity->metadata['duration_seconds'] ?? 0;
        });

        $totalHours = round($totalSeconds / 3600, 2);

        $task = WorkItem::find($taskId);
        $estimated = $task?->estimated_hours;
        $variance = null;
        $variancePercent = null;

        if ($estimated && $totalHours > 0) {
            $variance = $totalHours - $estimated;
            $variancePercent = round(($variance / $estimated) * 100, 1);
        }

        return [
            'task_id' => $taskId,
            'total_seconds' => $totalSeconds,
            'total_hours' => $totalHours,
            'estimated_hours' => $estimated,
            'variance_hours' => $variance,
            'variance_percent' => $variancePercent,
            'sessions_count' => $stopActivities->count(),
        ];
    }

    public function getActiveTimeSession(string $taskId): ?array
    {
        $startActivity = SessionActivity::where('task_id', $taskId)
            ->where('command', 'time:start')
            ->latest('occurred_at')
            ->first();

        if (!$startActivity) {
            return null;
        }

        $stopActivity = SessionActivity::where('task_id', $taskId)
            ->where('command', 'time:stop')
            ->where('occurred_at', '>', $startActivity->occurred_at)
            ->first();

        if ($stopActivity) {
            return null;
        }

        $startedAt = Carbon::parse($startActivity->metadata['started_at'] ?? $startActivity->occurred_at);
        $currentSeconds = $startedAt->diffInSeconds(now());

        return [
            'session_id' => $startActivity->session_id,
            'started_at' => $startedAt->toIso8601String(),
            'elapsed_seconds' => $currentSeconds,
            'elapsed_hours' => round($currentSeconds / 3600, 2),
        ];
    }

    public function compareEstimateToActual(string $taskId): array
    {
        $task = WorkItem::find($taskId);

        if (!$task) {
            return [
                'exists' => false,
            ];
        }

        $estimated = $task->estimated_hours;
        $actual = $task->actual_hours ?? 0;

        if (!$estimated) {
            return [
                'exists' => true,
                'has_estimate' => false,
                'actual_hours' => $actual,
            ];
        }

        $variance = $actual - $estimated;
        $variancePercent = round(($variance / $estimated) * 100, 1);
        $isOverBudget = $actual > $estimated;
        $isSignificantlyOver = $variancePercent > 25;

        return [
            'exists' => true,
            'has_estimate' => true,
            'estimated_hours' => $estimated,
            'actual_hours' => $actual,
            'variance_hours' => $variance,
            'variance_percent' => $variancePercent,
            'is_over_budget' => $isOverBudget,
            'is_significantly_over' => $isSignificantlyOver,
            'suggestion' => $this->getSuggestion($variancePercent, $isOverBudget),
        ];
    }

    protected function getSuggestion(float $variancePercent, bool $isOverBudget): ?string
    {
        if (!$isOverBudget) {
            if ($variancePercent < -20) {
                return 'Task completed faster than estimated. Consider reducing estimates for similar tasks.';
            }
            return null;
        }

        if ($variancePercent > 50) {
            return 'Task took significantly longer than estimated. Review scope or complexity for future estimates.';
        }

        if ($variancePercent > 25) {
            return 'Task took longer than estimated. Consider adjusting estimates for similar tasks.';
        }

        return null;
    }
}
