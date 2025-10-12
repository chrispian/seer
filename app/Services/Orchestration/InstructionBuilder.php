<?php

namespace App\Services\Orchestration;

use App\Models\WorkItem;
use App\Models\WorkSession;
use App\Models\Sprint;

class InstructionBuilder
{
    public function forSessionStart(WorkSession $session, array $suggestions = []): array
    {
        $instructions = [
            'next_actions' => [
                'Activate sprint to set working context',
                'Start or resume a task',
            ],
            'suggested_commands' => [
                '/sprint-activate SPRINT-XX',
                '/task-activate T-XX',
            ],
        ];

        if (!empty($suggestions)) {
            $instructions['suggestions'] = $suggestions;
        }

        return $instructions;
    }

    public function forSessionResume(WorkSession $session, array $context): array
    {
        $instructions = [
            'session_status' => 'Resumed',
            'time_since_last_activity' => $context['last_activity_at'] 
                ? \Carbon\Carbon::parse($context['last_activity_at'])->diffForHumans()
                : 'Unknown',
        ];

        $nextActions = [];
        
        if ($context['active_task']) {
            $nextActions[] = "Continue working on {$context['active_task']['data']['task_code']}";
            $nextActions[] = 'Log progress with /update or /note';
            $nextActions[] = 'Complete with /task-deactivate when done';
        } elseif ($context['active_sprint']) {
            $nextActions[] = "Select a task from {$context['active_sprint']['data']['code']}";
            $nextActions[] = 'Use /task-activate T-XX to start';
        } else {
            $nextActions[] = 'Activate sprint with /sprint-activate SPRINT-XX';
            $nextActions[] = 'Then start a task with /task-activate T-XX';
        }

        $instructions['next_actions'] = $nextActions;

        return $instructions;
    }

    public function forSprintActivate(Sprint $sprint, WorkSession $session): array
    {
        return [
            'next_actions' => [
                'Review sprint tasks and select one to work on',
                'Activate a task with /task-activate T-XX',
                'View tasks with /tasks command',
            ],
            'suggested_commands' => [
                '/tasks --sprint=' . $sprint->code,
                '/task-activate T-XX',
            ],
            'context_reminder' => [
                'active_sprint' => $sprint->code,
                'sprint_title' => $sprint->title,
            ],
        ];
    }

    public function forTaskActivate(WorkItem $task, WorkSession $session): array
    {
        $taskCode = $task->metadata['task_code'] ?? $task->id;
        $estimate = $task->metadata['estimate_text'] ?? null;

        $instructions = [
            'next_actions' => [
                'Begin implementation work',
                'Log progress with /update or /note commands',
                'Complete task with /task-deactivate when finished',
            ],
            'context_reminder' => [
                'task' => $taskCode,
                'description' => $task->metadata['task_name'] ?? $task->description,
                'priority' => $task->priority,
            ],
            'validation_requirements' => [
                'Add context updates as you work',
                'Provide summary on completion',
                'Track time spent',
            ],
        ];

        if ($estimate) {
            $instructions['context_reminder']['estimated_time'] = $estimate;
        }

        return $instructions;
    }

    public function forTaskUpdate(WorkItem $task, WorkSession $session, array $timeTracking = []): array
    {
        $taskCode = $task->metadata['task_code'] ?? $task->id;

        $instructions = [
            'next_actions' => [
                "Continue working on {$taskCode}",
                'Add more updates as progress is made',
                'Complete when ready with /task-deactivate',
            ],
        ];

        if (!empty($timeTracking)) {
            $instructions['progress'] = [
                'time_elapsed' => $timeTracking['elapsed_formatted'] ?? null,
                'estimated_remaining' => $timeTracking['remaining_formatted'] ?? null,
            ];
        }

        return $instructions;
    }

    public function forTaskDeactivate(WorkItem $task, array $timeResult): array
    {
        $taskCode = $task->metadata['task_code'] ?? $task->id;

        $instructions = [
            'next_actions' => [
                'Select another task to work on',
                'Or end session with /session-end',
                'View session status with /session-status',
            ],
            'suggested_commands' => [
                '/task-activate T-XX',
                '/session-status',
                '/session-end',
            ],
        ];

        if (isset($timeResult['total_time_formatted'])) {
            $instructions['completion_summary'] = [
                'task' => $taskCode,
                'total_time' => $timeResult['total_time_formatted'],
            ];

            if (isset($timeResult['variance'])) {
                $instructions['completion_summary']['variance'] = $timeResult['variance']['variance_formatted'];
            }
        }

        return $instructions;
    }

