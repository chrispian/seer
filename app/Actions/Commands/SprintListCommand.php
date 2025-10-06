<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Sprint;

class SprintListCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $sprints = Sprint::query()
            ->orderBy('code')
            ->get();

        if ($sprints->isEmpty()) {
            return new CommandResponse(
                message: '📋 No sprints found. Import delegation data to populate sprints.',
                type: 'sprint',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'list',
                    'message' => '📋 No sprints found. Import delegation data to populate sprints.',
                    'sprints' => [],
                ],
            );
        }

        $sprintData = $sprints->map(function (Sprint $sprint) {
            $tasks = \App\Models\WorkItem::query()
                ->where('metadata->sprint_code', $sprint->code)
                ->get();

            $taskCount = $tasks->count();
            $completedTasks = $tasks->where('status', 'done')->count();
            $inProgressTasks = $tasks->where('status', 'in-progress')->count();
            $todoTasks = $tasks->where('status', 'todo')->count();
            $backlogTasks = $tasks->where('status', 'backlog')->count();

            $status = $sprint->meta['status'] ?? 'active';

            return [
                'id' => $sprint->id,
                'code' => $sprint->code,
                'title' => $sprint->meta['title'] ?? $sprint->code,
                'description' => $sprint->meta['description'] ?? null,
                'status' => $status,
                'task_count' => $taskCount,
                'completed_tasks' => $completedTasks,
                'in_progress_tasks' => $inProgressTasks,
                'todo_tasks' => $todoTasks,
                'backlog_tasks' => $backlogTasks,
                'priority' => $sprint->meta['priority'] ?? null,
                'meta' => $sprint->meta ?? [],
                'created_at' => $sprint->created_at?->toIso8601String(),
                'updated_at' => $sprint->updated_at?->toIso8601String(),
            ];
        })->all();

        return new CommandResponse(
            message: '📋 Found **'.count($sprintData).'** sprint'.((count($sprintData) !== 1) ? 's' : ''),
            type: 'sprint',
            fragments: [],
            shouldResetChat: false,
            shouldOpenPanel: true,
            panelData: [
                'action' => 'list',
                'message' => '📋 Found **'.count($sprintData).'** sprint'.((count($sprintData) !== 1) ? 's' : ''),
                'sprints' => $sprintData,
            ],
        );
    }
}
