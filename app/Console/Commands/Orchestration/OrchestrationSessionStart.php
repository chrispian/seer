<?php

namespace App\Console\Commands\Orchestration;

use App\Services\Orchestration\OrchestrationSessionService;
use Illuminate\Console\Command;

class OrchestrationSessionStart extends Command
{
    protected $signature = 'orchestration:session-start
                            {task_code : The task code to start session for}
                            {--session-key= : Optional custom session key}';

    protected $description = 'Start a new orchestration session for a task';

    public function handle(OrchestrationSessionService $sessionService): int
    {
        $taskCode = $this->argument('task_code');
        $sessionKey = $this->option('session-key');

        try {
            if ($sessionService->isActiveSession($taskCode)) {
                $prompt = $sessionService->promptAppendOrContinue($taskCode);

                $this->warn($prompt['message']);
                $this->newLine();

                $choice = $this->choice(
                    'What would you like to do?',
                    array_values($prompt['options']),
                    0
                );

                $actionKey = array_search($choice, $prompt['options']);

                if ($actionKey === 'restart') {
                    $sessionService->endSession($taskCode);
                    $this->info("Previous session closed. Starting new session...");
                } elseif ($actionKey === 'new_session') {
                    $sessionService->endSession($taskCode);
                    $this->info("Previous session closed. Starting new session...");
                } elseif ($actionKey === 'continue') {
                    $currentPhase = $sessionService->getCurrentPhase($taskCode);
                    $nextStep = $sessionService->getNextStepInstructions(
                        \App\Models\OrchestrationTask::where('task_code', $taskCode)->firstOrFail(),
                        $currentPhase
                    );

                    $this->info("Resuming session from {$currentPhase->label()} phase");
                    $this->newLine();
                    $this->line((string) $nextStep);

                    return 0;
                }
            }

            $session = $sessionService->startSession($taskCode, $sessionKey);

            $this->info("✓ Session started for task: {$taskCode}");
            $this->line("  Session Key: {$session->sessionKey}");
            $this->line("  Starting Phase: {$session->currentPhase->label()}");
            $this->newLine();

            $nextStep = $sessionService->getNextStepInstructions(
                $session->task,
                $session->currentPhase
            );

            $this->line((string) $nextStep);

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to start session: {$e->getMessage()}");
            return 1;
        }
    }
}
