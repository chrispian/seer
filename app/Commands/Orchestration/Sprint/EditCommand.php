<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;

class EditCommand extends BaseCommand
{
    protected string $code;

    public function __construct(array $options = [])
    {
        $this->code = $options['code'] ?? $options[0] ?? throw new \InvalidArgumentException('Sprint code is required');
    }

    public function handle(): array
    {
        $sprint = OrchestrationSprint::where('sprint_code', $this->code)->firstOrFail();

        // Get unassigned tasks
        $unassignedTasks = OrchestrationTask::whereNull('sprint_id')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn($task) => [
                'id' => $task->id,
                'task_code' => $task->task_code,
                'task_name' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
            ])
            ->toArray();

        // Get current sprint tasks
        $currentTasks = OrchestrationTask::where('sprint_id', $sprint->id)
            ->get()
            ->map(fn($task) => [
                'id' => $task->id,
                'task_code' => $task->task_code,
                'task_name' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
            ])
            ->toArray();

        $data = [
            'mode' => 'edit',
            'sprint' => [
                'id' => $sprint->id,
                'code' => $sprint->sprint_code,
                'starts_on' => $sprint->starts_on?->format('Y-m-d'),
                'ends_on' => $sprint->ends_on?->format('Y-m-d'),
                'status' => $sprint->status ?? 'planned',
                'priority' => ($sprint->metadata['priority'] ?? 'medium'),
                'title' => $sprint->title,
            ],
            'available_tasks' => $unassignedTasks,
            'current_tasks' => $currentTasks,
        ];

        return $this->respond($data, $this->context === 'web' ? 'SprintFormModal' : null);
    }

    public static function getName(): string
    {
        return 'Edit Sprint';
    }

    public static function getDescription(): string
    {
        return 'Open form to edit an existing sprint';
    }

    public static function getUsage(): string
    {
        return '/orch-sprint-edit <code>';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
