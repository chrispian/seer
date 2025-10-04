# PIPELINE-001: Context & Architecture Analysis

## Current Processing Flow Analysis

### **Chat Fragment Processing (Current)**
```php
// ChatApiController@send
$createChatFragment = app(\App\Actions\CreateChatFragment::class);
$fragment = $createChatFragment($data['content']); 
// Fragment created with minimal processing - bypasses full pipeline

// ProcessAssistantFragment (for responses)
$routeFragment = app(RouteFragment::class);
$fragment = $routeFragment($content); // Uses RouteFragment but skips many steps
```

### **Regular Fragment Processing (Current)**
```php
// ProcessFragmentJob pipeline
$processed = app(Pipeline::class)
    ->send($this->fragment)
    ->through([
        \App\Actions\DriftSync::class,
        \App\Actions\ParseAtomicFragment::class,
        \App\Actions\ExtractMetadataEntities::class,
        \App\Actions\GenerateAutoTitle::class,
        \App\Actions\EnrichFragmentWithAI::class,      // AI - expensive
        \App\Actions\InferFragmentType::class,         // AI - expensive  
        \App\Actions\SuggestTags::class,               // AI - expensive
        \App\Actions\RouteToVault::class,
        \App\Actions\EmbedFragmentAction::class,       // Disabled by default
    ])
    ->thenReturn();
```

## Problem Analysis

### **Performance vs Completeness Trade-off**
- **Chat Fragments**: Fast but incomplete (no classification, embeddings, tagging)
- **Regular Fragments**: Complete but slow (AI steps block processing)
- **Missing Middle Ground**: No fast deterministic processing with async AI enhancement

### **Code Duplication Issues**
- `CreateChatFragment` and `RouteFragment` have overlapping normalization logic
- `ProcessAssistantFragment` partially reimplements fragment routing
- No shared configuration for processing behavior

### **Cost Inefficiency**
- AI enrichment always runs even when deterministic confidence is high
- No conditional execution based on fragment content or context
- Embeddings disabled globally instead of selectively

## Target Architecture

### **Unified Processing Flow**
```php
// Single entry point for all fragment processing
$orchestrator = app(FragmentProcessingOrchestrator::class);

// Chat fragments (real-time mode)
$result = $orchestrator->processChatFragment($fragment, isRealTime: true);
// → Inline: Parse, Extract Metadata, Deterministic Classification
// → Async: AI Enrichment, Embeddings, Advanced Tagging

// Regular fragments (full mode)  
$result = $orchestrator->processFragment($fragment, ProcessingMode::FULL);
// → All steps execute inline or with configurable async behavior

// Background jobs (async mode)
$result = $orchestrator->processFragment($fragment, ProcessingMode::ASYNC);
// → All steps execute in background queue
```

### **Intelligent Step Selection**
```php
class StepSelector 
{
    public function shouldExecuteInline(string $stepClass, Fragment $fragment): bool
    {
        // Deterministic steps always inline
        if ($this->isDeterministic($stepClass)) {
            return true;
        }
        
        // AI steps inline only if confidence is low and real-time required
        if ($this->isAIStep($stepClass)) {
            return $this->getDeterministicConfidence($fragment) < 0.8 
                && $this->isRealTimeRequired($fragment);
        }
        
        return false;
    }
    
    public function getDeterministicConfidence(Fragment $fragment): float
    {
        // Analyze fragment content for classification confidence
        // Return 0.0-1.0 confidence score for deterministic processing
    }
}
```

### **Performance Optimization Strategy**
```php
class ProcessingOptimizer
{
    public function optimizeForRealtime(array $steps, Fragment $fragment): array
    {
        return [
            'inline' => [
                // Fast deterministic steps only
                \App\Actions\ParseAtomicFragment::class,
                \App\Actions\ExtractMetadataEntities::class,
                \App\Actions\QuickClassification::class, // New deterministic classifier
            ],
            'async' => [
                // AI and expensive operations
                \App\Actions\EnrichFragmentWithAI::class,
                \App\Actions\InferFragmentType::class,
                \App\Actions\SuggestTags::class,
                \App\Actions\EmbedFragmentAction::class,
            ]
        ];
    }
}
```

## Configuration Architecture

