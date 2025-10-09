<?php

namespace App\Mcp\Tools;

use App\Models\TaskActivity;
use App\Models\WorkItem;
use HollisLabs\ToolCrate\Contracts\Tool;
use EchoLabs\ToolCrate\Tool as ToolCrate;

class TaskActivitiesLogTool implements Tool
{
    public function __invoke(
        string $taskId,
        string $description,
        string $activityType = 'note',
        ?array $metadata = null
    ): string {
        try {
            $task = WorkItem::where('id', $taskId)
                ->orWhere('metadata->task_code', $taskId)
                ->firstOrFail();

            $validTypes = ['note', 'error'];
            if (! in_array($activityType, $validTypes)) {
                return "Invalid activity type. Must be one of: " . implode(', ', $validTypes);
            }

            $activity = TaskActivity::create([
                'task_id' => $task->id,
                'activity_type' => $activityType,
                'action' => $activityType === 'note' ? 'note_added' : 'error_encountered',
                'description' => $description,
                'metadata' => $metadata,
            ]);

            $taskCode = $task->metadata['task_code'] ?? $task->id;

            return "✓ Activity logged for task {$taskCode}\n"
                . "Type: {$activityType}\n"
                . "Description: {$description}\n"
                . "Time: {$activity->created_at->format('Y-m-d H:i:s')}";
        } catch (\Exception $e) {
            return "Error logging activity: {$e->getMessage()}";
        }
    }

    public static function definition(): array
    {
        return ToolCrate::create()
            ->name('task-activities:log')
            ->description('Add a note or error activity to a task log')
            ->parameters([
                'taskId' => [
                    'type' => 'string',
                    'description' => 'Task ID or task code (e.g., T-ORCH-001)',
                    'required' => true,
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Activity description',
                    'required' => true,
                ],
                'activityType' => [
                    'type' => 'string',
                    'description' => 'Activity type: note or error (default: note)',
                    'required' => false,
                ],
                'metadata' => [
                    'type' => 'object',
                    'description' => 'Additional metadata (JSON object)',
                    'required' => false,
                ],
            ])
            ->toArray();
    }
}
