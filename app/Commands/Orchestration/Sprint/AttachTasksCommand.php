<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Services\SprintOrchestrationService;

class AttachTasksCommand extends BaseCommand
{
    protected string $sprintCode;
    protected array $taskCodes;
    protected bool $includeTasks = true;
    protected bool $includeAssignments = false;
    protected int $tasksLimit = 10;

    public function __construct(array $options = [])
    {
        $this->sprintCode = $options['sprint_code'] ?? throw new \InvalidArgumentException('Sprint code is required');
        $this->taskCodes = $options['task_codes'] ?? throw new \InvalidArgumentException('Task codes are required');
        $this->includeTasks = $options['include_tasks'] ?? true;
        $this->includeAssignments = $options['include_assignments'] ?? false;
        $this->tasksLimit = $options['tasks_limit'] ?? 10;

        if (empty($this->taskCodes)) {
            throw new \InvalidArgumentException('At least one task code is required');
        }
    }

    public function handle(): array
    {
        $service = app(SprintOrchestrationService::class);

        $options = [
            'include_tasks' => $this->includeTasks,
            'include_assignments' => $this->includeAssignments,
            'tasks_limit' => $this->tasksLimit,
        ];

        $result = $service->attachTasks($this->sprintCode, $this->taskCodes, $options);

        $data = [
            'sprint' => $result['sprint'],
            'attached_count' => count($this->taskCodes),
            'task_codes' => $this->taskCodes,
        ];

        return $this->respond($data, $this->context === 'web' ? 'SprintDetailModal' : null);
    }

    protected function getType(): string
    {
        return 'sprint';
    }

    public static function getName(): string
    {
        return 'Sprint Attach Tasks';
    }

    public static function getDescription(): string
    {
        return 'Attach one or more tasks to a sprint backlog';
    }

    public static function getUsage(): string
    {
        return '/sprint-attach-tasks sprint_code task_codes';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }

    public static function getInputSchema(): array
    {
        return [
            'sprint_code' => [
                'type' => 'string',
                'description' => 'Sprint code or UUID',
                'required' => true,
            ],
            'task_codes' => [
                'type' => 'array',
                'description' => 'Array of task codes or UUIDs to attach',
                'items' => ['type' => 'string'],
                'required' => true,
            ],
            'include_tasks' => [
                'type' => 'boolean',
                'description' => 'Include task details in response',
                'default' => true,
                'required' => false,
            ],
            'include_assignments' => [
                'type' => 'boolean',
                'description' => 'Include task assignments in response',
                'default' => false,
                'required' => false,
            ],
            'tasks_limit' => [
                'type' => 'integer',
                'description' => 'Limit number of tasks in detailed output',
                'default' => 10,
                'required' => false,
            ],
        ];
    }
}