### **Processing Mode Configuration**
```php
// config/fragments.php
'processing' => [
    'modes' => [
        'realtime' => [
            'timeout_ms' => 300,
            'max_inline_steps' => 5,
            'deterministic_only' => false,
            'async_queue' => 'fragments-realtime',
        ],
        'full' => [
            'timeout_ms' => 30000,
            'max_inline_steps' => 20,
            'deterministic_only' => false,
            'async_queue' => 'fragments',
        ],
        'deterministic' => [
            'timeout_ms' => 100,
            'max_inline_steps' => 10,
            'deterministic_only' => true,
            'async_queue' => null,
        ],
    ],
    
    'step_classification' => [
        'deterministic' => [
            \App\Actions\DriftSync::class,
            \App\Actions\ParseAtomicFragment::class,
            \App\Actions\ExtractMetadataEntities::class,
            \App\Actions\RouteToVault::class,
        ],
        'ai_powered' => [
            \App\Actions\EnrichFragmentWithAI::class,
            \App\Actions\InferFragmentType::class,
            \App\Actions\SuggestTags::class,
        ],
        'expensive' => [
            \App\Actions\EmbedFragmentAction::class,
            \App\Actions\GenerateAutoTitle::class,
        ],
    ],
],
```

### **Conditional Step Execution**
```php
'step_conditions' => [
    \App\Actions\InferFragmentType::class => [
        'execute_if' => 'deterministic_confidence < 0.8',
        'fallback_to' => \App\Actions\QuickTypeInference::class,
    ],
    \App\Actions\GenerateAutoTitle::class => [
        'execute_if' => 'title_missing AND content_length > 50',
        'mode' => 'async_only',
    ],
    \App\Actions\EmbedFragmentAction::class => [
        'execute_if' => 'embeddings_enabled AND content_length > 10',
        'priority' => 'low',
    ],
],
```

## Integration Points

### **Chat API Integration**
```php
// ChatApiController changes needed
class ChatApiController extends Controller 
{
    public function __construct(
        private FragmentProcessingOrchestrator $orchestrator
    ) {}
    
    public function send(Request $req) 
    {
        // Create fragment using existing action
        $fragment = app(CreateChatFragment::class)($data['content']);
        
        // Process through orchestrator with real-time mode
        $processingResult = $this->orchestrator->processChatFragment(
            $fragment, 
            isRealTime: true
        );
        
        // Include processing metadata in response for debugging
        return response()->json([
            'message_id' => $messageId,
            'user_fragment_id' => $fragment->id,
            'processing' => [
                'time_ms' => $processingResult->processingTime,
                'steps_completed' => count($processingResult->inlineSteps),
                'steps_queued' => $processingResult->asyncStepsQueued,
            ],
        ]);
    }
}
```

### **Background Job Integration**
```php
// ProcessFragmentJob becomes a wrapper
class ProcessFragmentJob implements ShouldQueue
{
    public function handle()
    {
        $orchestrator = app(FragmentProcessingOrchestrator::class);
        
        // Use full processing mode for background jobs
        $result = $orchestrator->processFragment(
            $this->fragment,
            ProcessingMode::FULL,
            ['source' => 'background_job']
        );
        
        // Maintain existing event dispatching for compatibility
        FragmentProcessed::dispatch(
            $this->fragment->id,
            1,
            [$this->fragment->toArray()]
        );
        
        return [
            'messages' => ["📦 Fragment processed: {$this->fragment->message}"],
            'fragments' => [$this->fragment->toArray()],
            'processing_stats' => $result->getStats(),
        ];
    }
}
```

## Backward Compatibility Strategy

### **Migration Approach**
1. **Phase 1**: Create orchestrator alongside existing processing
2. **Phase 2**: Update chat controllers to use orchestrator  
3. **Phase 3**: Update background jobs to delegate to orchestrator
4. **Phase 4**: Remove duplicate processing logic

### **Feature Flags**
```php
// Gradual rollout with feature flags
'orchestrator' => [
    'enabled' => env('FRAGMENT_ORCHESTRATOR_ENABLED', false),
    'chat_processing' => env('ORCHESTRATOR_CHAT_ENABLED', false),
    'background_processing' => env('ORCHESTRATOR_BACKGROUND_ENABLED', false),
],
```

### **Monitoring and Rollback**
```php
class ProcessingMetrics
{
    public function recordProcessing(
        Fragment $fragment, 
        ProcessingMode $mode, 
        float $duration,
        StepResults $results
    ): void {
        // Record detailed metrics for performance monitoring
        // Enable rollback if performance degrades
    }
}
```

This orchestrator architecture provides the foundation for unified fragment processing while maintaining the performance characteristics needed for real-time chat interactions and the completeness required for comprehensive fragment analysis.