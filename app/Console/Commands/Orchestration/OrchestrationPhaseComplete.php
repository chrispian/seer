<?php

namespace App\Console\Commands\Orchestration;

use App\Services\Orchestration\OrchestrationSessionService;
use App\Services\Orchestration\PhaseValidationException;
use Illuminate\Console\Command;

class OrchestrationPhaseComplete extends Command
{
    protected $signature = 'orchestration:phase-complete
                            {task_code : The task code to complete phase for}
                            {--override : Override validation and force phase completion}';

    protected $description = 'Complete the current phase and transition to next phase';

    public function handle(OrchestrationSessionService $sessionService): int
    {
        $taskCode = $this->argument('task_code');
        $userOverride = $this->option('override');

        try {
            if (!$sessionService->isActiveSession($taskCode)) {
                $this->error("No active session found for task: {$taskCode}");
                $this->line("Start a session first: orchestration:session-start {$taskCode}");
                return 1;
            }

            $currentPhase = $sessionService->getCurrentPhase($taskCode);
            $this->info("Completing phase: {$currentPhase->label()}");

            try {
                $nextStep = $sessionService->completePhase($taskCode, $userOverride);
            } catch (PhaseValidationException $e) {
                $this->error("Phase validation failed:");

                foreach ($e->validation['errors'] as $error) {
                    $this->line("  ✗ {$error}");
                }

                if (!empty($e->validation['warnings'])) {
                    $this->newLine();
                    $this->warn("Warnings:");
                    foreach ($e->validation['warnings'] as $warning) {
                        $this->line("  ⚠ {$warning}");
                    }
                }

                $this->newLine();
                $this->line("To proceed anyway, use: --override");

                return 1;
            }

            $this->newLine();
            $this->line("<fg=green>✓</> Phase {$currentPhase->label()} completed successfully");
            $this->newLine();

            $this->line((string) $nextStep);

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to complete phase: {$e->getMessage()}");
            return 1;
        }
    }
}
