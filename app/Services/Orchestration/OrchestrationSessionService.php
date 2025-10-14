<?php

namespace App\Services\Orchestration;

use App\Enums\OrchestrationPhase;
use App\Models\OrchestrationTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Yaml\Yaml;

class OrchestrationSessionService
{
    protected array $workflowConfig;

    public function __construct(
        protected OrchestrationEventService $eventService,
        protected MemoryService $memoryService
    ) {
        $this->loadWorkflowConfig();
    }

    protected function loadWorkflowConfig(): void
    {
        $configPath = resource_path('templates/orchestration/workflow.yaml');
        
        if (!file_exists($configPath)) {
            throw new \RuntimeException("Workflow config not found: {$configPath}");
        }

        $this->workflowConfig = Yaml::parseFile($configPath);
    }

    public function startSession(string $taskCode, ?string $sessionKey = null): SessionState
    {
        $task = OrchestrationTask::where('task_code', $taskCode)->firstOrFail();
        
        $sessionKey = $sessionKey ?? $this->generateSessionKey($taskCode);
        
        $existingSession = $this->getSessionMetadata($task);
        if ($existingSession && ($existingSession['active'] ?? false)) {
            return new SessionState(
                task: $task,
                currentPhase: OrchestrationPhase::from($existingSession['current_phase']),
                sessionKey: $sessionKey,
                startedAt: $existingSession['started_at'],
                active: true,
                resuming: true
            );
        }

        $metadata = $task->metadata ?? [];
        $metadata['session'] = [
            'session_key' => $sessionKey,
            'started_at' => now()->toIso8601String(),
            'current_phase' => OrchestrationPhase::INTAKE->value,
            'active' => true,
            'phase_history' => [],
        ];
        
        $task->update(['metadata' => $metadata]);

        $this->eventService->emit(
            'orchestration.session.start',
            $task,
            ['session_key' => $sessionKey],
            $sessionKey
        );

        Log::info('Orchestration session started', [
            'task_code' => $taskCode,
            'session_key' => $sessionKey,
        ]);

        return new SessionState(
            task: $task,
            currentPhase: OrchestrationPhase::INTAKE,
            sessionKey: $sessionKey,
            startedAt: now()->toIso8601String(),
            active: true,
            resuming: false
        );
    }

    public function getCurrentPhase(string $taskCode): ?OrchestrationPhase
    {
        $task = OrchestrationTask::where('task_code', $taskCode)->first();
        
        if (!$task) {
            return null;
        }

        $session = $this->getSessionMetadata($task);
        
        if (!$session || !($session['active'] ?? false)) {
            return null;
        }

        return OrchestrationPhase::from($session['current_phase']);
    }

    public function completePhase(string $taskCode, ?bool $userOverride = false): NextStepInstructions
    {
        $task = OrchestrationTask::where('task_code', $taskCode)->firstOrFail();
        $currentPhase = $this->getCurrentPhase($taskCode);

        if (!$currentPhase) {
            throw new \RuntimeException("No active session for task: {$taskCode}");
        }

        $session = $this->getSessionMetadata($task);
        $sessionKey = $session['session_key'] ?? null;

        $phaseConfig = $this->workflowConfig['phases'][$currentPhase->value] ?? [];
        
        if (!$userOverride) {
            $validation = $this->validatePhaseCompletion($task, $currentPhase, $phaseConfig);
            
            if (!$validation['passed']) {
                throw new PhaseValidationException(
                    "Phase {$currentPhase->value} cannot be completed: " . 
                    implode(', ', $validation['errors']),
                    $validation
                );
            }

            if (!empty($validation['warnings'])) {
                Log::warning('Phase completion warnings', [
                    'task_code' => $taskCode,
                    'phase' => $currentPhase->value,
                    'warnings' => $validation['warnings'],
                ]);
            }
        } else {
            $this->logUserOverride($task, $currentPhase, $sessionKey);
        }

        $this->eventService->emit(
            "orchestration.phase.{$currentPhase->value}.end",
            $task,
            ['phase' => $currentPhase->value, 'user_override' => $userOverride],
            $sessionKey
        );

        $nextPhase = $currentPhase->next();
        
        if ($nextPhase) {
            return $this->transitionToPhase($task, $nextPhase, $sessionKey);
        }

        return $this->prepareCloseInstructions($task);
    }

