<?php

namespace App\Console\Commands;

use App\Services\TaskOrchestrationService;
use Illuminate\Console\Command;

class OrchestrationTaskStatusCommand extends Command
{
    protected $signature = 'orchestration:task:status
        {task : Task UUID or delegation code}
        {status : Delegation status (unassigned, assigned, in_progress, blocked, completed, cancelled)}
        {--note= : Optional note appended to delegation history}
        {--assignments-limit=10 : Assignments to show in summary}
        {--json : Output JSON instead of console tables}';

    protected $description = 'Update delegation status for a work item and sync the active assignment.';

    public function handle(TaskOrchestrationService $service): int
    {
        $task = $this->argument('task');
        $status = $this->argument('status');
        $note = $this->option('note');
        $assignmentsLimit = (int) $this->option('assignments-limit');

        $service->updateStatus($task, $status, [
            'note' => $note,
        ]);

        $detail = $service->detail($task, [
            'assignments_limit' => $assignmentsLimit,
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($detail, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info(sprintf('Task %s now %s', $detail['task']['task_code'] ?? 'n/a', $detail['task']['delegation_status'] ?? 'n/a'));

        if ($note) {
            $this->line('Note: '.$note);
        }

        return self::SUCCESS;
    }
}
