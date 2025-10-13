<?php

declare(strict_types=1);

namespace App\Services\Orchestration;

use Illuminate\Support\Facades\File;

class OrchestrationPMToolsService
{
    private const ADR_TEMPLATE_PATH = 'delegation/.templates/docs/ADR_TEMPLATE.md';
    private const ADR_OUTPUT_DIR = 'docs/adr';
    private const BUG_REPORT_OUTPUT_DIR = 'delegation/backlog';

    public function generateADR(string $title, array $options = []): array
    {
        $this->sanitizeInputs($title);

        $adrNumber = $this->getNextADRNumber();
        $fileName = sprintf('ADR-%03d-%s.md', $adrNumber, $this->slugify($title));
        $filePath = base_path(self::ADR_OUTPUT_DIR . '/' . $fileName);

        $template = File::get(base_path(self::ADR_TEMPLATE_PATH));

        $content = str_replace(
            ['[Title]', 'ADR-XXX', 'YYYY-MM-DD', '[Names/Roles]'],
            [
                $title,
                sprintf('ADR-%03d', $adrNumber),
                now()->toDateString(),
                $options['deciders'] ?? 'Development Team'
            ],
            $template
        );

        if (isset($options['context'])) {
            $content = str_replace(
                '[Describe the context and problem statement]',
                $options['context'],
                $content
            );
        }

        if (isset($options['decision'])) {
            $content = str_replace(
                '[State the decision that was made]',
                $options['decision'],
                $content
            );
        }

        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $content);

        return [
            'success' => true,
            'file_path' => $filePath,
            'adr_number' => $adrNumber,
            'file_name' => $fileName,
        ];
    }

    public function generateBugReport(string $title, string $priority, array $options = []): array
    {
        $this->sanitizeInputs($title, $priority);

        $fileName = $this->slugify($title) . '.md';
        $filePath = base_path(self::BUG_REPORT_OUTPUT_DIR . '/' . $fileName);

        $content = $this->buildBugReportContent($title, $priority, $options);

        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $content);

        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'priority' => $priority,
        ];
    }

    public function updateTaskStatus(string $taskCode, string $status, array $options = []): array
    {
        $this->sanitizeInputs($taskCode, $status);

        $taskModel = \App\Models\OrchestrationTask::where('task_code', $taskCode)->firstOrFail();

        $oldStatus = $taskModel->status;

        $taskModel->status = $status;
        $taskModel->save();

        if ($options['emit_event'] ?? true) {
            app(OrchestrationEventService::class)->logTaskStatusUpdated(
                $taskModel,
                $options['notes'] ?? null,
                $options['agent_id'] ?? null,
                $options['session_key'] ?? null
            );
        }

        if ($options['sync_to_file'] ?? true) {
            app(OrchestrationFileSyncService::class)->syncTaskToFile($taskModel);
        }

        return [
            'success' => true,
            'task_code' => $taskCode,
            'old_status' => $oldStatus,
            'new_status' => $status,
            'updated_at' => $taskModel->updated_at->toISOString(),
        ];
    }

    public function generateStatusReport(string $sprintCode): array
    {
        $this->sanitizeInputs($sprintCode);

        $sprint = \App\Models\OrchestrationSprint::where('sprint_code', $sprintCode)
            ->with('tasks')
            ->firstOrFail();

        $tasks = $sprint->tasks;
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $inProgressTasks = $tasks->where('status', 'in_progress')->count();
        $blockedTasks = $tasks->where('status', 'blocked')->count();

        $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;

        $report = [
            'sprint_code' => $sprintCode,
            'sprint_title' => $sprint->title,
            'sprint_status' => $sprint->status,
            'summary' => [
                'total_tasks' => $totalTasks,
                'completed' => $completedTasks,
                'in_progress' => $inProgressTasks,
                'blocked' => $blockedTasks,
                'pending' => $totalTasks - $completedTasks - $inProgressTasks - $blockedTasks,
                'progress_percentage' => $progressPercentage,
            ],
            'tasks' => $tasks->map(fn($task) => [
                'task_code' => $task->task_code,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
            ])->toArray(),
        ];

        return $report;
    }

    private function getNextADRNumber(): int
    {
        $adrDir = base_path(self::ADR_OUTPUT_DIR);
        
        if (!File::isDirectory($adrDir)) {
            return 1;
        }

        $files = File::files($adrDir);
        $numbers = [];

        foreach ($files as $file) {
            if (preg_match('/^ADR-(\d{3})/', $file->getFilename(), $matches)) {
                $numbers[] = (int) $matches[1];
            }
        }

        return empty($numbers) ? 1 : max($numbers) + 1;
    }

    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }

    private function buildBugReportContent(string $title, string $priority, array $options): string
    {
        $date = now()->toDateString();
        $category = $options['category'] ?? 'Bug';
        $component = $options['component'] ?? 'Unknown';
        $effort = $options['effort'] ?? 'Unknown';
        $description = $options['description'] ?? '[Describe the problem]';
        $reproduction = $options['reproduction_steps'] ?? '[Add reproduction steps]';
        $expected = $options['expected_behavior'] ?? '[Describe expected behavior]';
        $actual = $options['actual_behavior'] ?? '[Describe actual behavior]';

        return <<<MD
# {$title}

**Created**: {$date}  
**Priority**: {$priority}  
**Category**: {$category}  
**Component**: {$component}  
**Estimated Effort**: {$effort}

---

## Problem Statement

{$description}

---

## Reproduction Steps

{$reproduction}

---

## Expected Behavior

{$expected}

---

## Actual Behavior

{$actual}

---

## Technical Context

[Add technical details, stack traces, logs, etc.]

---

## Proposed Solution

[Add proposed solution or investigation plan]

---

## Files to Review

[Add relevant file paths]

---

## Related Issues

[Add links to related issues, PRs, or documentation]

MD;
    }

    private function sanitizeInputs(string ...$inputs): void
    {
        foreach ($inputs as $input) {
            if (str_contains($input, '..') || str_contains($input, '/') || str_contains($input, '\\')) {
                throw new \InvalidArgumentException('Invalid input: path traversal detected');
            }
        }
    }
}
