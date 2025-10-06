# PIPELINE-001: Fragment Processing Orchestrator

## Agent Profile
**Type**: Senior Engineer/Code Reviewer  
**Specialization**: Pipeline Architecture, Laravel Services, Performance Optimization

## Task Overview
Create a unified `FragmentProcessingOrchestrator` service that routes both user and assistant chat fragments through the shared processing pipeline while maintaining real-time performance requirements.

## Context
Currently, chat fragments bypass the full processing pipeline through `CreateChatFragment`, missing out on classification, tagging, vault routing, and embeddings. Meanwhile, regular fragments go through `ProcessFragmentJob` with comprehensive processing. We need a unified approach that serves both use cases efficiently.

## Technical Requirements

### **Orchestrator Architecture**
```php
class FragmentProcessingOrchestrator
{
    public function processFragment(
        Fragment $fragment, 
        ProcessingMode $mode = ProcessingMode::FULL,
        array $options = []
    ): ProcessingResult;
    
    public function processChatFragment(
        Fragment $fragment,
        bool $isRealTime = true
    ): ProcessingResult;
    
    public function getProcessingPipeline(Fragment $fragment): array;
    public function shouldSkipStep(string $stepClass, Fragment $fragment): bool;
}
```

### **Processing Modes**
```php
enum ProcessingMode: string 
{
    case FULL = 'full';           // Complete pipeline with all steps
    case REALTIME = 'realtime';   // Optimized for chat with inline deterministic steps
    case ASYNC = 'async';         // Background processing only
    case DETERMINISTIC = 'deterministic'; // Skip AI steps entirely
}
```

### **Pipeline Configuration**
```php
// config/fragments.php extension
'processing' => [
    'orchestrator' => [
        'enabled' => env('FRAGMENT_ORCHESTRATOR_ENABLED', true),
        'realtime_timeout_ms' => env('FRAGMENT_REALTIME_TIMEOUT', 300),
        'deterministic_threshold' => env('FRAGMENT_DETERMINISTIC_THRESHOLD', 0.8),
    ],
    
    'steps' => [
        'inline' => [
            // Steps that run immediately for realtime processing
            \App\Actions\DriftSync::class,
            \App\Actions\ParseAtomicFragment::class,
            \App\Actions\ExtractMetadataEntities::class,
            \App\Actions\InferFragmentType::class, // If deterministic
        ],
        
        'async' => [
            // Steps that queue for background processing
            \App\Actions\EnrichFragmentWithAI::class,
            \App\Actions\SuggestTags::class,
            \App\Actions\EmbedFragmentAction::class,
        ],
        
        'conditional' => [
            // Steps with conditional execution logic
            \App\Actions\GenerateAutoTitle::class => [
                'condition' => 'title_missing',
                'mode' => 'async'
            ],
            \App\Actions\InferFragmentType::class => [
                'condition' => 'deterministic_confidence_low',
                'mode' => 'async'
            ],
        ],
    ],
],
```

## Implementation Plan

### **Phase 1: Orchestrator Service Creation**
```php
<?php

namespace App\Services;

use App\Models\Fragment;
use App\Enums\ProcessingMode;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;

class FragmentProcessingOrchestrator
{
    public function __construct(
        private Pipeline $pipeline,
        private ProcessingMetrics $metrics
    ) {}

    public function processFragment(
        Fragment $fragment, 
        ProcessingMode $mode = ProcessingMode::FULL,
        array $options = []
    ): ProcessingResult {
        $startTime = microtime(true);
        
        Log::info('🎭 Orchestrator: Processing fragment', [
            'fragment_id' => $fragment->id,
            'mode' => $mode->value,
            'source' => $fragment->source,
        ]);

        // Determine processing steps based on mode and fragment
        $steps = $this->getProcessingSteps($fragment, $mode);
        
        // Split into inline and async steps
        $inlineSteps = $steps['inline'] ?? [];
        $asyncSteps = $steps['async'] ?? [];

        // Execute inline steps immediately
        $inlineResult = $this->executeInlineSteps($fragment, $inlineSteps);
        
        // Queue async steps if any
        if (!empty($asyncSteps) && $mode !== ProcessingMode::DETERMINISTIC) {
            $this->queueAsyncSteps($fragment, $asyncSteps);
        }

        $duration = (microtime(true) - $startTime) * 1000;
        
        $this->metrics->recordProcessing($fragment, $mode, $duration, $inlineResult);
        
        return new ProcessingResult(
            fragment: $fragment,
            inlineSteps: $inlineResult,
            asyncStepsQueued: count($asyncSteps),
            processingTime: $duration,
            mode: $mode
        );
    }

    public function processChatFragment(
        Fragment $fragment,
        bool $isRealTime = true
    ): ProcessingResult {
        $mode = $isRealTime ? ProcessingMode::REALTIME : ProcessingMode::FULL;
        
        return $this->processFragment($fragment, $mode, [
            'chat_context' => true,
            'priority' => 'high',
        ]);
    }

    private function getProcessingSteps(Fragment $fragment, ProcessingMode $mode): array
    {
        $config = config('fragments.processing.steps');
        
        switch ($mode) {
            case ProcessingMode::REALTIME:
                return [
                    'inline' => $this->filterStepsForRealtime($config['inline'], $fragment),
                    'async' => array_merge($config['async'], $this->getConditionalAsyncSteps($fragment))
                ];
                
            case ProcessingMode::DETERMINISTIC:
                return [
                    'inline' => $this->filterDeterministicSteps($config['inline']),
                    'async' => []
                ];
                
            case ProcessingMode::FULL:
            default:
                return [
                    'inline' => array_merge($config['inline'], $config['async']),
                    'async' => []
                ];
        }
    }

    private function executeInlineSteps(Fragment $fragment, array $steps): StepResults
    {
        $results = new StepResults();
        
        foreach ($steps as $stepClass) {
            $startTime = microtime(true);
            
            try {
                $step = app($stepClass);
                $stepResult = $step($fragment);
                
                $results->addSuccess($stepClass, $stepResult, microtime(true) - $startTime);
                
            } catch (\Exception $e) {
                $results->addError($stepClass, $e, microtime(true) - $startTime);
                
                Log::warning('🎭 Orchestrator: Step failed', [
                    'step' => $stepClass,
                    'fragment_id' => $fragment->id,
                    'error' => $e->getMessage(),
                ]);
                
                // Continue processing other steps
            }
        }
        
        return $results;
    }

    private function queueAsyncSteps(Fragment $fragment, array $steps): void
    {
        foreach ($steps as $stepClass) {
            // Queue individual step jobs for granular processing
            ProcessFragmentStepJob::dispatch($fragment->id, $stepClass)
                ->onQueue('fragments-async')
                ->delay(now()->addSeconds(1)); // Small delay to ensure inline steps complete
        }
    }
}
```

