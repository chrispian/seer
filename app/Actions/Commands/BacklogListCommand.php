<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\WorkItem;

class BacklogListCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $tasks = WorkItem::query()
            ->where('status', 'backlog')
            ->orWhere(function ($query) {
                $query->where('metadata->backlog_import', true)
                    ->where('status', 'done');
            })
            ->orderByDesc('created_at')
            ->take(100)
            ->get();

        if ($tasks->isEmpty()) {
            return new CommandResponse(
                message: '📋 No backlog items found.',
                type: 'backlog',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'backlog_list',
                    'message' => '📋 No backlog items found.',
                    'tasks' => [],
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
                'status' => $task->status,
                'delegation_status' => $task->delegation_status,
                'priority' => $task->priority,
                'agent_recommendation' => $metadata['agent_recommendation'] ?? null,
                'estimate_text' => $metadata['estimate_text'] ?? null,
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

        return new CommandResponse(
            message: '📋 Found **'.count($taskData).'** backlog item'.(count($taskData) !== 1 ? 's' : ''),
            type: 'backlog',
            fragments: [],
            shouldResetChat: false,
            shouldOpenPanel: true,
            panelData: [
                'action' => 'backlog_list',
                'message' => '📋 Found **'.count($taskData).'** backlog item'.(count($taskData) !== 1 ? 's' : ''),
                'tasks' => $taskData,
            ],
        );
    }
}