    public function forValidationFailure(array $validation, string $context = 'task'): array
    {
        $instructions = [
            'required_actions' => [],
            'validation_errors' => $validation['errors'] ?? [],
            'validation_warnings' => $validation['warnings'] ?? [],
        ];

        if ($context === 'task') {
            $instructions['required_actions'] = [
                'Add summary with /task-deactivate --summary="Summary text"',
                'Or add context update first with /update "details"',
            ];
            $instructions['suggested_commands'] = [
                '/update "Additional context"',
                '/task-deactivate --summary="Work completed"',
            ];
        } elseif ($context === 'session') {
            $instructions['required_actions'] = [
                'Close all active tasks first',
                'Add session summary with /session-end --summary="Summary"',
            ];
            $instructions['suggested_commands'] = [
                '/task-deactivate',
                '/session-end --summary="Session summary"',
            ];
        }

        if (!empty($validation['requirements'])) {
            $instructions['missing_requirements'] = $validation['requirements'];
        }

        return $instructions;
    }

    public function forSessionEnd(WorkSession $session, array $validation): array
    {
        $duration = gmdate('H:i:s', $session->total_active_seconds);

        $instructions = [
            'session_summary' => [
                'duration' => $duration,
                'tasks_completed' => $session->tasks_completed,
                'artifacts_created' => $session->artifacts_created,
            ],
            'next_actions' => [
                'Start new session with /session-start when ready',
                'Or resume this session later with /session-resume',
            ],
        ];

        if (!empty($validation['warnings'])) {
            $instructions['warnings'] = $validation['warnings'];
        }

        return $instructions;
    }

    public function forContextSwitch(string $fromType, string $toType, array $fromContext, array $toContext): array
    {
        return [
            'action' => 'context_switched',
            'from' => [
                'type' => $fromType,
                'id' => $fromContext['id'] ?? null,
                'data' => $fromContext['data'] ?? [],
            ],
            'to' => [
                'type' => $toType,
                'id' => $toContext['id'] ?? null,
                'data' => $toContext['data'] ?? [],
            ],
            'next_actions' => [
                'New context is now active',
                'All commands will use this context',
            ],
        ];
    }

    public function forRecovery(WorkSession $session, array $context, int $minutesSinceActivity): array
    {
        $instructions = [
            'recovery_status' => [
                'session_interrupted' => "{$minutesSinceActivity} minutes ago",
                'session_key' => $session->session_key,
                'activities_logged' => $session->activities()->count(),
            ],
            'recovered_context' => [],
            'next_actions' => [],
        ];

        if ($context['active_sprint']) {
            $instructions['recovered_context']['sprint'] = $context['active_sprint']['data'];
            $instructions['next_actions'][] = "Continue working on sprint {$context['active_sprint']['data']['code']}";
        }

        if ($context['active_task']) {
            $instructions['recovered_context']['task'] = $context['active_task']['data'];
            $instructions['next_actions'][] = "Resume task {$context['active_task']['data']['task_code']}";
            $instructions['next_actions'][] = "Time tracking will resume automatically";
        }

        if (empty($instructions['next_actions'])) {
            $instructions['next_actions'] = [
                'Select a sprint to activate',
                'Then choose a task to work on',
            ];
        }

        return $instructions;
    }

    public function build(string $type, array $data = []): array
    {
        return match($type) {
            'session_start' => $this->forSessionStart($data['session'], $data['suggestions'] ?? []),
            'session_resume' => $this->forSessionResume($data['session'], $data['context']),
            'session_end' => $this->forSessionEnd($data['session'], $data['validation'] ?? []),
            'sprint_activate' => $this->forSprintActivate($data['sprint'], $data['session']),
            'task_activate' => $this->forTaskActivate($data['task'], $data['session']),
            'task_update' => $this->forTaskUpdate($data['task'], $data['session'], $data['time_tracking'] ?? []),
            'task_deactivate' => $this->forTaskDeactivate($data['task'], $data['time_result']),
            'validation_failure' => $this->forValidationFailure($data['validation'], $data['context'] ?? 'task'),
            'context_switch' => $this->forContextSwitch($data['from_type'], $data['to_type'], $data['from_context'], $data['to_context']),
            'recovery' => $this->forRecovery($data['session'], $data['context'], $data['minutes_since_activity']),
            default => ['error' => 'Unknown instruction type'],
        };
    }
}
