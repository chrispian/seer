<?php

namespace App\Services\Orchestration\Validation;

use App\Models\SessionActivity;
use App\Models\WorkItem;
use App\Models\WorkSession;

class TaskCompletionValidator
{
    public function validate(WorkItem $task, ?WorkSession $session = null): array
    {
        $errors = [];
        $warnings = [];
        $requirements = [];

        $summary = $this->checkSummary($task);
        $context = $this->checkContext($task, $session);
        $subtasks = $this->checkSubtasks($task);
        $timeTracking = $this->checkTimeTracking($task);

        if (!$summary['valid']) {
            $errors[] = $summary['message'];
            $requirements[] = [
                'type' => 'summary',
                'message' => $summary['message'],
                'field' => 'summary_content',
            ];
        } else if (!empty($summary['warning'])) {
            $warnings[] = $summary['warning'];
        }

        if (!$context['valid']) {
            $warnings[] = $context['message'];
        }

        if (!$subtasks['valid']) {
            $errors[] = $subtasks['message'];
            $requirements[] = [
                'type' => 'subtasks',
                'message' => $subtasks['message'],
                'incomplete_count' => $subtasks['incomplete_count'] ?? 0,
            ];
        }

        if (!empty($timeTracking['warning'])) {
            $warnings[] = $timeTracking['warning'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'requirements' => $requirements,
            'details' => [
                'summary' => $summary,
                'context' => $context,
                'subtasks' => $subtasks,
                'time_tracking' => $timeTracking,
            ],
        ];
    }

    protected function checkSummary(WorkItem $task): array
    {
        $summary = $task->summary_content;

        if (empty($summary)) {
            return [
                'valid' => false,
                'message' => 'Task requires a summary before completion',
                'has_summary' => false,
            ];
        }

        if (strlen($summary) < 20) {
            return [
                'valid' => true,
                'warning' => 'Task summary is very short (< 20 characters)',
                'has_summary' => true,
                'length' => strlen($summary),
            ];
        }

        return [
            'valid' => true,
            'has_summary' => true,
            'length' => strlen($summary),
        ];
    }

    protected function checkContext(WorkItem $task, ?WorkSession $session): array
    {
        $contextUpdates = 0;

        if ($session) {
            $contextUpdates = SessionActivity::where('session_id', $session->id)
                ->where('task_id', $task->id)
                ->where('activity_type', 'context_update')
                ->count();
        }

        $hasContext = !empty($task->context_content);

        if ($contextUpdates === 0 && !$hasContext) {
            return [
                'valid' => false,
                'message' => 'No context updates logged during work session',
                'context_updates' => 0,
                'has_context' => false,
            ];
        }

        if ($contextUpdates < 2 && !$hasContext) {
            return [
                'valid' => true,
                'message' => 'Very few context updates (< 2) logged during work',
                'context_updates' => $contextUpdates,
                'has_context' => false,
            ];
        }

        return [
            'valid' => true,
            'context_updates' => $contextUpdates,
            'has_context' => $hasContext,
        ];
    }

    protected function checkSubtasks(WorkItem $task): array
    {
        $subtasks = $task->children()->get();

        if ($subtasks->isEmpty()) {
            return [
                'valid' => true,
                'has_subtasks' => false,
            ];
        }

        $incomplete = $subtasks->filter(function ($subtask) {
            return !in_array($subtask->status, ['done', 'completed']);
        });

        if ($incomplete->count() > 0) {
            return [
                'valid' => false,
                'message' => "Task has {$incomplete->count()} incomplete subtask(s)",
                'has_subtasks' => true,
                'total_subtasks' => $subtasks->count(),
                'incomplete_count' => $incomplete->count(),
                'incomplete_tasks' => $incomplete->pluck('metadata.task_code')->toArray(),
            ];
        }

        return [
            'valid' => true,
            'has_subtasks' => true,
            'total_subtasks' => $subtasks->count(),
            'incomplete_count' => 0,
        ];
    }

    protected function checkTimeTracking(WorkItem $task): array
    {
        $estimated = $task->estimated_hours;
        $actual = $task->actual_hours;

        if (empty($estimated) && empty($actual)) {
            return [
                'valid' => true,
                'warning' => 'No time tracking data available',
                'has_estimate' => false,
                'has_actual' => false,
            ];
        }

        if (!empty($estimated) && empty($actual)) {
            return [
                'valid' => true,
                'warning' => 'Task has estimate but no actual time logged',
                'has_estimate' => true,
                'has_actual' => false,
                'estimated_hours' => $estimated,
            ];
        }

        $variance = null;
        if (!empty($estimated) && !empty($actual)) {
            $variance = (($actual - $estimated) / $estimated) * 100;
        }

        return [
            'valid' => true,
            'has_estimate' => !empty($estimated),
            'has_actual' => !empty($actual),
            'estimated_hours' => $estimated,
            'actual_hours' => $actual,
            'variance_percent' => $variance,
        ];
    }

    public function generateChecklist(WorkItem $task): array
    {
        $checklist = [];

        if (empty($task->summary_content)) {
            $checklist[] = [
                'type' => 'required',
                'label' => 'Add task summary',
                'completed' => false,
            ];
        } else {
            $checklist[] = [
                'type' => 'required',
                'label' => 'Task summary provided',
                'completed' => true,
            ];
        }

        if (!empty($task->context_content)) {
            $checklist[] = [
                'type' => 'recommended',
                'label' => 'Context updates documented',
                'completed' => true,
            ];
        } else {
            $checklist[] = [
                'type' => 'recommended',
                'label' => 'Add context updates',
                'completed' => false,
            ];
        }

        $subtasks = $task->children()->get();
        if ($subtasks->isNotEmpty()) {
            $incomplete = $subtasks->filter(fn($st) => !in_array($st->status, ['done', 'completed']));
            $checklist[] = [
                'type' => 'required',
                'label' => "Complete all subtasks ({$subtasks->count() - $incomplete->count()}/{$subtasks->count()})",
                'completed' => $incomplete->isEmpty(),
            ];
        }

        if (!empty($task->estimated_hours) && empty($task->actual_hours)) {
            $checklist[] = [
                'type' => 'recommended',
                'label' => 'Log actual time spent',
                'completed' => false,
            ];
        }

        return $checklist;
    }
}
