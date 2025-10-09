<?php

namespace App\Mcp\Tools;

use App\Models\WorkItem;
use App\Services\Orchestration\TaskContentService;
use EchoLabs\ToolCrate\Contracts\Tool;
use EchoLabs\ToolCrate\Tool as ToolCrate;

class TaskContentUpdateTool implements Tool
{
    public function __invoke(string $taskId, string $field, string $content): string
    {
        try {
            $task = WorkItem::where('id', $taskId)
                ->orWhere('metadata->task_code', $taskId)
                ->firstOrFail();

            $validFields = ['agent_content', 'plan_content', 'context_content', 'todo_content', 'summary_content'];
            if (! in_array($field, $validFields)) {
                return "Invalid field. Must be one of: " . implode(', ', $validFields);
            }

            $contentService = app(TaskContentService::class);
            $contentService->updateContent($task, $field, $content);

            $taskCode = $task->metadata['task_code'] ?? $task->id;
            $contentSize = strlen($content);
            $isArtifact = $contentService->isArtifactReference($task->fresh()->{$field} ?? '');

            $output = "✓ Updated {$field} for task {$taskCode}\n";
            $output .= "Content size: " . number_format($contentSize) . " bytes\n";
            
            if ($isArtifact) {
                $output .= "⚠ Content too large, stored as artifact\n";
            } else {
                $output .= "✓ Content stored in database\n";
            }

            return $output;
        } catch (\Exception $e) {
            return "Error updating content: {$e->getMessage()}";
        }
    }

    public static function definition(): array
    {
        return ToolCrate::create()
            ->name('task-content:update')
            ->description('Update task content fields (agent_content, plan_content, context_content, todo_content, summary_content)')
            ->parameters([
                'taskId' => [
                    'type' => 'string',
                    'description' => 'Task ID or task code (e.g., T-ORCH-001)',
                    'required' => true,
                ],
                'field' => [
                    'type' => 'string',
                    'description' => 'Content field: agent_content, plan_content, context_content, todo_content, summary_content',
                    'required' => true,
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'New content for the field',
                    'required' => true,
                ],
            ])
            ->toArray();
    }
}
