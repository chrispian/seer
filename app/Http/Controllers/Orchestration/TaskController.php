<?php

namespace App\Http\Controllers\Orchestration;

use App\Http\Controllers\Controller;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Models\TaskActivity;
use App\Models\WorkItem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TaskController extends Controller
{
    public function updateField(Request $request, string $id)
    {
        $validated = $request->validate([
            'field' => 'required|string|in:task_name,description,status,priority,sprint_code,estimate_text,assignee_id,agent_content,plan_content,context_content,todo_content,summary_content',
            'value' => 'nullable|string',
        ]);

        $task = OrchestrationTask::findOrFail($id);
        $field = $validated['field'];
        $value = $validated['value'];

        $oldValue = match ($field) {
            'task_name' => $task->title,
            'description' => $task->metadata['description'] ?? null,
            'status' => $task->status,
            'priority' => $task->priority,
            'sprint_code' => $task->sprint_id ? OrchestrationSprint::find($task->sprint_id)?->sprint_code : null,
            'estimate_text' => $task->estimated_hours,
            'assignee_id' => $task->assignee_id,
            'agent_content' => $task->agent_content,
            'plan_content' => $task->plan_content,
            'context_content' => $task->context_content,
            'todo_content' => $task->todo_content,
            'summary_content' => $task->summary_content,
        };

        if ($field === 'status') {
            $task->status = $value;
            $task->save();

            $this->logActivity($task->id, [
                'user_id' => auth()->id(),
                'activity_type' => 'status_change',
                'action' => 'status_updated',
                'description' => "Status changed from {$oldValue} to {$value}",
                'changes' => [
                    'field' => 'status',
                    'old_value' => $oldValue,
                    'new_value' => $value,
                ],
            ]);
        } elseif ($field === 'priority') {
            $task->priority = $value;
            $task->save();

            $this->logActivity($task->id, [
                'user_id' => auth()->id(),
                'activity_type' => 'content_update',
                'action' => 'field_updated',
                'description' => "Priority changed from {$oldValue} to {$value}",
                'changes' => [
                    'field' => 'priority',
                    'old_value' => $oldValue,
                    'new_value' => $value,
                ],
            ]);
        } elseif ($field === 'assignee_id') {
            $task->assignee_id = $value ?: null;
            $task->assignee_type = $value ? 'agent' : null;
            $task->save();

            $this->logActivity($task->id, [
                'user_id' => auth()->id(),
                'activity_type' => 'assignment',
                'action' => 'agent_assigned',
                'description' => $value ? "Agent assigned: {$value}" : 'Agent unassigned',
                'changes' => [
                    'field' => 'assignee_id',
                    'old_value' => $oldValue,
                    'new_value' => $value,
                ],
            ]);
        } elseif (in_array($field, ['agent_content', 'plan_content', 'context_content', 'todo_content', 'summary_content'])) {
            $task->{$field} = $value;
            $task->save();

            $fieldLabel = str_replace('_content', '', $field);

            $this->logActivity($task->id, [
                'user_id' => auth()->id(),
                'activity_type' => 'content_update',
                'action' => 'content_updated',
                'description' => ucfirst($fieldLabel).' content updated',
                'changes' => [
                    'field' => $field,
                    'old_value' => substr($oldValue ?? '', 0, 100),
                    'new_value' => substr($value ?? '', 0, 100),
                ],
            ]);
        } elseif ($field === 'task_name') {
            $task->title = $value;
            $task->save();

            $this->logActivity($task->id, [
                'user_id' => auth()->id(),
                'activity_type' => 'content_update',
                'action' => 'field_updated',
                'description' => "Task name changed from {$oldValue} to {$value}",
                'changes' => [
                    'field' => 'title',
                    'old_value' => $oldValue,
                    'new_value' => $value,
                ],
            ]);
        } elseif ($field === 'sprint_code') {
            // Find sprint by code and set sprint_id
            $sprint = OrchestrationSprint::where('sprint_code', $value)->first();
            $task->sprint_id = $sprint?->id;
            $task->save();

            $this->logActivity($task->id, [
                'user_id' => auth()->id(),
                'activity_type' => 'content_update',
                'action' => 'sprint_assigned',
                'description' => "Sprint changed to {$value}",
                'changes' => [
                    'field' => 'sprint_id',
                    'old_value' => $oldValue,
                    'new_value' => $value,
                ],
            ]);
        } elseif ($field === 'estimate_text') {
            // Convert estimate text to hours if numeric
            $task->estimated_hours = is_numeric($value) ? floatval($value) : null;
            $task->save();

            $this->logActivity($task->id, [
                'user_id' => auth()->id(),
                'activity_type' => 'content_update',
                'action' => 'field_updated',
                'description' => "Estimate updated to {$value}",
                'changes' => [
                    'field' => 'estimated_hours',
                    'old_value' => $oldValue,
                    'new_value' => $value,
                ],
            ]);
        } else {
            // Fallback: store in metadata JSON
            $metadata = $task->metadata ?? [];
            $metadata[$field] = $value;
            $task->metadata = $metadata;
            $task->save();

            $fieldLabel = str_replace('_', ' ', $field);

            $this->logActivity($task->id, [
                'user_id' => auth()->id(),
                'activity_type' => 'content_update',
                'action' => 'field_updated',
                'description' => ucfirst($fieldLabel).' updated',
                'changes' => [
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $value,
                ],
            ]);
        }

        $task->refresh();

        // Get sprint_code if sprint_id is set
        $sprintCode = null;
        if ($task->sprint_id) {
            $sprint = OrchestrationSprint::find($task->sprint_id);
            $sprintCode = $sprint?->sprint_code;
        }

        // Get assignee_name if assignee_id is set
        $assigneeName = null;
        if ($task->assignee_id && $task->assignee_type && strtolower($task->assignee_type) === 'agent') {
            $agent = \App\Models\AgentProfile::find($task->assignee_id);
            $assigneeName = $agent?->name;
        }

        return response()->json([
            'success' => true,
            'task' => [
                'id' => $task->id,
                'task_code' => $task->task_code,
                'task_name' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'delegation_status' => $task->delegation_status,
                'priority' => $task->priority,
                'sprint_code' => $sprintCode,
                'assignee_id' => $task->assignee_id,
                'assignee_name' => $assigneeName,
                'assignee_type' => $task->assignee_type,
                'estimate_text' => $task->estimated_hours ? $task->estimated_hours . ' hours' : null,
                'tags' => $task->tags ?? [],
                'created_at' => $task->created_at?->toIso8601String(),
                'updated_at' => $task->updated_at?->toIso8601String(),
                'completed_at' => $task->completed_at?->toIso8601String(),
                'metadata' => $task->metadata ?? [],
            ],
        ]);
    }

    public function updateTags(Request $request, string $id)
    {
        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string',
        ]);

        $task = OrchestrationTask::findOrFail($id);
        $oldTags = $task->tags ?? [];
        $newTags = $validated['tags'];

        $task->tags = $newTags;
        $task->save();

        $added = array_diff($newTags, $oldTags);
        $removed = array_diff($oldTags, $newTags);

        $description = 'Tags updated';
        if (! empty($added) && ! empty($removed)) {
            $description = 'Tags updated: added '.implode(', ', $added).'; removed '.implode(', ', $removed);
        } elseif (! empty($added)) {
            $description = 'Tags added: '.implode(', ', $added);
        } elseif (! empty($removed)) {
            $description = 'Tags removed: '.implode(', ', $removed);
        }

        $this->logActivity($task->id, [
            'user_id' => auth()->id(),
            'activity_type' => 'content_update',
            'action' => 'tags_updated',
            'description' => $description,
            'changes' => [
                'field' => 'tags',
                'old_value' => $oldTags,
                'new_value' => $newTags,
            ],
        ]);

        $task->refresh();

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function getAvailableSprints()
    {
        $sprints = OrchestrationSprint::orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sprint) {
                return [
                    'id' => $sprint->id,
                    'code' => $sprint->sprint_code,
                    'title' => $sprint->title,
                    'status' => $sprint->status,
                    'task_count' => $sprint->tasks()->count(),
                    'completed_tasks' => $sprint->tasks()->where('status', 'completed')->count(),
                    // For InlineEditSelect compatibility
                    'value' => $sprint->sprint_code,
                    'label' => $sprint->sprint_code.' - '.($sprint->title ?? 'Untitled'),
                ];
            })
            ->filter(fn ($sprint) => ! empty($sprint['value']));

        return response()->json([
            'sprints' => $sprints->values(),
        ]);
    }

    /**
     * Safely create task activity (skip if UUID mismatch for OrchestrationTask)
     */
    private function logActivity(int $taskId, array $data): void
    {
        try {
            TaskActivity::create(array_merge(['task_id' => $taskId], $data));
        } catch (\Exception $e) {
            // TODO: Update task_activities table to support integer task_id for OrchestrationTask
            \Log::warning('Failed to log task activity', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