    public function transitionToPhase(
        OrchestrationTask $task, 
        OrchestrationPhase $toPhase, 
        ?string $sessionKey = null
    ): NextStepInstructions {
        $currentPhase = $this->getCurrentPhase($task->task_code);

        if ($currentPhase && !$currentPhase->canTransitionTo($toPhase)) {
            throw new \RuntimeException(
                "Invalid phase transition from {$currentPhase->value} to {$toPhase->value}"
            );
        }

        $metadata = $task->metadata ?? [];
        $session = $metadata['session'] ?? [];
        
        $session['phase_history'][] = [
            'phase' => $currentPhase?->value,
            'completed_at' => now()->toIso8601String(),
        ];
        
        $session['current_phase'] = $toPhase->value;
        $metadata['session'] = $session;
        
        $task->update(['metadata' => $metadata]);

        $this->eventService->emit(
            "orchestration.phase.{$toPhase->value}.start",
            $task,
            [
                'phase' => $toPhase->value,
                'from_phase' => $currentPhase?->value,
            ],
            $sessionKey ?? $session['session_key'] ?? null
        );

        Log::info('Phase transition', [
            'task_code' => $task->task_code,
            'from' => $currentPhase?->value,
            'to' => $toPhase->value,
        ]);

        return $this->getNextStepInstructions($task, $toPhase);
    }

    public function endSession(string $taskCode): void
    {
        $task = OrchestrationTask::where('task_code', $taskCode)->firstOrFail();
        $session = $this->getSessionMetadata($task);
        
        if (!$session || !($session['active'] ?? false)) {
            throw new \RuntimeException("No active session to end for task: {$taskCode}");
        }

        $sessionKey = $session['session_key'] ?? null;
        
        $metadata = $task->metadata ?? [];
        $metadata['session']['active'] = false;
        $metadata['session']['ended_at'] = now()->toIso8601String();
        
        $task->update(['metadata' => $metadata]);

        $this->memoryService->compactToPostop($task->id);

        $this->eventService->emit(
            'orchestration.session.end',
            $task,
            [
                'session_key' => $sessionKey,
                'duration_seconds' => $this->calculateSessionDuration($session),
            ],
            $sessionKey
        );

        Log::info('Orchestration session ended', [
            'task_code' => $taskCode,
            'session_key' => $sessionKey,
        ]);
    }

    public function isActiveSession(string $taskCode): bool
    {
        $task = OrchestrationTask::where('task_code', $taskCode)->first();
        
        if (!$task) {
            return false;
        }

        $session = $this->getSessionMetadata($task);
        
        return $session && ($session['active'] ?? false);
    }

    public function promptAppendOrContinue(string $taskCode): array
    {
        $task = OrchestrationTask::where('task_code', $taskCode)->firstOrFail();
        $session = $this->getSessionMetadata($task);
        $currentPhase = $this->getCurrentPhase($taskCode);

        return [
            'message' => "Task {$taskCode} has an active session in {$currentPhase->label()} phase.",
            'options' => [
                'continue' => "Continue from {$currentPhase->label()} phase",
                'restart' => 'Restart session from Intake',
                'new_session' => 'Start a new session (will close current)',
            ],
            'current_phase' => $currentPhase->value,
            'session_started' => $session['started_at'] ?? null,
        ];
    }