### **Phase 2: Integration with Chat Controllers**
Update `ChatApiController` to use orchestrator:
```php
// In ChatApiController@send
$createChatFragment = app(\App\Actions\CreateChatFragment::class);
$fragment = $createChatFragment($data['content']);

// NEW: Process through orchestrator for real-time processing
$orchestrator = app(\App\Services\FragmentProcessingOrchestrator::class);
$processingResult = $orchestrator->processChatFragment($fragment, isRealTime: true);

// Log processing metrics for monitoring
Log::info('💬 Chat fragment processed', [
    'fragment_id' => $fragment->id,
    'processing_time_ms' => $processingResult->processingTime,
    'inline_steps' => count($processingResult->inlineSteps),
    'async_steps_queued' => $processingResult->asyncStepsQueued,
]);
```

### **Phase 3: Assistant Fragment Integration**
Update `ProcessAssistantFragment` to use orchestrator:
```php
// In ProcessAssistantFragment
public function __invoke(array $params): void
{
    $fragment = $this->createAssistantFragment($params);
    
    // Use orchestrator instead of direct RouteFragment
    $orchestrator = app(\App\Services\FragmentProcessingOrchestrator::class);
    $processingResult = $orchestrator->processChatFragment($fragment, isRealTime: false);
    
    // Continue with response streaming...
}
```

### **Phase 4: Legacy Pipeline Compatibility**
Ensure `ProcessFragmentJob` uses orchestrator:
```php
// Update ProcessFragmentJob to delegate to orchestrator
public function handle()
{
    $orchestrator = app(\App\Services\FragmentProcessingOrchestrator::class);
    
    $result = $orchestrator->processFragment(
        $this->fragment, 
        ProcessingMode::FULL
    );
    
    // Maintain existing event dispatching and return format
    FragmentProcessed::dispatch(
        $this->fragment->id,
        1,
        [$this->fragment->toArray()]
    );
    
    return [
        'messages' => ["📦 Fragment processed via orchestrator"],
        'fragments' => [$this->fragment->toArray()],
    ];
}
```

## Success Criteria
- [ ] Orchestrator handles both chat and regular fragments uniformly
- [ ] Real-time processing completes within 300ms for chat fragments
- [ ] Async steps queue properly for background processing
- [ ] Zero regression in existing fragment functionality
- [ ] Comprehensive metrics and logging for debugging
- [ ] Configurable processing modes for different use cases
- [ ] Backward compatibility with existing `ProcessFragmentJob` consumers

## Files to Create/Modify
### New Files
- `app/Services/FragmentProcessingOrchestrator.php`
- `app/Enums/ProcessingMode.php`
- `app/DTOs/ProcessingResult.php`
- `app/DTOs/StepResults.php`
- `app/Services/ProcessingMetrics.php`
- `app/Jobs/ProcessFragmentStepJob.php`

### Modified Files
- `app/Http/Controllers/ChatApiController.php`
- `app/Actions/ProcessAssistantFragment.php`
- `app/Jobs/ProcessFragmentJob.php`
- `config/fragments.php`

## Testing Strategy
- Unit tests for orchestrator with different processing modes
- Integration tests for chat fragment processing end-to-end
- Performance tests to validate <300ms real-time processing
- Backward compatibility tests with existing fragment consumers
- Load testing for concurrent chat fragment processing