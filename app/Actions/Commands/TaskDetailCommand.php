<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Services\TaskOrchestrationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskDetailCommand implements HandlesCommand
{
    public function __construct(
        private readonly TaskOrchestrationService $taskService
    ) {}

    public function handle(CommandRequest $command): CommandResponse
    {
        $taskId = $command->arguments['identifier'] ?? null;

        if (empty($taskId)) {
            return new CommandResponse(
                message: 'Please provide a task ID. Example: `/task-detail TASK-001` or `/task-detail uuid`',
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
            $detail = $this->taskService->detail($taskId, [
                'assignments_limit' => 20,
                'include_history' => true,
            ]);

            $task = $detail['task'];
            $currentAssignment = $detail['current_assignment'];

            $taskIdentifier = $task['task_code'] ?? $task['id'];

            return new CommandResponse(
                message: "📋 Task: {$taskIdentifier} ({$task['delegation_status']})",
                type: 'task',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'detail',
                    'task' => $task,
                    'current_assignment' => $currentAssignment,
                    'assignments' => $detail['assignments'] ?? [],
                    'content' => [
                        'agent' => $this->getTaskContent($task['id'], 'agent_content'),
                        'plan' => $this->getTaskContent($task['id'], 'plan_content'),
                        'context' => $this->getTaskContent($task['id'], 'context_content'),
                        'todo' => $this->getTaskContent($task['id'], 'todo_content'),
                        'summary' => $this->getTaskContent($task['id'], 'summary_content'),
                    ],
                ],
            );

        } catch (ModelNotFoundException $e) {
            return new CommandResponse(
                message: "Task '{$taskId}' not found. Use `/task-list` to see available tasks.",
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
                message: "Error retrieving task details: {$e->getMessage()}",
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

    private function getTaskContent(string $taskId, string $contentField): ?string
    {
        try {
            $workItem = \App\Models\WorkItem::find($taskId);

            return $workItem ? $workItem->{$contentField} : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
