<?php

namespace App\Mcp\Tools;

use App\Models\TaskActivity;
use App\Models\WorkItem;
use EchoLabs\ToolCrate\Contracts\Tool;
use EchoLabs\ToolCrate\Tool as ToolCrate;

class TaskActivitiesListTool implements Tool
{
    public function __invoke(string $taskId, ?string $type = null, int $limit = 20): string
    {
        try {
            $task = WorkItem::where('id', $taskId)
                ->orWhere('metadata->task_code', $taskId)
                ->firstOrFail();

            $query = TaskActivity::query()
                ->forTask($task->id)
                ->with(['agent:id,name,slug', 'user:id,name']);

            if ($type) {
                $query->byType($type);
            }

            $activities = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            if ($activities->isEmpty()) {
                return "No activities found for task {$task->metadata['task_code']}";
            }

            $output = "# Task Activities: {$task->metadata['task_code']}\n\n";
            $output .= "Total: {$activities->count()} activities\n\n";

            foreach ($activities as $activity) {
                $output .= $this->formatActivity($activity);
            }

            return $output;
        } catch (\Exception $e) {
            return "Error retrieving activities: {$e->getMessage()}";
        }
    }

    protected function formatActivity(TaskActivity $activity): string
    {
        $actor = $activity->agent?->name ?? $activity->user?->name ?? 'System';
        $timestamp = $activity->created_at->format('Y-m-d H:i:s');

        $output = "## [{$activity->activity_type}] {$activity->action}\n";
        $output .= "**Time**: {$timestamp}\n";
        $output .= "**Actor**: {$actor}\n";
        $output .= "**Description**: {$activity->description}\n";

        if ($activity->changes) {
            $output .= "**Changes**:\n";
            $output .= "```json\n".json_encode($activity->changes, JSON_PRETTY_PRINT)."\n```\n";
        }

        if ($activity->metadata) {
            $output .= "**Metadata**:\n";
            $output .= "```json\n".json_encode($activity->metadata, JSON_PRETTY_PRINT)."\n```\n";
        }

        $output .= "\n---\n\n";

        return $output;
    }

    public static function definition(): array
    {
        return ToolCrate::create()
            ->name('task-activities:list')
            ->description('List activity log entries for a task')
            ->parameters([
                'taskId' => [
                    'type' => 'string',
                    'description' => 'Task ID or task code (e.g., T-ORCH-001)',
                    'required' => true,
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Filter by activity type: status_change, content_update, assignment, note, error, artifact_attached',
                    'required' => false,
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of activities to return (default: 20)',
                    'required' => false,
                ],
            ])
            ->toArray();
    }
}
