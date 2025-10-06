<?php

namespace App\Console\Commands;

use App\Enums\AgentStatus;
use App\Services\AgentOrchestrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OrchestrationAgentStatusCommand extends Command
{
    protected $signature = 'orchestration:agent:status
        {agent : Agent slug, UUID, or name}
        {status : Agent status (active|inactive|archived)}
        {--note= : Optional note for status change}
        {--json : Output JSON instead of tables}';

    protected $description = 'Update agent status and record change in audit trail.';

    public function handle(AgentOrchestrationService $service): int
    {
        $agent = $this->argument('agent');
        $status = $this->argument('status');
        $note = $this->option('note');

        // Validate status enum
        if (! AgentStatus::tryFrom(Str::lower($status))) {
            $this->error(sprintf('Unknown agent status [%s]. Valid options: %s', 
                $status, 
                implode(', ', array_map(fn($case) => $case->value, AgentStatus::cases()))
            ));

            return self::FAILURE;
        }

        try {
            $updatedAgent = $service->setStatus($agent, $status);
            
            // Get detailed information for output
            $detail = $service->detail($updatedAgent, [
                'assignments_limit' => 5,
            ]);

            if ($this->option('json')) {
                $this->line(json_encode([
                    'success' => true,
                    'agent' => $detail['agent'],
                    'previous_status' => $detail['agent']['status'] ?? null,
                    'new_status' => $status,
                    'note' => $note,
                ], JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            $agentName = $detail['agent']['name'] ?? $detail['agent']['slug'] ?? 'Unknown';
            $this->info(sprintf('Agent "%s" status updated to: %s', $agentName, $status));
            
            if ($note) {
                $this->comment("Note: {$note}");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            if ($this->option('json')) {
                $this->line(json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error('Failed to update agent status: ' . $e->getMessage());
            }

            return self::FAILURE;
        }
    }
}