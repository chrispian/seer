<?php

namespace App\Console\Commands;

use App\Commands\Orchestration\Task\UpdateStatusCommand;
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

    public function handle(): int
    {
        $command = new UpdateStatusCommand([
            'task_code' => $this->argument('task'),
            'delegation_status' => $this->argument('status'),
            'note' => $this->option('note'),
        ]);

        $command->setContext('cli');
        $result = $command->handle();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $data = $result['data'];
        $this->info(sprintf('Task %s now %s', $data['task_code'] ?? 'n/a', $data['delegation_status'] ?? 'n/a'));

        if ($this->option('note')) {
            $this->line('Note: '.$this->option('note'));
        }

        return self::SUCCESS;
    }
}
