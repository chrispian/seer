<?php

namespace App\Console\Commands;

use App\Services\Orchestration\OrchestrationSessionService;
use Illuminate\Console\Command;

class OrchestrationSessionEnd extends Command
{
    protected $signature = 'orchestration:session-end 
                            {task_code : The task code to end session for}
                            {--force : Force end session even if not in CLOSE phase}';

    protected $description = 'End an active orchestration session and finalize task';

    public function handle(OrchestrationSessionService $sessionService): int
    {
        $taskCode = $this->argument('task_code');
        $force = $this->option('force');

        try {
            if (!$sessionService->isActiveSession($taskCode)) {
                $this->error("No active session found for task: {$taskCode}");
                return 1;
            }

            $currentPhase = $sessionService->getCurrentPhase($taskCode);

            if (!$force && $currentPhase->value !== 'close') {
                $this->error("Cannot end session: Current phase is {$currentPhase->label()}");
                $this->line("Complete all phases first, or use --force to end anyway");
                $this->newLine();
                $this->line("To complete current phase: orchestration:phase-complete {$taskCode}");
                return 1;
            }

            if ($force && $currentPhase->value !== 'close') {
                $this->warn("Force-ending session from {$currentPhase->label()} phase");
                
                if (!$this->confirm('Are you sure?')) {
                    $this->info('Cancelled');
                    return 0;
                }
            }

            $sessionService->endSession($taskCode);

            $this->newLine();
            $this->info("✓ Session ended for task: {$taskCode}");
            $this->line("  All artifacts have been finalized");
            $this->line("  Ephemeral memory compacted to postop");
            $this->newLine();
            $this->line("<fg=green>Task complete!</>");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to end session: {$e->getMessage()}");
            return 1;
        }
    }
}
