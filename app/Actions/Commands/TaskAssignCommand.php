<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Services\TaskOrchestrationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskAssignCommand implements HandlesCommand
{
    public function __construct(
        private readonly TaskOrchestrationService $taskService
    ) {}

    public function handle(CommandRequest $command): CommandResponse
    {
        $arguments = $command->arguments['identifier'] ?? '';
        $parts = explode(' ', trim($arguments), 2);

        $taskId = $parts[0] ?? null;
        $agentId = $parts[1] ?? null;

        if (empty($taskId) || empty($agentId)) {
            return new CommandResponse(
                message: 'Please provide both task ID and agent ID. Example: `/task-assign TASK-001 agent-slug`',
                type: 'task',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: false,
                panelData: [],
                shouldShowSuccessToast: false,
                toastData: [],
                shouldShowErrorToast: true,
            );
        }

        try {
            $result = $this->taskService->assignAgent($taskId, $agentId, [
                'status' => 'assigned',
            ]);

            $task = $result['task'];
            $assignment = $result['assignment'];

            $taskIdentifier = $task->metadata['task_code'] ?? $task->id;

            return new CommandResponse(
                message: "✅ Task {$taskIdentifier} assigned to {$assignment->agent->name}",
                type: 'task',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: false,
                panelData: [],
                shouldShowSuccessToast: true,
                toastData: [
                    'title' => 'Task Assigned',
                    'message' => "Task {$taskIdentifier} → {$assignment->agent->name}",
                ],
                shouldShowErrorToast: false,
                data: [
                    'task_id' => $task->id,
                    'task_code' => $task->metadata['task_code'] ?? null,
                    'agent_id' => $assignment->agent_id,
                    'agent_name' => $assignment->agent->name,
                    'assignment_id' => $assignment->id,
                ],
            );

        } catch (ModelNotFoundException $e) {
            $model = str_contains($e->getMessage(), 'WorkItem') ? 'Task' : 'Agent';
            $identifier = str_contains($e->getMessage(), 'WorkItem') ? $taskId : $agentId;

            return new CommandResponse(
                message: "{$model} '{$identifier}' not found. Use `/task-list` or `/agent-list` to see available items.",
                type: 'task',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: false,
                panelData: [],
                shouldShowSuccessToast: false,
                toastData: [],
                shouldShowErrorToast: true,
            );
        } catch (\Exception $e) {
            return new CommandResponse(
                message: "Error assigning task: {$e->getMessage()}",
                type: 'task',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: false,
                panelData: [],
                shouldShowSuccessToast: false,
                toastData: [],
                shouldShowErrorToast: true,
            );
        }
    }
}
