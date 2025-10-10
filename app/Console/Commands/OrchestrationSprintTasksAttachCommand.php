<?php

namespace App\Console\Commands;

use App\Commands\Orchestration\Sprint\AttachTasksCommand;
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

    public function handle(): int
    {
        $tasks = $this->argument('task');

        if (empty($tasks)) {
            $this->error('At least one task must be provided.');
            return self::FAILURE;
        }

        try {
            $command = new AttachTasksCommand([
                'sprint_code' => $this->argument('sprint'),
                'task_codes' => $tasks,
                'include_tasks' => $this->option('include-tasks') ?: false,
                'include_assignments' => $this->option('include-assignments') ?: false,
                'tasks_limit' => (int) $this->option('tasks-limit'),
            ]);

            $command->setContext('cli');
            $result = $command->handle();

            if ($this->option('json')) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return self::SUCCESS;
            }

            $data = $result['data'];
            $this->info(sprintf('Attached %d task(s) to sprint: %s',
                $data['attached_count'],
                $data['sprint']['code'] ?? $data['sprint']['id']
            ));

            $this->comment(sprintf('Tasks attached: %s', implode(', ', $data['task_codes'])));

            // Show sprint summary
            if (isset($data['sprint']['stats'])) {
                $stats = $data['sprint']['stats'];
                $this->info('Sprint Summary:');
                $this->table(['Total Tasks', 'Completed', 'In Progress', 'Blocked', 'Unassigned'], [[
                    $stats['total'] ?? 0,
                    $stats['completed'] ?? 0,
                    $stats['in_progress'] ?? 0,
                    $stats['blocked'] ?? 0,
                    $stats['unassigned'] ?? 0,
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
