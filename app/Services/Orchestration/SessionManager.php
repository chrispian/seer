<?php

namespace App\Services\Orchestration;

use App\Models\SessionActivity;
use App\Models\SessionContextHistory;
use App\Models\WorkSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionManager
{
    public function __construct(
        protected SessionContextStack $contextStack
    ) {}

    public function startSession(array $data): WorkSession
    {
        $sessionKey = $this->generateSessionKey();

        $session = WorkSession::create([
            'session_key' => $sessionKey,
            'agent_id' => $data['agent_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'chat_session_id' => $data['chat_session_id'] ?? null,
            'source' => $data['source'] ?? 'api',
            'session_type' => $data['session_type'] ?? 'work',
            'status' => 'active',
            'context_stack' => [],
            'metadata' => $data['metadata'] ?? [],
            'started_at' => now(),
        ]);

        $this->logActivity($session->id, 'command', [
            'command' => 'session:start',
            'description' => 'Work session started',
        ]);

        return $session;
    }

    public function resumeSession(string $sessionKey): WorkSession
    {
        $session = WorkSession::where('session_key', $sessionKey)->firstOrFail();

        if ($session->status === 'paused') {
            $pausedAt = $session->paused_at;
            $now = now();

            $session->update([
                'status' => 'active',
                'resumed_at' => $now,
                'paused_at' => null,
            ]);

            $this->logActivity($session->id, 'resume', [
                'description' => 'Session resumed',
                'metadata' => [
                    'paused_duration_seconds' => $pausedAt->diffInSeconds($now),
                ],
            ]);
        }

        return $session;
    }

    public function pauseSession(string $sessionId, ?string $reason = null): WorkSession
    {
        $session = WorkSession::findOrFail($sessionId);

        $activeDuration = $session->started_at->diffInSeconds(now());

        $session->update([
            'status' => 'paused',
            'paused_at' => now(),
            'total_active_seconds' => $session->total_active_seconds + $activeDuration,
            'metadata' => array_merge($session->metadata ?? [], [
                'pause_reason' => $reason,
            ]),
        ]);

        $this->logActivity($session->id, 'pause', [
            'description' => $reason ?? 'Session paused',
        ]);

        return $session->fresh();
    }

    public function endSession(string $sessionId, array $data = []): WorkSession
    {
        $session = WorkSession::findOrFail($sessionId);

        $totalDuration = $this->calculateTotalDuration($session);

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'total_active_seconds' => $totalDuration,
            'summary' => $data['summary'] ?? null,
        ]);

        $this->logActivity($session->id, 'command', [
            'command' => 'session:end',
            'description' => 'Session ended',
            'metadata' => [
                'total_duration_seconds' => $totalDuration,
            ],
        ]);

        return $session->fresh();
    }

    public function pushContext(string $sessionId, string $type, string $id, array $data = []): void
    {
        $session = WorkSession::findOrFail($sessionId);

        $this->contextStack->push($session, $type, $id, $data);

        $this->updateActivePointers($session);

        SessionContextHistory::create([
            'session_id' => $sessionId,
            'action' => 'push',
            'context_type' => $type,
            'context_id' => $id,
            'context_data' => $data,
            'switched_at' => now(),
        ]);

        $this->logActivity($sessionId, 'context_update', [
            'description' => "Activated {$type}: {$id}",
            'metadata' => [
                'context_type' => $type,
                'context_id' => $id,
            ],
        ]);
    }

    public function popContext(string $sessionId, string $type): ?array
    {
        $session = WorkSession::findOrFail($sessionId);

        $popped = $this->contextStack->pop($session, $type);

        if ($popped) {
            $this->updateActivePointers($session);

            SessionContextHistory::create([
                'session_id' => $sessionId,
                'action' => 'pop',
                'context_type' => $type,
                'context_id' => $popped['id'],
                'context_data' => $popped,
                'switched_at' => now(),
            ]);

            $this->logActivity($sessionId, 'context_update', [
                'description' => "Deactivated {$type}: {$popped['id']}",
            ]);
        }

        return $popped;
    }

    public function getActiveContext(string $sessionId, ?string $type = null): ?array
    {
        $session = WorkSession::findOrFail($sessionId);

        return $this->contextStack->getCurrent($session, $type);
    }

    public function getContextStack(string $sessionId): array
    {
        $session = WorkSession::findOrFail($sessionId);

        return $this->contextStack->getStack($session);
    }

    public function logActivity(string $sessionId, string $type, array $data): SessionActivity
    {
        $session = WorkSession::find($sessionId);
        $activeTask = $session?->active_task_id;
        $activeSprint = $session?->active_sprint_id;

        return SessionActivity::create([
            'session_id' => $sessionId,
            'activity_type' => $type,
            'command' => $data['command'] ?? null,
            'description' => $data['description'] ?? null,
            'task_id' => $data['task_id'] ?? $activeTask,
            'sprint_id' => $data['sprint_id'] ?? $activeSprint,
            'metadata' => $data['metadata'] ?? [],
            'occurred_at' => now(),
        ]);
    }

    public function validateCompletion(string $sessionId): array
    {
        $session = WorkSession::findOrFail($sessionId);
        $errors = [];
        $warnings = [];

        if (empty($session->context_stack)) {
            $warnings[] = 'No work context was set during this session';
        }

        $activityCount = SessionActivity::where('session_id', $sessionId)->count();
        if ($activityCount < 2) {
            $warnings[] = 'Very few activities logged during this session';
        }

        if ($session->status === 'active' && $session->ended_at === null) {
            $errors[] = 'Cannot complete: session is still active';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function generateSessionKey(): string
    {
        $latest = WorkSession::orderBy('created_at', 'desc')->first();
        $number = $latest ? intval(substr($latest->session_key, 8)) + 1 : 1;

        return sprintf('SESSION-%03d', $number);
    }

    protected function calculateTotalDuration(WorkSession $session): int
    {
        if ($session->status === 'paused') {
            return $session->total_active_seconds;
        }

        $lastResume = $session->resumed_at ?? $session->started_at;

        return $session->total_active_seconds + $lastResume->diffInSeconds(now());
    }

    protected function updateActivePointers(WorkSession $session): void
    {
        $stack = $this->contextStack->getStack($session);

        $activeTask = null;
        $activeSprint = null;
        $activeProject = null;

        foreach ($stack as $context) {
            match ($context['type']) {
                'task' => $activeTask = $context['id'],
                'sprint' => $activeSprint = $context['id'],
                'project' => $activeProject = $context['id'],
                default => null,
            };
        }

        $session->update([
            'active_task_id' => $activeTask,
            'active_sprint_id' => $activeSprint,
            'active_project_id' => $activeProject,
        ]);
    }
}
