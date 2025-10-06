<?php

namespace App\Console\Commands;

use App\Services\TaskOrchestrationService;
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

    public function handle(TaskOrchestrationService $service): int
    {
        $task = $this->argument('task');
        $agent = $this->argument('agent');
        $status = (string) $this->option('status');
        $note = $this->option('note');
        $context = $this->decodeContext($this->option('context'));
        $assignmentsLimit = (int) $this->option('assignments-limit');

        $service->assignAgent($task, $agent, [
            'status' => $status,
            'note' => $note,
            'context' => $context,
        ]);

        $detail = $service->detail($task, [
            'assignments_limit' => $assignmentsLimit,
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($detail, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info(sprintf('Task %s assigned to %s (%s)', $detail['task']['task_code'] ?? 'n/a', $detail['current_assignment']['agent_name'] ?? 'n/a', $detail['current_assignment']['status'] ?? 'n/a'));

        $this->table(['Task', 'Delegation', 'Status', 'Agent'], [[
            $detail['task']['task_code'] ?? '—',
            $detail['task']['delegation_status'] ?? '—',
            $detail['task']['status'] ?? '—',
            $detail['current_assignment']['agent_name'] ?? $detail['current_assignment']['agent_slug'] ?? '—',
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
