<?php

namespace App\Http\Controllers\Orchestration;

use App\Http\Controllers\Controller;
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

        $task = WorkItem::findOrFail($id);
        $field = $validated['field'];
        $value = $validated['value'];

        $oldValue = match ($field) {
            'task_name' => Arr::get($task->metadata, 'task_name'),
            'description' => Arr::get($task->metadata, 'description'),
            'status' => $task->status,
            'priority' => $task->priority,
            'sprint_code' => Arr::get($task->metadata, 'sprint_code'),
            'estimate_text' => Arr::get($task->metadata, 'estimate_text'),
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

            TaskActivity::create([
                'task_id' => $task->id,
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

            TaskActivity::create([
                'task_id' => $task->id,
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

            TaskActivity::create([
                'task_id' => $task->id,
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

            TaskActivity::create([
                'task_id' => $task->id,
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
        } else {
            $metadata = $task->metadata ?? [];
            $metadata[$field] = $value;
            $task->metadata = $metadata;
            $task->save();

            $fieldLabel = str_replace('_', ' ', $field);

            TaskActivity::create([
                'task_id' => $task->id,
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

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function updateTags(Request $request, string $id)
    {
        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string',
        ]);

        $task = WorkItem::findOrFail($id);
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

        TaskActivity::create([
            'task_id' => $task->id,
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
        $sprints = WorkItem::where('type', 'sprint')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sprint) {
                return [
                    'value' => Arr::get($sprint->metadata, 'code'),
                    'label' => Arr::get($sprint->metadata, 'code').' - '.Arr::get($sprint->metadata, 'title', 'Untitled'),
                ];
            })
            ->filter(fn ($sprint) => ! empty($sprint['value']));

        return response()->json([
            'sprints' => $sprints->values(),
        ]);
    }
}
