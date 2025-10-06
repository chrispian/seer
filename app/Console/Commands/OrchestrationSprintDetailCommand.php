<?php

namespace App\Console\Commands;

use App\Services\SprintOrchestrationService;
use Illuminate\Console\Command;

class OrchestrationSprintDetailCommand extends Command
{
    protected $signature = 'orchestration:sprint:detail
        {sprint : Sprint code or UUID}
        {--tasks-limit=10 : Number of tasks to display}
        {--with-assignments : Include assignment summaries for tasks}
        {--json : Output JSON instead of console tables}';

    protected $description = 'Show a sprint summary with stats and recent tasks.';

    public function handle(SprintOrchestrationService $service): int
    {
        $sprint = $this->argument('sprint');
        $tasksLimit = (int) $this->option('tasks-limit');
        $includeAssignments = (bool) $this->option('with-assignments');

        $detail = $service->detail($sprint, [
            'tasks_limit' => $tasksLimit,
            'include_tasks' => true,
            'include_assignments' => $includeAssignments,
        ]);

        $payload = $detail['sprint'];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->table(['Field', 'Value'], [
            ['Code', $payload['code']],
            ['Title', $payload['title']],
            ['Status', $payload['status'] ?? '—'],
            ['Priority', $payload['priority'] ?? '—'],
            ['Estimate', $payload['estimate'] ?? '—'],
            ['Notes', implode('; ', $payload['notes'] ?? []) ?: '—'],
        ]);

        $this->info('Stats');
        $stats = $payload['stats'];
        $this->table([
            'Total', 'Completed', 'In Progress', 'Blocked', 'Unassigned',
        ], [[
            $stats['total'],
            $stats['completed'],
            $stats['in_progress'],
            $stats['blocked'],
            $stats['unassigned'],
        ]]);

        if (! empty($payload['tasks'])) {
            $this->info('Tasks');
            $this->table(
                ['Task', 'Delegation', 'Status', 'Agent', 'Updated'],
                collect($payload['tasks'])->map(function ($task) {
                    return [
                        $task['task_code'] ?? '—',
                        $task['delegation_status'] ?? '—',
                        $task['status'] ?? '—',
                        $task['current_agent']['name'] ?? $task['agent_recommendation'] ?? '—',
                        $task['updated_at'] ?? '—',
                    ];
                })->toArray()
            );
        }

        return self::SUCCESS;
    }
}
