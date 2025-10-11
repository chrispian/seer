<?php

namespace App\Services\Orchestration\Validation;

use App\Models\Sprint;
use App\Models\WorkItem;

class SprintCompletionValidator
{
    public function validate(Sprint $sprint): array
    {
        $errors = [];
        $warnings = [];
        $requirements = [];

        $tasks = $this->checkTasks($sprint);
        $summary = $this->checkSummary($sprint);

        if (!$tasks['valid']) {
            $errors[] = $tasks['message'];
            $requirements[] = [
                'type' => 'tasks',
                'message' => $tasks['message'],
                'incomplete_count' => $tasks['incomplete_count'] ?? 0,
            ];
        }

        if (!$summary['valid']) {
            $warnings[] = $summary['message'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'requirements' => $requirements,
            'details' => [
                'tasks' => $tasks,
                'summary' => $summary,
            ],
        ];
    }

    protected function checkTasks(Sprint $sprint): array
    {
        $tasks = WorkItem::where('metadata->sprint_code', $sprint->code)
            ->orWhere('sprint_id', $sprint->id)
            ->get();

        if ($tasks->isEmpty()) {
            return [
                'valid' => true,
                'message' => 'Sprint has no tasks',
                'has_tasks' => false,
            ];
        }

        $incomplete = $tasks->filter(function ($task) {
            return !in_array($task->status, ['done', 'completed']);
        });

        if ($incomplete->count() > 0) {
            $incompleteCodes = $incomplete->map(function ($task) {
                return $task->metadata['task_code'] ?? $task->id;
            })->toArray();

            return [
                'valid' => false,
                'message' => "Sprint has {$incomplete->count()} incomplete task(s)",
                'has_tasks' => true,
                'total_tasks' => $tasks->count(),
                'incomplete_count' => $incomplete->count(),
                'incomplete_tasks' => $incompleteCodes,
            ];
        }

        return [
            'valid' => true,
            'has_tasks' => true,
            'total_tasks' => $tasks->count(),
            'incomplete_count' => 0,
        ];
    }

    protected function checkSummary(Sprint $sprint): array
    {
        $notes = $sprint->notes ?? [];

        if (empty($notes)) {
            return [
                'valid' => false,
                'message' => 'Sprint has no summary or notes',
                'has_notes' => false,
            ];
        }

        return [
            'valid' => true,
            'has_notes' => true,
            'notes_count' => count($notes),
        ];
    }

    public function generateChecklist(Sprint $sprint): array
    {
        $checklist = [];

        $tasks = WorkItem::where('metadata->sprint_code', $sprint->code)
            ->orWhere('sprint_id', $sprint->id)
            ->get();

        if ($tasks->isNotEmpty()) {
            $incomplete = $tasks->filter(fn($t) => !in_array($t->status, ['done', 'completed']));
            $checklist[] = [
                'type' => 'required',
                'label' => "Complete all tasks ({$tasks->count() - $incomplete->count()}/{$tasks->count()})",
                'completed' => $incomplete->isEmpty(),
            ];
        }

        $notes = $sprint->notes ?? [];
        $checklist[] = [
            'type' => 'recommended',
            'label' => 'Add sprint summary/notes',
            'completed' => !empty($notes),
        ];

        return $checklist;
    }
}
