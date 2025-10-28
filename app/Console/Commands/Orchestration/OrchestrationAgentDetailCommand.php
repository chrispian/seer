<?php

namespace App\Console\Commands\Orchestration;

use App\Services\AgentOrchestrationService;
use Illuminate\Console\Command;

class OrchestrationAgentDetailCommand extends Command
{
    protected $signature = 'orchestration:agent:detail
        {agent : Agent slug, UUID, or name}
        {--assignments-limit=10 : Recent assignments to include}
        {--json : Output JSON instead of tables}';

    protected $description = 'Display agent profile details, stats, and recent assignments.';

    public function handle(AgentOrchestrationService $service): int
    {
        $agent = $this->argument('agent');
        $limit = (int) $this->option('assignments-limit');

        $detail = $service->detail($agent, [
            'assignments_limit' => $limit,
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($detail, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $profile = $detail['agent'];
        $this->table(['Field', 'Value'], [
            ['Name', $profile['name'] ?? '—'],
            ['Slug', $profile['slug'] ?? '—'],
            ['Type', $profile['type'] ?? '—'],
            ['Mode', $profile['mode'] ?? '—'],
            ['Status', $profile['status'] ?? '—'],
        ]);

        $stats = $detail['stats'];
        $this->info('Assignment Stats');
        $this->table(['Total', 'Active', 'Completed'], [[
            $stats['assignments_total'] ?? 0,
            $stats['assignments_active'] ?? 0,
            $stats['assignments_completed'] ?? 0,
        ]]);

        if (! empty($detail['recent_assignments'])) {
            $this->info('Recent Assignments');
            $this->table(
                ['Task', 'Status', 'Assigned', 'Completed'],
                collect($detail['recent_assignments'])->map(function ($assignment) {
                    return [
                        $assignment['work_item_code'] ?? $assignment['work_item_id'] ?? '—',
                        $assignment['status'] ?? '—',
                        $assignment['assigned_at'] ?? '—',
                        $assignment['completed_at'] ?? '—',
                    ];
                })->toArray()
            );
        }

        return self::SUCCESS;
    }
}
