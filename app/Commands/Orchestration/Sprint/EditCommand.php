<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\Sprint;
use App\Models\WorkItem;
use Illuminate\Support\Arr;

class EditCommand extends BaseCommand
{
    protected string $code;

    public function __construct(array $options = [])
    {
        $this->code = $options['code'] ?? $options[0] ?? throw new \InvalidArgumentException('Sprint code is required');
    }

    public function handle(): array
    {
        $sprint = Sprint::where('code', $this->code)->firstOrFail();

        // Get unassigned tasks (same pattern as ListCommand)
        $unassignedTasks = WorkItem::where(function($query) {
                $query->whereNull('metadata->sprint_code')
                      ->orWhere('metadata->sprint_code', '');
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn($task) => [
                'id' => $task->id,
                'task_code' => Arr::get($task->metadata, 'task_code'),
                'task_name' => Arr::get($task->metadata, 'task_name'),
                'status' => $task->status,
                'priority' => Arr::get($task->metadata, 'priority'),
            ])
            ->toArray();

        // Get current sprint tasks
        $currentTasks = $sprint->sprintItems()
            ->with('workItem')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->workItem->id,
                'task_code' => Arr::get($item->workItem->metadata, 'task_code'),
                'task_name' => Arr::get($item->workItem->metadata, 'task_name'),
                'status' => $item->workItem->status,
                'priority' => Arr::get($item->workItem->metadata, 'priority'),
            ])
            ->toArray();

        $data = [
            'mode' => 'edit',
            'sprint' => [
                'id' => $sprint->id,
                'code' => $sprint->code,
                'starts_on' => $sprint->starts_on?->format('Y-m-d'),
                'ends_on' => $sprint->ends_on?->format('Y-m-d'),
                'status' => $sprint->meta['status'] ?? 'planned',
                'priority' => $sprint->meta['priority'] ?? 'medium',
                'title' => $sprint->meta['title'] ?? null,
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
        return '/sprint-edit <code>';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
