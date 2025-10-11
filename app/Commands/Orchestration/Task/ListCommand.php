<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;

class ListCommand extends BaseCommand
{
    public function __invoke(array $params = []): array
    {
        // Get sprint filter from command arguments (if any)
        $sprintFilter = $params['sprint'] ?? null;
        $status = $params['status'] ?? null;
        $limit = $params['limit'] ?? 100;

        // Build query
        $query = \App\Models\WorkItem::query()->with('assignedAgent');

        // Apply sprint filtering
        if ($sprintFilter) {
            $query->whereJsonContains('metadata->sprint_code', $sprintFilter);
        }

        // Apply status filtering
        if ($status) {
            if ($status === 'unassigned') {
                $query->where(function($q) {
                    $q->whereNull('metadata->sprint_code')
                      ->orWhere('metadata->sprint_code', '');
                });
            } else {
                $query->where('delegation_status', $status);
            }
        }

        // Apply status-based ordering (todo, backlog, others) then by created_at
        $query->orderByRaw("CASE WHEN status = 'todo' THEN 1 WHEN status = 'backlog' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        $tasks = $query->get();

        // Transform data for generic display
        $taskData = $tasks->map(function ($task) {
            $metadata = $task->metadata ?? [];

            return [
                'id' => $task->id,
                'task_code' => $metadata['task_code'] ?? $task->id,
                'task_name' => $metadata['task_name'] ?? 'Untitled Task',
                'description' => $metadata['description'] ?? null,
                'sprint_code' => $metadata['sprint_code'] ?? null,
                'status' => $task->status,
                'delegation_status' => $task->delegation_status,
                'priority' => $task->priority ?? 'medium',
                'agent_recommendation' => $metadata['agent_recommendation'] ?? null,
                'assigned_to' => ($task->assignee_type == 'agent' && $task->assignedAgent) 
                    ? $task->assignedAgent->name 
                    : ($task->assignee_id ?: 'Unassigned'),
                'estimate_text' => $metadata['estimate_text'] ?? null,
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
        return '/tasks';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
