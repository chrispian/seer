<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
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
        return $this->respond([
            'sprints' => $data,
        ]);
    }

    private function fetchSprints()
    {
        return OrchestrationSprint::query()
            ->when($this->codes, fn($q) => $q->whereIn('sprint_code', $this->codes))
            ->orderByDesc('created_at')
            ->orderByDesc('updated_at')
            ->limit($this->limit)
            ->get();
    }

    private function formatSprint(OrchestrationSprint $sprint): array
    {
        $stats = $this->calculateStats($sprint);
        $metadata = $sprint->metadata ?? [];

        $formatted = [
            'id' => $sprint->id,
            'code' => $sprint->sprint_code,
            'sprint_code' => $sprint->sprint_code,
            'title' => $sprint->title,
            'status' => $sprint->status ?? 'planning',
            'owner' => $sprint->owner,
            'priority' => Arr::get($metadata, 'priority'),
            'estimate' => Arr::get($metadata, 'estimate'),
            'starts_on' => $sprint->starts_on?->toDateString(),
            'ends_on' => $sprint->ends_on?->toDateString(),
            'notes' => Arr::get($metadata, 'notes', []),
            'stats' => $stats,
            'created_at' => $sprint->created_at?->toIso8601String(),
            'updated_at' => $sprint->updated_at?->toIso8601String(),
            // Legacy fields for backward compatibility
            'task_count' => $stats['total'],
            'total_tasks' => $stats['total'],
            'completed_tasks' => $stats['completed'],
            'in_progress_tasks' => $stats['in_progress'],
            'todo_tasks' => $stats['unassigned'],
            'backlog_tasks' => 0,
            'progress_percentage' => $stats['total'] > 0 
                ? round(($stats['completed'] / $stats['total']) * 100) 
                : 0,
            'metadata' => $metadata,
        ];

        if ($this->includeDetails) {
            $formatted['tasks'] = $this->fetchTasks($sprint);
        }

        return $formatted;
    }

    private function calculateStats(OrchestrationSprint $sprint): array
    {
        $query = OrchestrationTask::where('sprint_id', $sprint->id);

        return [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('delegation_status', 'completed')->count(),
            'in_progress' => (clone $query)->whereIn('delegation_status', ['assigned', 'in_progress'])->count(),
            'blocked' => (clone $query)->where('delegation_status', 'blocked')->count(),
            'unassigned' => (clone $query)->where('delegation_status', 'unassigned')->count(),
        ];
    }

    private function fetchTasks(OrchestrationSprint $sprint): array
    {
        return OrchestrationTask::where('sprint_id', $sprint->id)
            ->orderByDesc('created_at')
            ->limit($this->tasksLimit)
            ->get()
            ->map(fn($task) => [
                'task_code' => $task->task_code,
                'task_name' => $task->title,
                'delegation_status' => $task->delegation_status,
                'status' => $task->status,
                'agent_recommendation' => Arr::get($task->delegation_context, 'agent_recommendation'),
                'estimate_text' => $task->estimated_hours ? "{$task->estimated_hours}h" : null,
                'estimated_hours' => $task->estimated_hours,
            ])
            ->toArray();
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
        return '/orch [options]';
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
