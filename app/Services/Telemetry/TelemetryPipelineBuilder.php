<?php

namespace App\Services\Telemetry;

use App\Decorators\TelemetryPipelineDecorator;
use App\Models\Fragment;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;

class TelemetryPipelineBuilder
{
    protected array $steps = [];

    protected array $globalContext = [];

    protected bool $telemetryEnabled = true;

    protected ?string $pipelineId = null;

    public function __construct()
    {
        $this->telemetryEnabled = config('app.telemetry_enabled', true);
    }

    /**
     * Add a processing step to the pipeline
     */
    public function addStep(string $actionClass, ?string $stepName = null, array $context = []): self
    {
        $this->steps[] = [
            'class' => $actionClass,
            'name' => $stepName ?? class_basename($actionClass),
            'context' => $context,
        ];

        return $this;
    }

    /**
     * Add multiple steps at once
     */
    public function addSteps(array $actionClasses): self
    {
        foreach ($actionClasses as $actionClass) {
            if (is_array($actionClass)) {
                [$class, $name, $context] = array_pad($actionClass, 3, []);
                $this->addStep($class, $name, $context);
            } else {
                $this->addStep($actionClass);
            }
        }

        return $this;
    }

    /**
     * Set global context for all pipeline steps
     */
    public function withContext(array $context): self
    {
        $this->globalContext = array_merge($this->globalContext, $context);

        return $this;
    }

    /**
     * Set pipeline ID for telemetry correlation
     */
    public function withPipelineId(string $pipelineId): self
    {
        $this->pipelineId = $pipelineId;

        return $this;
    }

    /**
     * Enable or disable telemetry for this pipeline
     */
    public function withTelemetry(bool $enabled = true): self
    {
        $this->telemetryEnabled = $enabled;

        return $this;
    }

    /**
     * Build and execute the pipeline on a fragment
     */
    public function process(Fragment $fragment): Fragment
    {
        if (empty($this->steps)) {
            return $fragment;
        }

        $startTime = microtime(true);
        $pipelineId = $this->pipelineId ?? \Illuminate\Support\Str::uuid();

        // Log pipeline start if telemetry is enabled
        if ($this->telemetryEnabled) {
            $stepClasses = array_column($this->steps, 'class');
            FragmentProcessingTelemetry::logPipelineStarted($fragment, $stepClasses);
        }

        try {
            $decoratedSteps = $this->buildDecoratedSteps();

            $result = App::make(Pipeline::class)
                ->send($fragment)
                ->through($decoratedSteps)
                ->thenReturn();

            // Log pipeline completion
            if ($this->telemetryEnabled) {
                $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                FragmentProcessingTelemetry::logPipelineCompleted($pipelineId, $result, $durationMs);
            }

            return $result;

        } catch (\Throwable $e) {
            // Log pipeline failure
            if ($this->telemetryEnabled) {
                $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                FragmentProcessingTelemetry::logPipelineFailed($pipelineId, $fragment, $e, $durationMs);
            }

            throw $e;
        }
    }

    /**
     * Get the pipeline steps as decorated instances
     */
    public function buildDecoratedSteps(): array
    {
        return array_map(function ($stepConfig) {
            $actionInstance = App::make($stepConfig['class']);

            if (! $this->telemetryEnabled) {
                return $actionInstance;
            }

            $context = array_merge($this->globalContext, $stepConfig['context']);

            if ($this->pipelineId) {
                $context['pipeline_id'] = $this->pipelineId;
            }

            return TelemetryPipelineDecorator::wrap(
                $actionInstance,
                $stepConfig['name'],
                $context
            );
        }, $this->steps);
    }

    /**
     * Create a new pipeline builder instance
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Create a standard fragment processing pipeline
     */
    public static function standard(): self
    {
        return self::create()->addSteps([
            \App\Actions\DriftSync::class,
            \App\Actions\ParseAtomicFragment::class,
            \App\Actions\ExtractMetadataEntities::class,
            \App\Actions\GenerateAutoTitle::class,
            \App\Actions\EnrichFragmentWithAI::class,
            \App\Actions\InferFragmentType::class,
            \App\Actions\SuggestTags::class,
            \App\Actions\RouteToVault::class,
            \App\Actions\EmbedFragmentAction::class,
        ]);
    }

    /**
     * Create a lightweight pipeline (no AI processing)
     */
    public static function lightweight(): self
    {
        return self::create()->addSteps([
            \App\Actions\ParseAtomicFragment::class,
            \App\Actions\ExtractMetadataEntities::class,
            \App\Actions\GenerateAutoTitle::class,
            \App\Actions\RouteToVault::class,
        ]);
    }

    /**
     * Create an AI-focused pipeline
     */
    public static function aiEnrichment(): self
    {
        return self::create()->addSteps([
            \App\Actions\ParseAtomicFragment::class,
            \App\Actions\EnrichFragmentWithAI::class,
            \App\Actions\InferFragmentType::class,
            \App\Actions\SuggestTags::class,
        ]);
    }

    /**
     * Execute a single action with telemetry
     */
    public static function executeAction(string $actionClass, Fragment $fragment, array $context = []): Fragment
    {
        return self::create()
            ->addStep($actionClass, null, $context)
            ->process($fragment);
    }

    /**
     * Execute multiple actions in sequence with telemetry
     */
    public static function executeActions(array $actionClasses, Fragment $fragment, array $context = []): Fragment
    {
        return self::create()
            ->withContext($context)
            ->addSteps($actionClasses)
            ->process($fragment);
    }
}
