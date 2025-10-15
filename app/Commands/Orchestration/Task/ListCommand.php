<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;
use App\Models\OrchestrationTask;
use Illuminate\Support\Arr;

class ListCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get sprint filter from command arguments (if any)
        $sprintFilter = $this->getSprintFilter();
        $status = null;
        $limit = 100;

        // Build query
        $query = OrchestrationTask::query()->with('assignedAgent', 'sprint');

        // Apply sprint filtering
        if ($sprintFilter) {
            $query->whereHas('sprint', function($q) use ($sprintFilter) {
                $q->where('sprint_code', $sprintFilter);
            });
        }

        // Apply status filtering
        if ($status) {
            if ($status === 'unassigned') {
                $query->whereNull('sprint_id');
            } else {
                $query->where('delegation_status', $status);
            }
        }

        // Apply status-based ordering (pending=todo, others) then by created_at
        $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        $tasks = $query->get();

        // Transform data for generic display
        $taskData = $tasks->map(function ($task) {
            $metadata = $task->metadata ?? [];

            return [
                'id' => $task->id,
                'task_code' => $task->task_code,
                'task_name' => $task->title,
                'description' => Arr::get($metadata, 'description'),
                'sprint_code' => $task->sprint?->sprint_code,
                'status' => $task->status,
                'delegation_status' => $task->delegation_status,
                'priority' => $task->priority ?? 'P2',
                'type' => $task->type,
                'agent_recommendation' => Arr::get($task->delegation_context, 'agent_recommendation'),
                'assigned_to' => ($task->assignee_type == 'agent' && $task->assignedAgent) 
                    ? $task->assignedAgent->name 
                    : ($task->assignee_id ?: 'Unassigned'),
                'estimate_text' => $task->estimated_hours ? "{$task->estimated_hours}h" : null,
                'estimated_hours' => $task->estimated_hours,
                'tags' => $task->tags ?? [],
                'has_agent_content' => !empty($task->agent_content),
                'has_plan_content' => !empty($task->plan_content),
                'has_context' => !empty($task->context_content),
                'created_at' => $task->created_at?->toISOString(),
                'updated_at' => $task->updated_at?->toISOString(),
                'completed_at' => $task->completed_at?->toISOString(),
            ];
        })->toArray();

        // Use BaseCommand's respond method for config-driven approach
        return $this->respond([
            'tasks' => $taskData,
            'meta' => [
                'count' => count($taskData),
                'has_more' => count($taskData) >= $limit,
                'filters' => [
                    'sprint' => $sprintFilter,
                    'status' => $status,
                ]
            ]
        ]);
    }

    private function getSprintFilter(): ?string
    {
        // TODO: Get from command context/arguments
        // For now, return null to show all tasks
        // This should be extracted from the command input like "tasks 43" -> "SPRINT-43"
        return null;
    }

    public static function getName(): string
    {
        return 'Tasklist';
    }

    public static function getDescription(): string
    {
        return 'List all tasks with filtering and sprint options';
    }

    public static function getUsage(): string
    {
        return '/orch-tasks';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
