<?php

namespace App\Commands;

class TaskListCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get sprint filter from command arguments (if any)
        $sprintFilter = $this->getSprintFilter();
        
        // Build query
        $query = \App\Models\WorkItem::query()->with('assignedAgent');
        
        // Apply sprint filtering
        if ($sprintFilter) {
            $query->whereJsonContains('metadata->sprint_code', $sprintFilter);
        } else {
            // Show all tasks that have a sprint code (not null)
            $query->whereNotNull('metadata->sprint_code');
        }
        
        // Apply status-based ordering (todo, backlog, others) then by created_at
        $query->orderByRaw("CASE WHEN status = 'todo' THEN 1 WHEN status = 'backlog' THEN 2 ELSE 3 END")
              ->orderBy('created_at', 'desc')
              ->limit(50);
              
        $tasks = $query->get();
        
        // Transform data to match TaskListModal expectations
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
                'priority' => $task->priority,
                'agent_recommendation' => $metadata['agent_recommendation'] ?? null,
                'current_agent' => ($task->assignee_type == 'agent' && $task->assignedAgent) ? $task->assignedAgent->name : null,
                'estimate_text' => $metadata['estimate_text'] ?? null,
                'estimated_hours' => $task->estimated_hours,
                'tags' => $task->tags ?? [],
                'has_content' => [
                    'agent' => !empty($task->agent_content),
                    'plan' => !empty($task->plan_content),
                    'context' => !empty($task->context_content),
                    'todo' => !empty($task->todo_content),
                    'summary' => !empty($task->summary_content),
                ],
                'created_at' => $task->created_at?->toISOString(),
                'updated_at' => $task->updated_at?->toISOString(),
                'completed_at' => $task->completed_at?->toISOString(),
            ];
        })->all();
        
        return [
            'type' => 'task',
            'component' => 'TaskListModal',
            'data' => $taskData
        ];
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