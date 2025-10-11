<?php

namespace App\Services\Orchestration;

use App\Models\WorkSession;
use Illuminate\Support\Facades\Storage;

class SessionPersistenceService
{
    protected string $sessionFile = '.fragments/session';
    
    public function saveActiveSession(string $sessionKey, string $sessionId): bool
    {
        $data = [
            'session_key' => $sessionKey,
            'session_id' => $sessionId,
            'saved_at' => now()->toIso8601String(),
        ];

        $homeDir = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? null;
        if (!$homeDir) {
            return false;
        }

        $fragmentsDir = $homeDir . '/.fragments';
        if (!is_dir($fragmentsDir)) {
            mkdir($fragmentsDir, 0755, true);
        }

        $sessionFilePath = $fragmentsDir . '/session';
        return file_put_contents($sessionFilePath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    public function loadActiveSession(): ?array
    {
        $homeDir = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? null;
        if (!$homeDir) {
            return null;
        }

        $sessionFilePath = $homeDir . '/.fragments/session';
        
        if (!file_exists($sessionFilePath)) {
            return null;
        }

        $content = file_get_contents($sessionFilePath);
        if (!$content) {
            return null;
        }

        $data = json_decode($content, true);
        if (!$data || !isset($data['session_id'])) {
            return null;
        }

        return $data;
    }

    public function clearActiveSession(): bool
    {
        $homeDir = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? null;
        if (!$homeDir) {
            return false;
        }

        $sessionFilePath = $homeDir . '/.fragments/session';
        
        if (!file_exists($sessionFilePath)) {
            return true;
        }

        return unlink($sessionFilePath);
    }

    public function resumeSession(string $sessionId): ?WorkSession
    {
        $session = WorkSession::find($sessionId);
        
        if (!$session) {
            return null;
        }

        if ($session->status === 'completed' || $session->status === 'abandoned') {
            return null;
        }

        if ($session->status === 'paused') {
            $session->update([
                'status' => 'active',
                'resumed_at' => now(),
            ]);
        }

        return $session;
    }

    public function getSessionContext(WorkSession $session): array
    {
        $contextStack = $session->context_stack ?? [];
        
        $activeSprint = null;
        $activeTask = null;
        
        foreach ($contextStack as $context) {
            if ($context['type'] === 'sprint') {
                $activeSprint = $context;
            } elseif ($context['type'] === 'task') {
                $activeTask = $context;
            }
        }

        $lastActivity = $session->activities()
            ->latest('occurred_at')
            ->first();

        return [
            'session_key' => $session->session_key,
            'session_id' => $session->id,
            'status' => $session->status,
            'context_stack' => $contextStack,
            'active_sprint' => $activeSprint,
            'active_task' => $activeTask,
            'started_at' => $session->started_at->toIso8601String(),
            'last_activity_at' => $lastActivity?->occurred_at->toIso8601String(),
            'total_active_seconds' => $session->total_active_seconds,
            'tasks_completed' => $session->tasks_completed,
        ];
    }

    public function shouldAutoResume(): bool
    {
        $data = $this->loadActiveSession();
        
        if (!$data) {
            return false;
        }

        $session = WorkSession::find($data['session_id']);
        
        if (!$session) {
            $this->clearActiveSession();
            return false;
        }

        if ($session->status === 'completed' || $session->status === 'abandoned') {
            $this->clearActiveSession();
            return false;
        }

        return true;
    }
}
