<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\WorkItem;

class TaskListCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $sprintCode = $command->arguments['identifier'] ?? null;
        $query = WorkItem::query();

        // Filter by sprint if provided
        if ($sprintCode) {
            $normalizedCode = $this->normalizeSprintCode($sprintCode);
            $query->where('metadata->sprint_code', $normalizedCode);
        }

        $tasks = $query->with(['assignedAgent'])->orderByDesc('created_at')->take(50)->get();

        if ($tasks->isEmpty()) {
            $message = $sprintCode
                ? "📋 No tasks found for sprint: {$sprintCode}. Use `/sprint-list` to see available sprints."
                : '📋 No tasks found. Import delegation data to populate tasks.';

            return new CommandResponse(
                message: $message,
                type: 'task',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'list',
                    'message' => $message,
                    'tasks' => [],
                    'sprint_filter' => $sprintCode,
                ],
            );
        }

        $taskData = $tasks->map(function (WorkItem $task) {
            $metadata = $task->metadata ?? [];

            return [
                'id' => $task->id,
                'task_code' => $metadata['task_code'] ?? $task->id,
                'task_name' => $metadata['task_name'] ?? 'Untitled Task',
                'description' => $metadata['description'] ?? null,
                'sprint_code' => $metadata['sprint_code'] ?? null,
                'status' => $task->status,
                'delegation_status' => $task->delegation_status,
                'priority' => $task->priority,
                'agent_recommendation' => $metadata['agent_recommendation'] ?? null,
                'current_agent' => $task->assignee_type === 'agent' ? $task->assignedAgent?->name : null,
                'estimate_text' => $metadata['estimate_text'] ?? null,
                'estimated_hours' => $task->estimated_hours,
                'tags' => $task->tags ?? [],
                'has_content' => [
                    'agent' => ! empty($task->agent_content),
                    'plan' => ! empty($task->plan_content),
                    'context' => ! empty($task->context_content),
                    'todo' => ! empty($task->todo_content),
                    'summary' => ! empty($task->summary_content),
                ],
                'created_at' => $task->created_at?->toIso8601String(),
                'updated_at' => $task->updated_at?->toIso8601String(),
                'completed_at' => $task->completed_at?->toIso8601String(),
            ];
        })->all();

        $message = $sprintCode
            ? '📋 Found **'.count($taskData).'** task'.(count($taskData) !== 1 ? 's' : '')." for sprint: {$sprintCode}"
            : '📋 Found **'.count($taskData).'** task'.(count($taskData) !== 1 ? 's' : '');

        return new CommandResponse(
            message: $message,
            type: 'task',
            fragments: [],
            shouldResetChat: false,
            shouldOpenPanel: true,
            panelData: [
                'action' => 'list',
                'message' => $message,
                'tasks' => $taskData,
                'sprint_filter' => $sprintCode,
            ],
        );
    }

    private function normalizeSprintCode(string $code): string
    {
        $code = trim($code);

        if (preg_match('/^\d+$/', $code)) {
            return 'SPRINT-'.str_pad($code, 2, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^(?:sprint-)?(\d+)$/i', $code, $matches)) {
            return 'SPRINT-'.str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }

        return strtoupper($code);
    }
}
