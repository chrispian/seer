<?php

namespace App\Console\Commands;

use App\Services\Orchestration\OrchestrationPMToolsService;
use Illuminate\Console\Command;

class OrchestrationStatusReport extends Command
{
    protected $signature = 'orchestration:status-report 
                            {sprint_code : The sprint code (e.g., orchestration-api-v2)}
                            {--json : Output as JSON}';

    protected $description = 'Generate a status report for a sprint';

    public function handle(OrchestrationPMToolsService $pmToolsService): int
    {
        $sprintCode = $this->argument('sprint_code');

        try {
            $report = $pmToolsService->generateStatusReport($sprintCode);

            if ($this->option('json')) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT));
                return 0;
            }

            $this->info("Sprint Status Report: {$report['sprint_code']}");
            $this->line("Title: {$report['sprint_title']}");
            $this->line("Status: {$report['sprint_status']}");
            $this->newLine();

            $this->info('Summary:');
            $summary = $report['summary'];
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Tasks', $summary['total_tasks']],
                    ['Completed', $summary['completed']],
                    ['In Progress', $summary['in_progress']],
                    ['Blocked', $summary['blocked']],
                    ['Pending', $summary['pending']],
                    ['Progress', $summary['progress_percentage'] . '%'],
                ]
            );

            $this->newLine();
            $this->info('Tasks:');
            $this->table(
                ['Task Code', 'Title', 'Status', 'Priority'],
                collect($report['tasks'])->map(fn($task) => [
                    $task['task_code'],
                    $task['title'],
                    $task['status'],
                    $task['priority'],
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate status report: {$e->getMessage()}");
            return 1;
        }
    }
}
