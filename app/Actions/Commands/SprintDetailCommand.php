<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Services\SprintOrchestrationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SprintDetailCommand implements HandlesCommand
{
    public function __construct(
        private readonly SprintOrchestrationService $sprintService
    ) {}

    public function handle(CommandRequest $command): CommandResponse
    {
        $sprintCode = $command->arguments['identifier'] ?? null;

        if (empty($sprintCode)) {
            return new CommandResponse(
                message: 'Please provide a sprint code. Example: `/sprint-detail sprint-1` or `/sprint-detail 1`',
                type: 'sprint',
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
            $sprint = $this->sprintService->resolveSprint($sprintCode);
            $detail = $this->sprintService->detail($sprint, [
                'include_tasks' => true,
                'tasks_limit' => 50,
            ]);

            $tasksCount = $detail['sprint']['stats']['total'] ?? 0;

            return new CommandResponse(
                message: "📋 Sprint: {$sprint->code} ({$tasksCount} tasks)",
                type: 'sprint',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'detail',
                    'sprint' => $detail['sprint'],
                    'tasks' => $detail['sprint']['tasks'] ?? [],
                    'stats' => $detail['sprint']['stats'] ?? [],
                ],
            );

        } catch (ModelNotFoundException $e) {
            return new CommandResponse(
                message: "Sprint '{$sprintCode}' not found. Use `/sprint-list` to see available sprints.",
                type: 'sprint',
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
                message: "Error retrieving sprint details: {$e->getMessage()}",
                type: 'sprint',
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
