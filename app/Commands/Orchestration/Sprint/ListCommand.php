<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\Sprint;
use App\Models\WorkItem;
use Illuminate\Support\Arr;

class ListCommand extends BaseCommand
{
    protected ?array $codes = null;
    protected int $limit = 50;
    protected bool $includeDetails = false;
    protected int $tasksLimit = 5;

    public function __construct(array $options = [])
    {
        $this->codes = $options['codes'] ?? null;
        $this->limit = $options['limit'] ?? 50;
        $this->includeDetails = $options['details'] ?? false;
        $this->tasksLimit = $options['tasks_limit'] ?? 5;
    }

    public function handle(): array
    {
        $sprints = $this->fetchSprints();
        $data = $sprints->map(fn($sprint) => $this->formatSprint($sprint))->toArray();

        // Web context gets optional UI component
        return $this->respond($data, $this->context === 'web' ? 'SprintListModal' : null);
    }

    private function fetchSprints()
    {
        return Sprint::query()
            ->when($this->codes, fn($q) => $q->whereIn('code', $this->codes))
            ->orderByDesc('created_at')
            ->orderByDesc('updated_at')
            ->limit($this->limit)
            ->get();
    }

    private function formatSprint(Sprint $sprint): array
    {
        $stats = $this->calculateStats($sprint);
        $meta = $sprint->meta ?? [];

        $formatted = [
            'id' => $sprint->id,
            'code' => $sprint->code,
            'title' => Arr::get($meta, 'title', $sprint->code),
            'status' => Arr::get($meta, 'status', 'active'),
            'priority' => Arr::get($meta, 'priority'),
            'estimate' => Arr::get($meta, 'estimate'),
            'starts_on' => Arr::get($meta, 'starts_on'),
            'ends_on' => Arr::get($meta, 'ends_on'),
            'notes' => Arr::get($meta, 'notes', []),
            'stats' => $stats,
            'created_at' => $sprint->created_at?->toIso8601String(),
            'updated_at' => $sprint->updated_at?->toIso8601String(),
            // Legacy fields for backward compatibility
            'task_count' => $stats['total'],
            'completed_tasks' => $stats['completed'],
            'in_progress_tasks' => $stats['in_progress'],
            'todo_tasks' => $stats['unassigned'],
            'backlog_tasks' => 0,
            'meta' => $meta,
        ];

        if ($this->includeDetails) {
            $formatted['tasks'] = $this->fetchTasks($sprint);
        }

        return $formatted;
    }

    private function calculateStats(Sprint $sprint): array
    {
        $query = WorkItem::where('metadata->sprint_code', $sprint->code);

        return [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('delegation_status', 'completed')->count(),
            'in_progress' => (clone $query)->whereIn('delegation_status', ['assigned', 'in_progress'])->count(),
            'blocked' => (clone $query)->where('delegation_status', 'blocked')->count(),
            'unassigned' => (clone $query)->where('delegation_status', 'unassigned')->count(),
        ];
    }

    private function fetchTasks(Sprint $sprint): array
    {
        return WorkItem::where('metadata->sprint_code', $sprint->code)
            ->orderByDesc('created_at')
            ->limit($this->tasksLimit)
            ->get()
            ->map(fn($task) => [
                'task_code' => Arr::get($task->metadata, 'task_code'),
                'task_name' => Arr::get($task->metadata, 'task_name'),
                'delegation_status' => $task->delegation_status,
                'status' => $task->status,
                'agent_recommendation' => Arr::get($task->delegation_context, 'agent_recommendation'),
                'estimate_text' => Arr::get($task->metadata, 'estimate_text'),
            ])
            ->toArray();
    }

    protected function getType(): string
    {
        return 'sprint';
    }

    public static function getName(): string
    {
        return 'Sprint List';
    }

    public static function getDescription(): string
    {
        return 'List sprints with progress stats and optional task details';
    }

    public static function getUsage(): string
    {
        return '/sprints [options]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }

    public static function getInputSchema(): array
    {
        return [
            'codes' => [
                'type' => 'array',
                'description' => 'Filter by sprint codes (e.g., ["SPRINT-67", "SPRINT-68"])',
                'items' => ['type' => 'string'],
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of sprints to return',
                'default' => 50,
                'required' => false,
            ],
            'details' => [
                'type' => 'boolean',
                'description' => 'Include task details for each sprint',
                'default' => false,
                'required' => false,
            ],
            'tasks_limit' => [
                'type' => 'integer',
                'description' => 'Number of tasks to include when details=true',
                'default' => 5,
                'required' => false,
            ],
        ];
    }
}
