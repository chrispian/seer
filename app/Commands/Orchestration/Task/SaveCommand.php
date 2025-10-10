<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;
use App\Services\TaskOrchestrationService;

class SaveCommand extends BaseCommand
{
    protected string $taskCode;
    protected ?string $taskName = null;
    protected ?string $description = null;
    protected ?string $sprintCode = null;
    protected ?string $status = null;
    protected ?string $delegationStatus = null;
    protected ?string $priority = null;
    protected ?string $estimateText = null;
    protected ?float $estimatedHours = null;
    protected ?array $dependencies = null;
    protected ?array $tags = null;
    protected ?string $agentContent = null;
    protected ?string $taskType = null;
    protected ?string $acceptance = null;
    protected bool $upsert = true;

    public function __construct(array $options = [])
    {
        $this->taskCode = $options['task_code'] ?? throw new \InvalidArgumentException('Task code is required');
        $this->taskName = $options['task_name'] ?? null;
        $this->description = $options['description'] ?? null;
        $this->sprintCode = $options['sprint_code'] ?? null;
        $this->status = $options['status'] ?? null;
        $this->delegationStatus = $options['delegation_status'] ?? null;
        $this->priority = $options['priority'] ?? null;
        $this->estimateText = $options['estimate_text'] ?? null;
        $this->estimatedHours = $options['estimated_hours'] ?? null;
        $this->dependencies = $options['dependencies'] ?? null;
        $this->tags = $options['tags'] ?? null;
        $this->agentContent = $options['agent_content'] ?? null;
        $this->taskType = $options['type'] ?? null;
        $this->acceptance = $options['acceptance'] ?? null;
        $this->upsert = $options['upsert'] ?? true;
    }

    public function handle(): array
    {
        $service = app(TaskOrchestrationService::class);

        $data = array_filter([
            'task_code' => $this->taskCode,
            'task_name' => $this->taskName,
            'description' => $this->description,
            'sprint_code' => $this->sprintCode,
            'status' => $this->status,
            'delegation_status' => $this->delegationStatus,
            'priority' => $this->priority,
            'estimate_text' => $this->estimateText,
            'estimated_hours' => $this->estimatedHours,
            'type' => $this->taskType,
            'acceptance' => $this->acceptance,
            'agent_content' => $this->agentContent,
        ], static fn ($value) => $value !== null && $value !== '');

        if ($this->dependencies !== null) {
            $data['dependencies'] = $this->dependencies;
        }

        if ($this->tags !== null) {
            $data['tags'] = $this->tags;
        }

        $task = $service->create($data, $this->upsert);

        $detail = $service->detail($task, [
            'assignments_limit' => 5,
        ]);

        $taskData = $detail['task'];

        return $this->respond($taskData, $this->context === 'web' ? 'TaskDetailModal' : null);
    }


    public static function getName(): string
    {
        return 'Task Save';
    }

    public static function getDescription(): string
    {
        return 'Create or update a task with metadata, estimates, and agent content';
    }

    public static function getUsage(): string
    {
        return '/task-save task_code [options]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }

    public static function getInputSchema(): array
    {
        return [
            'task_code' => [
                'type' => 'string',
                'description' => 'Task code (e.g., "T-ART-01", "T-BE-05")',
                'required' => true,
            ],
            'task_name' => [
                'type' => 'string',
                'description' => 'Human-friendly task name',
                'required' => false,
            ],
            'description' => [
                'type' => 'string',
                'description' => 'Task description',
                'required' => false,
            ],
            'sprint_code' => [
                'type' => 'string',
                'description' => 'Sprint code to associate task with (e.g., "SPRINT-67")',
                'required' => false,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Task status (todo, in_progress, done, etc.)',
                'required' => false,
            ],
            'delegation_status' => [
                'type' => 'string',
                'description' => 'Delegation status (unassigned, assigned, in_progress, blocked, completed, cancelled)',
                'required' => false,
            ],
            'priority' => [
                'type' => 'string',
                'description' => 'Priority (low, medium, high, critical)',
                'required' => false,
            ],
            'estimate_text' => [
                'type' => 'string',
                'description' => 'Human-readable estimate (e.g., "3-4 hours", "2 days")',
                'required' => false,
            ],
            'estimated_hours' => [
                'type' => 'number',
                'description' => 'Numeric estimate in hours',
                'required' => false,
            ],
            'dependencies' => [
                'type' => 'array',
                'description' => 'Array of task codes this task depends on',
                'items' => ['type' => 'string'],
                'required' => false,
            ],
            'tags' => [
                'type' => 'array',
                'description' => 'Array of tags',
                'items' => ['type' => 'string'],
                'required' => false,
            ],
            'agent_content' => [
                'type' => 'string',
                'description' => 'Detailed instructions for agents working on this task',
                'required' => false,
            ],
            'type' => [
                'type' => 'string',
                'description' => 'Task type (task, feature, bug, etc.)',
                'required' => false,
            ],
            'acceptance' => [
                'type' => 'string',
                'description' => 'Acceptance criteria',
                'required' => false,
            ],
            'upsert' => [
                'type' => 'boolean',
                'description' => 'If true, update existing task; if false, fail if task exists',
                'default' => true,
                'required' => false,
            ],
        ];
    }
}
