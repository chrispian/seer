<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;
use App\Services\TaskOrchestrationService;

class UpdateStatusCommand extends BaseCommand
{
    protected string $taskCode;
    protected string $delegationStatus;
    protected ?string $note = null;

    public function __construct(array $options = [])
    {
        $this->taskCode = $options['task_code'] ?? throw new \InvalidArgumentException('Task code is required');
        $this->delegationStatus = $options['delegation_status'] ?? throw new \InvalidArgumentException('Delegation status is required');
        $this->note = $options['note'] ?? null;
    }

    public function handle(): array
    {
        $service = app(TaskOrchestrationService::class);

        $options = [];
        if ($this->note !== null) {
            $options['note'] = $this->note;
        }

        $task = $service->updateStatus($this->taskCode, $this->delegationStatus, $options);

        $detail = $service->detail($task, [
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
        return 'Task Update Status';
    }

    public static function getDescription(): string
    {
        return 'Update task delegation status and optionally log a note';
    }

    public static function getUsage(): string
    {
        return '/task-status task_code delegation_status [note]';
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
            'delegation_status' => [
                'type' => 'string',
                'description' => 'Delegation status: unassigned, assigned, in_progress, blocked, completed, cancelled',
                'required' => true,
            ],
            'note' => [
                'type' => 'string',
                'description' => 'Optional note to append to delegation history',
                'required' => false,
            ],
        ];
    }
}
