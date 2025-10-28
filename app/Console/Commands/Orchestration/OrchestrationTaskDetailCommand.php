<?php

namespace App\Console\Commands\Orchestration;

use App\Services\TaskOrchestrationService;
use Illuminate\Console\Command;

class OrchestrationTaskDetailCommand extends Command
{
    protected $signature = 'orchestration:task:detail
        {task : Task UUID or delegation task code}
        {--assignments-limit=10 : Number of assignments to include}
        {--history : Include delegation history in table output}
        {--json : Output JSON instead of textual summary}';

    protected $description = 'Show delegation-aware detail for a work item.';

    public function handle(TaskOrchestrationService $service): int
    {
        $task = $this->argument('task');
        $assignmentsLimit = (int) $this->option('assignments-limit');
        $includeHistory = (bool) $this->option('history') || (bool) $this->option('json');

        $detail = $service->detail($task, [
            'assignments_limit' => $assignmentsLimit,
            'include_history' => $includeHistory,
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($detail, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $taskInfo = $detail['task'];
        $this->table(['Field', 'Value'], [
            ['Task Code', $taskInfo['task_code'] ?? '—'],
            ['Status', $taskInfo['status'] ?? '—'],
            ['Delegation', $taskInfo['delegation_status'] ?? '—'],
            ['Priority', $taskInfo['priority'] ?? '—'],
            ['Estimate', $taskInfo['metadata']['estimate_text'] ?? '—'],
            ['Updated', $taskInfo['updated_at'] ?? '—'],
        ]);

        if (! empty($detail['current_assignment'])) {
            $current = $detail['current_assignment'];
            $this->info('Current Assignment');
            $this->table(['Field', 'Value'], [
                ['Agent', $current['agent_name'] ?? $current['agent_slug'] ?? '—'],
                ['Status', $current['status'] ?? '—'],
                ['Assigned', $current['assigned_at'] ?? '—'],
                ['Started', $current['started_at'] ?? '—'],
                ['Completed', $current['completed_at'] ?? '—'],
            ]);
        }

        if (! empty($detail['assignments'])) {
            $this->info('Recent Assignments');
            $this->table(
                ['Agent', 'Status', 'Assigned', 'Completed'],
                collect($detail['assignments'])->map(function ($assignment) {
                    return [
                        $assignment['agent_name'] ?? $assignment['agent_slug'] ?? '—',
                        $assignment['status'] ?? '—',
                        $assignment['assigned_at'] ?? '—',
                        $assignment['completed_at'] ?? '—',
                    ];
                })->toArray()
            );
        }

        if ($includeHistory && ! empty($taskInfo['delegation_history'])) {
            $this->info('Delegation History');
            $this->table(
                ['Timestamp', 'Action', 'Status', 'Note'],
                collect($taskInfo['delegation_history'])->map(function ($entry) {
                    return [
                        $entry['timestamp'] ?? '—',
                        $entry['action'] ?? '—',
                        $entry['status'] ?? '—',
                        $entry['note'] ?? '—',
                    ];
                })->toArray()
            );
        }

        return self::SUCCESS;
    }
}
