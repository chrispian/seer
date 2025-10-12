<?php

namespace App\Commands\Orchestration\Backlog;

use App\Commands\BaseCommand;

class ListCommand extends BaseCommand
{
    public function handle(): array
    {
        $tasks = $this->getBacklogTasks();

        return $this->respond([
            'tasks' => $tasks,
        ]);
    }

    private function getBacklogTasks(): array
    {
        if (class_exists(\App\Models\WorkItem::class)) {
            $tasks = \App\Models\WorkItem::query()
                ->with('assignedAgent')
                ->where('status', 'backlog')
                ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            return $tasks->map(function ($task) {
                $metadata = $task->metadata ?? [];

                return [
                    'id' => $task->id,
                    'task_code' => $metadata['task_code'] ?? $task->id,
                    'task_name' => $metadata['task_name'] ?? 'Untitled Task',
                    'description' => $metadata['description'] ?? null,
                    'sprint_code' => $metadata['sprint_code'] ?? null,
                    'status' => $task->status,
                    'delegation_status' => $task->delegation_status,
                    'priority' => $task->priority,
                    'agent_recommendation' => $metadata['agent_recommendation'] ?? null,
                    'current_agent' => ($task->assignee_type == 'agent' && $task->assignedAgent) ? $task->assignedAgent->name : null,
                    'estimate_text' => $metadata['estimate_text'] ?? null,
                    'estimated_hours' => $task->estimated_hours,
                    'tags' => $task->tags ?? [],
                    'has_content' => [
                        'agent' => ! empty($task->agent_content),
                        'plan' => ! empty($task->plan_content),
                        'context' => ! empty($task->context_content),
                        'todo' => ! empty($task->todo_content),
                        'summary' => ! empty($task->summary_content),
                    ],
                    'created_at' => $task->created_at?->toISOString(),
                    'updated_at' => $task->updated_at?->toISOString(),
                    'completed_at' => $task->completed_at?->toISOString(),
                ];
            })->all();
        }

        return [];
    }

    public static function getName(): string
    {
        return 'Backlog List';
    }

    public static function getDescription(): string
    {
        return 'List all backlog items for future planning';
    }

    public static function getUsage(): string
    {
        return '/backlog-list';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
