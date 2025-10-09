<?php

namespace App\Services\Orchestration;

use App\Models\AgentProfile;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AgentInitService
{
    protected MemoryService $memory;

    public function __construct(MemoryService $memory)
    {
        $this->memory = $memory;
    }

    public function initialize(string $agentId, string $taskId, array $contextPack = []): InitPhaseResult
    {
        $agent = AgentProfile::findOrFail($agentId);
        $task = WorkItem::findOrFail($taskId);

        $result = new InitPhaseResult($agent, $task);

        Log::info('Agent INIT: Starting initialization', [
            'agent' => $agent->slug,
            'task' => $task->metadata['task_code'] ?? $task->id,
        ]);

        // Step 1: Resume Memory
        $this->initResumeMemory($taskId, $result);

        // Step 2: Load Profile
        $this->initLoadProfile($agent, $result);

        // Step 3: Healthcheck
        $this->initHealthcheck($result);

        // Step 4: Plan Confirm
        if (! empty($contextPack)) {
            $this->initPlanConfirm($taskId, $contextPack, $result);
        }

        $result->complete();

        Log::info('Agent INIT: Initialization complete', [
            'agent' => $agent->slug,
            'task' => $task->metadata['task_code'] ?? $task->id,
            'checks_passed' => $result->getPassedCount(),
            'checks_failed' => $result->getFailedCount(),
            'warnings' => count($result->getWarnings()),
        ]);

        return $result;
    }

    protected function initResumeMemory(string $taskId, InitPhaseResult $result): void
    {
        $boot = $this->memory->getBoot($taskId);
        $notes = $this->memory->getNotes($taskId);
        $postop = $this->memory->getPostop($taskId);

        if ($boot || $notes || $postop) {
            $result->addCheck('init.resume_memory', true, 'Durable memory loaded', [
                'has_boot' => $boot !== null,
                'has_notes' => $notes !== null,
                'has_postop' => $postop !== null,
            ]);

            $result->memory = [
                'boot' => $boot,
                'notes' => $notes,
                'postop' => $postop,
            ];
        } else {
            $result->addCheck('init.resume_memory', true, 'No prior memory (first run)', []);
            $result->memory = null;
        }
    }

    protected function initLoadProfile(AgentProfile $agent, InitPhaseResult $result): void
    {
        $profile = [
            'id' => $agent->id,
            'name' => $agent->name,
            'slug' => $agent->slug,
            'type' => $agent->type?->value,
            'mode' => $agent->mode?->value,
            'status' => $agent->status?->value,
            'capabilities' => $agent->capabilities,
            'constraints' => $agent->constraints,
            'tools' => $agent->tools,
            'metadata' => $agent->metadata,
        ];

        $orchestrationSettings = config('orchestration');

        $result->addCheck('init.load_profile', true, 'Agent profile and settings loaded', [
            'agent_type' => $agent->type?->value,
            'tools_count' => count($agent->tools ?? []),
            'has_constraints' => ! empty($agent->constraints),
        ]);

        $result->profile = $profile;
        $result->settings = $orchestrationSettings;
    }

    protected function initHealthcheck(InitPhaseResult $result): void
    {
        $checks = [];

        // Check artifacts disk
        try {
            $disk = config('orchestration.artifacts.disk', 'local');
            Storage::disk($disk)->exists('.');
            $checks['artifacts_disk'] = true;
        } catch (\Exception $e) {
            $checks['artifacts_disk'] = false;
            $result->addWarning('Artifacts disk not accessible: '.$e->getMessage());
        }

        // Check cache (for memory)
        try {
            cache()->get('_healthcheck');
            $checks['cache'] = true;
        } catch (\Exception $e) {
            $checks['cache'] = false;
            $result->addWarning('Cache not accessible: '.$e->getMessage());
        }

        // Check database
        try {
            \DB::connection()->getPdo();
            $checks['database'] = true;
        } catch (\Exception $e) {
            $checks['database'] = false;
            $result->addWarning('Database not accessible: '.$e->getMessage());
        }

        $allPassed = ! in_array(false, $checks, true);

        $result->addCheck('init.healthcheck', $allPassed,
            $allPassed ? 'All systems healthy' : 'Some systems unhealthy',
            $checks
        );
    }

    protected function initPlanConfirm(string $taskId, array $contextPack, InitPhaseResult $result): void
    {
        $existingPlan = $this->memory->getDurable($taskId, 'intent_plan');

        if (! $existingPlan) {
            $result->addCheck('init.plan_confirm', true, 'No existing plan, accepting context pack', [
                'context_pack_type' => $contextPack['type'] ?? 'unknown',
            ]);

            // Store context pack as initial plan
            $this->memory->setDurable($taskId, 'intent_plan', $contextPack);

            $result->plan = $contextPack;

            return;
        }

        // Compare plans
        $delta = $this->comparePlans($existingPlan, $contextPack);

        if (empty($delta)) {
            $result->addCheck('init.plan_confirm', true, 'Plans match, no delta', []);
        } else {
            $result->addCheck('init.plan_confirm', true, 'Plan delta detected', [
                'changes' => count($delta),
            ]);
            $result->addWarning('Context pack differs from existing plan. Delta: '.json_encode($delta));
        }

        $result->plan = $contextPack;
        $result->planDelta = $delta;
    }

    protected function comparePlans(array $existing, array $incoming): array
    {
        $delta = [];

        foreach ($incoming as $key => $value) {
            if (! isset($existing[$key])) {
                $delta[$key] = ['added' => $value];
            } elseif ($existing[$key] !== $value) {
                $delta[$key] = ['old' => $existing[$key], 'new' => $value];
            }
        }

        foreach ($existing as $key => $value) {
            if (! isset($incoming[$key])) {
                $delta[$key] = ['removed' => $value];
            }
        }

        return $delta;
    }
}