    protected function validatePhaseCompletion(
        OrchestrationTask $task, 
        OrchestrationPhase $phase, 
        array $phaseConfig
    ): array {
        $errors = [];
        $warnings = [];
        
        $validation = $phaseConfig['validation'] ?? [];
        
        if (!empty($validation['required_fields'])) {
            foreach ($validation['required_fields'] as $field) {
                $value = data_get($task->metadata, $field);
                if (empty($value)) {
                    $errors[] = "Required field missing: {$field}";
                }
            }
        }

        if (!empty($validation['warn_if_missing'])) {
            foreach ($validation['warn_if_missing'] as $field) {
                $value = data_get($task->metadata, $field);
                if (empty($value)) {
                    $warnings[] = "Recommended field missing: {$field}";
                }
            }
        }

        if (!empty($phaseConfig['artifacts']['required'])) {
            foreach ($phaseConfig['artifacts']['required'] as $artifact) {
                $artifactName = is_array($artifact) ? $artifact['name'] : $artifact;
                
                if (!$this->artifactExists($task, $artifactName)) {
                    $errors[] = "Required artifact missing: {$artifactName}";
                }
            }
        }

        return [
            'passed' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function artifactExists(OrchestrationTask $task, string $artifactName): bool
    {
        $artifacts = $task->metadata['artifacts'] ?? [];
        
        return isset($artifacts[$artifactName]) && !empty($artifacts[$artifactName]);
    }

    public function getNextStepInstructions(
        OrchestrationTask $task, 
        OrchestrationPhase $phase
    ): NextStepInstructions {
        $phaseConfig = $this->workflowConfig['phases'][$phase->value] ?? [];
        
        $requiredArtifacts = array_map(
            fn($a) => is_array($a) ? $a['name'] : $a,
            $phaseConfig['artifacts']['required'] ?? []
        );

        $optionalArtifacts = array_map(
            fn($a) => is_array($a) ? $a['name'] : $a,
            $phaseConfig['artifacts']['optional'] ?? []
        );

        return new NextStepInstructions(
            phase: $phase,
            goal: $phaseConfig['goal'] ?? '',
            description: $phaseConfig['description'] ?? '',
            requiredArtifacts: $requiredArtifacts,
            optionalArtifacts: $optionalArtifacts,
            nextStepText: $phaseConfig['next_step'] ?? '',
            completionCommand: "orchestration:phase-complete {$phase->value}",
            nextPhase: $phase->next()
        );
    }

    protected function prepareCloseInstructions(OrchestrationTask $task): NextStepInstructions
    {
        return new NextStepInstructions(
            phase: OrchestrationPhase::CLOSE,
            goal: 'Finalize and close task',
            description: 'All phases complete. Ready to close session.',
            requiredArtifacts: [],
            optionalArtifacts: [],
            nextStepText: 'Run: orchestration:session-end ' . $task->task_code,
            completionCommand: 'orchestration:session-end ' . $task->task_code,
            nextPhase: null
        );
    }

    protected function logUserOverride(
        OrchestrationTask $task, 
        OrchestrationPhase $phase, 
        ?string $sessionKey
    ): void {
        $metadata = $task->metadata ?? [];
        $metadata['phase_overrides'][] = [
            'phase' => $phase->value,
            'overridden_at' => now()->toIso8601String(),
            'reason' => 'User requested to proceed despite validation warnings/errors',
        ];
        
        $task->update(['metadata' => $metadata]);

        $this->eventService->emit(
            'orchestration.phase.override',
            $task,
            ['phase' => $phase->value],
            $sessionKey
        );

        Log::warning('Phase validation overridden by user', [
            'task_code' => $task->task_code,
            'phase' => $phase->value,
        ]);
    }

    protected function getSessionMetadata(OrchestrationTask $task): ?array
    {
        return $task->metadata['session'] ?? null;
    }

    protected function calculateSessionDuration(array $session): int
    {
        $startedAt = $session['started_at'] ?? now()->toIso8601String();
        $start = \Carbon\Carbon::parse($startedAt);
        
        return now()->diffInSeconds($start);
    }

    protected function generateSessionKey(string $taskCode): string
    {
        return 'sess_' . $taskCode . '_' . now()->format('Ymd_His');
    }

    public function getWorkflowConfig(): array
    {
        return $this->workflowConfig;
    }
}

class SessionState
{
    public function __construct(
        public OrchestrationTask $task,
        public OrchestrationPhase $currentPhase,
        public string $sessionKey,
        public string $startedAt,
        public bool $active,
        public bool $resuming = false
    ) {}

    public function toArray(): array
    {
        return [
            'task_code' => $this->task->task_code,
            'current_phase' => $this->currentPhase->value,
            'session_key' => $this->sessionKey,
            'started_at' => $this->startedAt,
            'active' => $this->active,
            'resuming' => $this->resuming,
        ];
    }
}

class NextStepInstructions
{
    public function __construct(
        public OrchestrationPhase $phase,
        public string $goal,
        public string $description,
        public array $requiredArtifacts,
        public array $optionalArtifacts,
        public string $nextStepText,
        public string $completionCommand,
        public ?OrchestrationPhase $nextPhase
    ) {}

    public function toArray(): array
    {
        return [
            'current_phase' => $this->phase->value,
            'goal' => $this->goal,
            'description' => trim($this->description),
            'required_artifacts' => $this->requiredArtifacts,
            'optional_artifacts' => $this->optionalArtifacts,
            'next_step' => trim($this->nextStepText),
            'completion_command' => $this->completionCommand,
            'next_phase' => $this->nextPhase?->value,
        ];
    }

    public function __toString(): string
    {
        $output = "\n";
        $output .= "═══════════════════════════════════════════════════════════════\n";
        $output .= " PHASE: {$this->phase->label()}\n";
        $output .= "═══════════════════════════════════════════════════════════════\n\n";
        $output .= "Goal: {$this->goal}\n\n";
        
        if (!empty($this->requiredArtifacts)) {
            $output .= "Required Artifacts:\n";
            foreach ($this->requiredArtifacts as $artifact) {
                $output .= "  • {$artifact}\n";
            }
            $output .= "\n";
        }

        if (!empty($this->optionalArtifacts)) {
            $output .= "Optional Artifacts:\n";
            foreach ($this->optionalArtifacts as $artifact) {
                $output .= "  • {$artifact}\n";
            }
            $output .= "\n";
        }

        $output .= "───────────────────────────────────────────────────────────────\n";
        $output .= "Next Steps:\n";
        $output .= "───────────────────────────────────────────────────────────────\n\n";
        $output .= trim($this->nextStepText) . "\n\n";
        
        return $output;
    }
}

class PhaseValidationException extends \Exception
{
    public function __construct(
        string $message,
        public array $validation
    ) {
        parent::__construct($message);
    }
}
