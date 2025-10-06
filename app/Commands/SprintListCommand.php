<?php

namespace App\Commands;

use App\Models\Sprint;

class SprintListCommand extends BaseCommand
{
    public function handle(): array
    {
        $sprints = Sprint::query()
            ->orderBy('code')
            ->get();
            
        // Transform data to match SprintListModal expectations
        $sprintData = $sprints->map(function ($sprint) {
            return [
                'id' => $sprint->id,
                'code' => $sprint->code,
                'title' => $sprint->meta['title'] ?? $sprint->code,
                'status' => $sprint->meta['status'] ?? 'active',
                'priority' => $sprint->meta['priority'] ?? null,
                'task_count' => 0, // TODO: Calculate actual task counts
                'completed_tasks' => 0,
                'in_progress_tasks' => 0,
                'todo_tasks' => 0,
                'backlog_tasks' => 0,
                'created_at' => $sprint->created_at?->toISOString(),
                'updated_at' => $sprint->updated_at?->toISOString(),
                'meta' => $sprint->meta ?? [],
            ];
        })->all();
        
        return [
            'type' => 'sprint',
            'component' => 'SprintListModal',
            'data' => $sprintData
        ];
    }
    
    public static function getName(): string
    {
        return 'Sprint List';
    }
    
    public static function getDescription(): string
    {
        return 'List all sprints with progress tracking';
    }
    
    public static function getUsage(): string
    {
        return '/sprints';
    }
    
    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}