class InitPhaseResult
{
    public AgentProfile $agent;

    public WorkItem $task;

    public array $checks = [];

    public array $warnings = [];

    public ?array $memory = null;

    public ?array $profile = null;

    public ?array $settings = null;

    public ?array $plan = null;

    public ?array $planDelta = null;

    public bool $completed = false;

    public ?string $completedAt = null;

    public function __construct(AgentProfile $agent, WorkItem $task)
    {
        $this->agent = $agent;
        $this->task = $task;
    }

    public function addCheck(string $step, bool $passed, string $message, array $details = []): void
    {
        $this->checks[] = [
            'step' => $step,
            'passed' => $passed,
            'message' => $message,
            'details' => $details,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = [
            'message' => $warning,
            'at' => now()->toIso8601String(),
        ];
    }

    public function complete(): void
    {
        $this->completed = true;
        $this->completedAt = now()->toIso8601String();
    }

    public function getPassedCount(): int
    {
        return count(array_filter($this->checks, fn ($c) => $c['passed']));
    }

    public function getFailedCount(): int
    {
        return count(array_filter($this->checks, fn ($c) => ! $c['passed']));
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function toArray(): array
    {
        return [
            'agent' => [
                'id' => $this->agent->id,
                'name' => $this->agent->name,
                'slug' => $this->agent->slug,
            ],
            'task' => [
                'id' => $this->task->id,
                'task_code' => $this->task->metadata['task_code'] ?? null,
            ],
            'checks' => $this->checks,
            'warnings' => $this->warnings,
            'memory' => $this->memory,
            'profile' => $this->profile,
            'settings' => $this->settings,
            'plan' => $this->plan,
            'plan_delta' => $this->planDelta,
            'completed' => $this->completed,
            'completed_at' => $this->completedAt,
            'summary' => [
                'checks_passed' => $this->getPassedCount(),
                'checks_failed' => $this->getFailedCount(),
                'warnings_count' => count($this->warnings),
            ],
        ];
    }
}
