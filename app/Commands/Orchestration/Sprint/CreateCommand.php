<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\WorkItem;

class CreateCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get unassigned tasks for potential sprint assignment
        $unassignedTasks = WorkItem::query()
            ->whereDoesntHave('sprintItems')
            ->where('status', '!=', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($task) {
                $metadata = $task->metadata ?? [];
                return [
                    'id' => $task->id,
                    'task_code' => $metadata['task_code'] ?? $task->id,
                    'task_name' => $metadata['task_name'] ?? 'Untitled Task',
                    'status' => $task->status,
                    'priority' => $task->priority ?? 'medium',
                ];
            })
            ->toArray();

        // Get default values for new sprint
        $defaultValues = [
            'code' => $this->generateNextSprintCode(),
            'starts_on' => now()->startOfWeek()->format('Y-m-d'),
            'ends_on' => now()->endOfWeek()->addWeek()->format('Y-m-d'),
            'status' => 'planned',
            'priority' => 'medium',
        ];

        return $this->respond([
            'mode' => 'create',
            'default_values' => $defaultValues,
            'available_tasks' => $unassignedTasks,
        ], 'SprintFormModal');
    }

    /**
     * Generate the next sprint code based on existing sprints
     */
    private function generateNextSprintCode(): string
    {
        $year = now()->year;
        $week = now()->weekOfYear;
        
        return "SPRINT-{$year}-{$week}";
    }

    public static function getName(): string
    {
        return 'Create Sprint';
    }

    public static function getDescription(): string
    {
        return 'Open form to create a new sprint';
    }

    public static function getUsage(): string
    {
        return '/sprint-create';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
