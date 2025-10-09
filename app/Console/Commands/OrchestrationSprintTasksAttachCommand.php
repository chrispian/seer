<?php

namespace App\Console\Commands;

use App\Services\SprintOrchestrationService;
use Illuminate\Console\Command;

class OrchestrationSprintTasksAttachCommand extends Command
{
    protected $signature = 'orchestration:sprint:tasks:attach
        {sprint : Sprint code or UUID}
        {task* : Task codes or UUIDs to attach}
        {--include-tasks : Include full task details in output}
        {--include-assignments : Include task assignments in output}
        {--tasks-limit=10 : Limit number of tasks in detailed output}
        {--json : Output JSON instead of tables}';

    protected $description = 'Attach one or more tasks to a sprint backlog.';

    public function handle(SprintOrchestrationService $service): int
    {
        $sprint = $this->argument('sprint');
        $tasks = $this->argument('task');

        if (empty($tasks)) {
            $this->error('At least one task must be provided.');

            return self::FAILURE;
        }

        $options = array_filter([
            'include_tasks' => $this->option('include-tasks') ?: false,
            'include_assignments' => $this->option('include-assignments') ?: false,
            'tasks_limit' => (int) $this->option('tasks-limit'),
        ]);

        try {
            $result = $service->attachTasks($sprint, $tasks, $options);

            if ($this->option('json')) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            $sprintInfo = $result['sprint'];
            $this->info(sprintf('Attached %d task(s) to sprint: %s',
                count($tasks),
                $sprintInfo['code'] ?? $sprintInfo['id']
            ));

            // Display attached tasks
            if (! empty($result['tasks'])) {
                $this->comment('Attached Tasks:');
                $taskRows = [];
                foreach ($result['tasks'] as $task) {
                    $taskRows[] = [
                        $task['code'] ?? $task['id'] ?? '—',
                        $task['title'] ?? '—',
                        $task['delegation_status'] ?? '—',
                    ];
                }
                $this->table(['Code', 'Title', 'Status'], $taskRows);
            } else {
                $this->comment(sprintf('Tasks attached: %s', implode(', ', $tasks)));
            }

            // Show sprint summary
            if (isset($result['stats'])) {
                $stats = $result['stats'];
                $this->info('Sprint Summary:');
                $this->table(['Total Tasks', 'Pending', 'In Progress', 'Completed'], [[
                    $stats['tasks_total'] ?? 0,
                    $stats['tasks_pending'] ?? 0,
                    $stats['tasks_in_progress'] ?? 0,
                    $stats['tasks_completed'] ?? 0,
                ]]);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            if ($this->option('json')) {
                $this->line(json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error('Failed to attach tasks to sprint: '.$e->getMessage());
            }

            return self::FAILURE;
        }
    }
}
