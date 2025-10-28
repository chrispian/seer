<?php

namespace App\Console\Commands\Orchestration;

use App\Commands\Orchestration\Task\AssignCommand;
use Illuminate\Console\Command;

class OrchestrationTaskAssignCommand extends Command
{
    protected $signature = 'orchestration:task:assign
        {task : Task UUID or delegation code}
        {agent : Agent UUID or slug}
        {--status=assigned : Delegation status to set}
        {--note= : Optional note stored with assignment}
        {--context= : JSON context payload to store}
        {--assignments-limit=10 : Assignments to show in summary}
        {--json : Output JSON instead of console tables}';

    protected $description = 'Assign a work item to an agent and update its delegation status.';

    public function handle(): int
    {
        $context = $this->decodeContext($this->option('context'));

        $command = new AssignCommand([
            'task_code' => $this->argument('task'),
            'agent_slug' => $this->argument('agent'),
            'status' => (string) $this->option('status'),
            'note' => $this->option('note'),
            'context' => $context,
        ]);

        $command->setContext('cli');
        $result = $command->handle();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $data = $result['data'];
        $this->info(sprintf('Task %s assigned to agent', $data['task_code'] ?? 'n/a'));

        $this->table(['Task', 'Delegation Status', 'Agent'], [[
            $data['task_code'] ?? '—',
            $data['delegation_status'] ?? '—',
            $data['delegation_context']['assigned_agent']['name'] ?? $data['delegation_context']['agent_recommendation'] ?? '—',
        ]]);

        return self::SUCCESS;
    }

    private function decodeContext(?string $json): ?array
    {
        if (! $json) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
