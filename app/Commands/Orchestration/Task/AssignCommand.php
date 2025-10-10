<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;
use App\Services\TaskOrchestrationService;

class AssignCommand extends BaseCommand
{
    protected string $taskCode;
    protected string $agentSlug;
    protected string $status = 'assigned';
    protected ?string $note = null;
    protected ?array $assignmentContext = null;

    public function __construct(array $options = [])
    {
        $this->taskCode = $options['task_code'] ?? throw new \InvalidArgumentException('Task code is required');
        $this->agentSlug = $options['agent_slug'] ?? throw new \InvalidArgumentException('Agent slug is required');
        $this->status = $options['status'] ?? 'assigned';
        $this->note = $options['note'] ?? null;
        $this->assignmentContext = $options['context'] ?? null;
    }

    public function handle(): array
    {
        $service = app(TaskOrchestrationService::class);

        $options = [
            'status' => $this->status,
        ];

        if ($this->note !== null) {
            $options['note'] = $this->note;
        }

        if ($this->assignmentContext !== null) {
            $options['context'] = $this->assignmentContext;
        }

        $result = $service->assignAgent($this->taskCode, $this->agentSlug, $options);

        $detail = $service->detail($result['task'], [
            'assignments_limit' => 5,
        ]);

        $taskData = $detail['task'];

        return $this->respond($taskData, $this->context === 'web' ? 'TaskDetailModal' : null);
    }

    protected function getType(): string
    {
        return 'task';
    }

    public static function getName(): string
    {
        return 'Task Assign';
    }

    public static function getDescription(): string
    {
        return 'Assign a task to an agent and update delegation status';
    }

    public static function getUsage(): string
    {
        return '/task-assign task_code agent_slug';
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
                'description' => 'Task code or UUID',
                'required' => true,
            ],
            'agent_slug' => [
                'type' => 'string',
                'description' => 'Agent slug, name, or UUID',
                'required' => true,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Delegation status (assigned, in_progress, etc.)',
                'default' => 'assigned',
                'required' => false,
            ],
            'note' => [
                'type' => 'string',
                'description' => 'Optional note to store with assignment',
                'required' => false,
            ],
            'context' => [
                'type' => 'object',
                'description' => 'Additional context data to store with assignment',
                'required' => false,
            ],
        ];
    }
}
