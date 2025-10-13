<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Models\OrchestrationEvent;
use Illuminate\Support\Facades\File;

class OrchestrationContextBrokerService
{
    public function assembleSprintContext(string $sprintCode): ?array
    {
        $sprint = OrchestrationSprint::where('sprint_code', $sprintCode)
            ->with(['tasks'])
            ->first();

        if (!$sprint) {
            return null;
        }

        $recentEvents = OrchestrationEvent::byEntity('sprint', $sprint->id)
            ->recent(50)
            ->get();

        $sprintDir = base_path("delegation/sprints/{$sprintCode}");
        $files = $this->gatherFiles($sprintDir);

        return [
            'sprint' => [
                'code' => $sprint->sprint_code,
                'title' => $sprint->title,
                'status' => $sprint->status,
                'owner' => $sprint->owner,
                'metadata' => $sprint->metadata ?? [],
                'hash' => $sprint->hash,
                'created_at' => $sprint->created_at->toIso8601String(),
                'updated_at' => $sprint->updated_at->toIso8601String(),
            ],
            'tasks' => $sprint->tasks->map(fn($task) => [
                'code' => $task->task_code,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'metadata' => $task->metadata ?? [],
            ])->toArray(),
            'progress' => [
                'total_tasks' => $sprint->tasks->count(),
                'completed_tasks' => $sprint->tasks->where('status', 'completed')->count(),
                'in_progress_tasks' => $sprint->tasks->where('status', 'in_progress')->count(),
                'pending_tasks' => $sprint->tasks->where('status', 'pending')->count(),
                'blocked_tasks' => $sprint->tasks->where('status', 'blocked')->count(),
            ],
            'recent_events' => $recentEvents->map(fn($event) => [
                'type' => $event->event_type,
                'emitted_at' => $event->emitted_at->toIso8601String(),
                'payload' => $event->payload,
            ])->toArray(),
            'files' => $files,
        ];
    }

    public function assembleTaskContext(string $taskCode): ?array
    {
        $task = OrchestrationTask::where('task_code', $taskCode)
            ->with(['sprint'])
            ->first();

        if (!$task) {
            return null;
        }

        $recentEvents = OrchestrationEvent::byEntity('task', $task->id)
            ->recent(50)
            ->get();

        $sprintCode = $task->sprint ? $task->sprint->sprint_code : null;
        $taskDir = $sprintCode 
            ? base_path("delegation/sprints/{$sprintCode}/{$taskCode}")
            : null;
        
        $files = $taskDir ? $this->gatherFiles($taskDir) : [];
        
        $taskMdContent = null;
        $agentYmlContent = null;
        
        if ($taskDir) {
            if (File::exists("{$taskDir}/TASK.md")) {
                $taskMdContent = File::get("{$taskDir}/TASK.md");
            }
            if (File::exists("{$taskDir}/AGENT.yml")) {
                $agentYmlContent = File::get("{$taskDir}/AGENT.yml");
            }
        }

        $context = [
            'task' => [
                'code' => $task->task_code,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'metadata' => $task->metadata ?? [],
                'agent_config' => $task->agent_config ?? [],
                'hash' => $task->hash,
                'created_at' => $task->created_at->toIso8601String(),
                'updated_at' => $task->updated_at->toIso8601String(),
            ],
            'recent_events' => $recentEvents->map(fn($event) => [
                'type' => $event->event_type,
                'emitted_at' => $event->emitted_at->toIso8601String(),
                'payload' => $event->payload,
            ])->toArray(),
            'files' => $files,
            'content' => [
                'task_md' => $taskMdContent,
                'agent_yml' => $agentYmlContent,
            ],
        ];

        if ($task->sprint) {
            $context['sprint'] = [
                'code' => $task->sprint->sprint_code,
                'title' => $task->sprint->title,
                'status' => $task->sprint->status,
                'owner' => $task->sprint->owner,
                'metadata' => $task->sprint->metadata ?? [],
            ];
            
            $context['sprint_progress'] = [
                'total_tasks' => $task->sprint->tasks->count(),
                'completed_tasks' => $task->sprint->tasks->where('status', 'completed')->count(),
                'in_progress_tasks' => $task->sprint->tasks->where('status', 'in_progress')->count(),
            ];
        }

        return $context;
    }

    public function assembleSessionContext(string $sessionKey): ?array
    {
        $sessionEvents = OrchestrationEvent::bySession($sessionKey)
            ->orderBy('emitted_at', 'asc')
            ->get();

        if ($sessionEvents->isEmpty()) {
            return null;
        }

        $firstEvent = $sessionEvents->first();
        $lastEvent = $sessionEvents->last();

        $entityType = $firstEvent->entity_type;
        $entityId = $firstEvent->entity_id;

        $entityContext = null;
        if ($entityType === 'sprint') {
            $sprint = OrchestrationSprint::find($entityId);
            if ($sprint) {
                $entityContext = $this->assembleSprintContext($sprint->sprint_code);
            }
        } elseif ($entityType === 'task') {
            $task = OrchestrationTask::find($entityId);
            if ($task) {
                $entityContext = $this->assembleTaskContext($task->task_code);
            }
        }

        return [
            'session' => [
                'session_key' => $sessionKey,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'started_at' => $firstEvent->emitted_at->toIso8601String(),
                'last_activity_at' => $lastEvent->emitted_at->toIso8601String(),
                'duration_seconds' => $lastEvent->emitted_at->diffInSeconds($firstEvent->emitted_at),
                'event_count' => $sessionEvents->count(),
            ],
            'entity_context' => $entityContext,
            'session_events' => $sessionEvents->map(fn($event) => [
                'type' => $event->event_type,
                'emitted_at' => $event->emitted_at->toIso8601String(),
                'payload' => $event->payload,
            ])->toArray(),
        ];
    }

    public function mergeContextData(array $contexts): array
    {
        $merged = [
            'contexts' => $contexts,
            'summary' => [
                'total_sprints' => 0,
                'total_tasks' => 0,
                'total_events' => 0,
            ],
        ];

        foreach ($contexts as $context) {
            if (isset($context['sprint'])) {
                $merged['summary']['total_sprints']++;
            }
            if (isset($context['task'])) {
                $merged['summary']['total_tasks']++;
            }
            if (isset($context['recent_events'])) {
                $merged['summary']['total_events'] += count($context['recent_events']);
            }
        }

        return $merged;
    }

    private function gatherFiles(string $directory): array
    {
        if (!File::isDirectory($directory)) {
            return [];
        }

        $files = [];
        $items = File::allFiles($directory);

        foreach ($items as $file) {
            $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
            $files[] = [
                'path' => $relativePath,
                'name' => $file->getFilename(),
                'extension' => $file->getExtension(),
                'size' => $file->getSize(),
            ];
        }

        return $files;
    }
}
