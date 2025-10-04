# PIPELINE-002: Chat Fragment Processing Integration

## Agent Profile
**Type**: Senior Engineer/Code Reviewer  
**Specialization**: Chat Systems, Real-time Processing, Embeddings Integration

## Task Overview
Integrate chat fragments into the full processing pipeline, enabling embeddings, classification, and tagging while maintaining real-time performance requirements for chat interactions.

## Context
Currently, chat fragments bypass most processing steps through `CreateChatFragment`, missing classification, tagging, vault routing, and embeddings. This task integrates chat fragments into the comprehensive processing pipeline while ensuring sub-500ms response times.

## Technical Requirements

### **Chat Fragment Processing Enhancement**
Enable full pipeline processing for chat fragments:
- **Embeddings**: Enable `EMBEDDINGS_ENABLED=true` for chat fragments
- **Classification**: Apply type inference and classification
- **Tagging**: Generate relevant tags for chat content
- **Vault Routing**: Proper vault and project assignment
- **Metadata**: Extract entities and metadata from chat

### **Performance Requirements**
- **Real-time Processing**: <300ms for inline steps
- **Async Processing**: Queue expensive operations (embeddings, AI enrichment)
- **Progressive Enhancement**: Basic functionality works even if async steps fail
- **Resource Management**: Prevent chat processing from overwhelming system

## Implementation Plan

### **Phase 1: Enable Embeddings for Chat**
```php
// Update CreateChatFragment to support embeddings
class CreateChatFragment
{
    public function __invoke(string $content): Fragment
    {
        // ... existing normalization logic ...
        
        $fragment = Fragment::create([
            'message' => $normalization['normalized'],
            'type' => 'log',
            'vault' => $defaultVault->name,
            'project_id' => $defaultProject->id,
            'input_hash' => $normalization['hash'],
            'hash_bucket' => $normalization['bucket'],
            'source' => 'chat-user',
            // NEW: Enable processing flags for chat
            'processing_enabled' => true,
            'embeddings_enabled' => config('fragments.embeddings.enabled', false),
        ]);
        
        return $fragment;
    }
}
```

### **Phase 2: Chat-Optimized Processing Steps**
Create chat-specific processing optimizations:
```php
class ChatOptimizedSteps
{
    /**
     * Quick classification for chat messages
     * Uses deterministic rules before AI classification
     */
    public static function quickClassify(Fragment $fragment): ?string
    {
        $content = strtolower($fragment->message);
        
        // Question patterns
        if (preg_match('/\?|how to|what is|why|when|where/', $content)) {
            return 'question';
        }
        
        // Task patterns  
        if (preg_match('/todo|task|remind|need to|should/', $content)) {
            return 'todo';
        }
        
        // Note patterns
        if (str_word_count($content) > 10) {
            return 'note';
        }
        
        return null; // Fall back to AI classification
    }
    
    /**
     * Extract quick metadata for chat
     */
    public static function extractChatMetadata(Fragment $fragment): array
    {
        $metadata = [];
        $content = $fragment->message;
        
        // Extract dates
        if (preg_match('/\b(today|tomorrow|next week|monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', $content)) {
            $metadata['contains_date'] = true;
        }
        
        // Extract mentions
        if (preg_match_all('/@(\w+)/', $content, $matches)) {
            $metadata['mentions'] = $matches[1];
        }
        
        // Extract hashtags
        if (preg_match_all('/#(\w+)/', $content, $matches)) {
            $metadata['hashtags'] = $matches[1];
        }
        
        // Extract priorities
        if (preg_match('/urgent|asap|important|!!!/i', $content)) {
            $metadata['priority'] = 'high';
        }
        
        return $metadata;
    }
}
```

### **Phase 3: Enhanced Processing Pipeline Configuration**
```php
// config/fragments.php additions
'chat_processing' => [
    'enabled' => env('CHAT_PROCESSING_ENABLED', true),
    'embeddings_enabled' => env('CHAT_EMBEDDINGS_ENABLED', true),
    'realtime_timeout_ms' => env('CHAT_REALTIME_TIMEOUT', 300),
    
    'inline_steps' => [
        // Fast deterministic steps for real-time processing
        \App\Actions\ParseAtomicFragment::class,
        \App\Actions\ExtractMetadataEntities::class,
        \App\Services\ChatOptimizedSteps::class . '@quickClassify',
        \App\Services\ChatOptimizedSteps::class . '@extractChatMetadata',
        \App\Actions\RouteToVault::class,
    ],
    
    'async_steps' => [
        // Expensive operations queued for background processing
        \App\Actions\InferFragmentType::class,
        \App\Actions\EnrichFragmentWithAI::class,
        \App\Actions\SuggestTags::class,
        \App\Actions\EmbedFragmentAction::class,
        \App\Actions\GenerateAutoTitle::class,
    ],
    
    'embedding_config' => [
        'provider' => env('CHAT_EMBEDDING_PROVIDER', 'ollama'),
        'model' => env('CHAT_EMBEDDING_MODEL', 'nomic-embed-text'),
        'batch_size' => env('CHAT_EMBEDDING_BATCH_SIZE', 5),
        'queue_delay_seconds' => env('CHAT_EMBEDDING_DELAY', 2),
    ],
],
```

### **Phase 4: Assistant Fragment Enhancement**
Update assistant fragment processing for better pipeline integration:
```php
// Enhanced ProcessAssistantFragment
class ProcessAssistantFragment
{
    public function __invoke(array $params): void
    {
        // Create assistant fragment with enhanced metadata
        $fragment = $this->createAssistantFragment($params);
        
        // Set assistant-specific processing flags
        $fragment->update([
            'source' => 'chat-assistant',
            'metadata' => array_merge($fragment->metadata ?? [], [
                'turn' => 'response',
                'conversation_id' => $params['conversation_id'],
                'response_to_fragment_id' => $params['user_fragment_id'],
                'model_used' => $params['model'],
                'provider_used' => $params['provider'],
            ]),
        ]);
        
        // Process through orchestrator with assistant-optimized mode
        $orchestrator = app(FragmentProcessingOrchestrator::class);
        $result = $orchestrator->processFragment(
            $fragment, 
            ProcessingMode::REALTIME,
            ['context' => 'assistant_response']
        );
        
        // Log assistant processing for analytics
        Log::info('🤖 Assistant fragment processed', [
            'fragment_id' => $fragment->id,
            'conversation_id' => $params['conversation_id'],
            'processing_time_ms' => $result->processingTime,
            'steps_completed' => count($result->inlineSteps),
        ]);
        
        // Continue with existing response logic...
    }
    
    private function createAssistantFragment(array $params): Fragment
    {
        // Enhanced assistant fragment creation with better metadata
        $content = $params['content'];
        $conversationId = $params['conversation_id'];
        
        // Extract structured data if present
        $structuredData = $this->extractStructuredData($content);
        
        return Fragment::create([
            'message' => $content,
            'type' => $structuredData['type'] ?? 'response',
            'vault' => $params['vault'] ?? Vault::getDefault()->name,
            'project_id' => $params['project_id'] ?? Project::getDefaultForVault(Vault::getDefault()->id)->id,
            'source' => 'chat-assistant',
            'metadata' => [
                'conversation_id' => $conversationId,
                'structured_data' => $structuredData,
                'model_info' => [
                    'provider' => $params['provider'],
                    'model' => $params['model'],
                    'temperature' => $params['temperature'] ?? 0.7,
                ],
            ],
            'processing_enabled' => true,
            'embeddings_enabled' => config('fragments.chat_processing.embeddings_enabled', true),
        ]);
    }
}
```

### **Phase 5: Embeddings Optimization for Chat**
```php
class ChatEmbeddingOptimizer
{
    public function shouldEmbedChatFragment(Fragment $fragment): bool
    {
        // Skip very short messages
        if (str_word_count($fragment->message) < 3) {
            return false;
        }
        
        // Skip common greetings and responses
        $lowValue = ['hi', 'hello', 'thanks', 'ok', 'yes', 'no', 'sure'];
        if (in_array(strtolower(trim($fragment->message)), $lowValue)) {
            return false;
        }
        
        // Prioritize questions and substantial content
        if (str_word_count($fragment->message) > 10 || 
            str_contains($fragment->message, '?')) {
            return true;
        }
        
        return true;
    }
    
    public function optimizeEmbeddingBatching(array $fragments): array
    {
        // Group chat fragments for batch embedding
        // Prioritize by conversation and timing
        return collect($fragments)
            ->groupBy('metadata.conversation_id')
            ->map(function ($conversationFragments) {
                return $conversationFragments->sortBy('created_at');
            })
            ->flatten()
            ->values()
            ->toArray();
    }
}
```

### **Phase 6: Performance Monitoring**
```php
class ChatProcessingMetrics
{
    public function recordChatProcessing(
        Fragment $fragment, 
        ProcessingResult $result,
        string $context = 'chat'
    ): void {
        $metrics = [
            'fragment_id' => $fragment->id,
            'context' => $context,
            'source' => $fragment->source,
            'processing_time_ms' => $result->processingTime,
            'inline_steps_count' => count($result->inlineSteps->successful),
            'async_steps_queued' => $result->asyncStepsQueued,
            'failed_steps' => count($result->inlineSteps->failed),
            'embeddings_queued' => $this->wasEmbeddingQueued($result),
            'conversation_id' => $fragment->metadata['conversation_id'] ?? null,
        ];
        
        // Log for monitoring
        Log::info('💬 Chat processing metrics', $metrics);
        
        // Store for analytics
        DB::table('fragment_processing_metrics')->insert([
            ...$metrics,
            'created_at' => now(),
        ]);
        
        // Alert if processing is slow
        if ($result->processingTime > 500) {
            Log::warning('🐌 Slow chat processing detected', $metrics);
        }
    }
    
    public function getDailyProcessingStats(): array
    {
        return DB::table('fragment_processing_metrics')
            ->where('created_at', '>=', now()->subDay())
            ->where('context', 'chat')
            ->selectRaw('
                COUNT(*) as total_fragments,
                AVG(processing_time_ms) as avg_processing_time,
                MAX(processing_time_ms) as max_processing_time,
                SUM(embeddings_queued) as total_embeddings_queued,
                SUM(CASE WHEN failed_steps > 0 THEN 1 ELSE 0 END) as fragments_with_errors
            ')
            ->first();
    }
}
```

## Success Criteria
- [ ] Chat fragments receive embeddings when enabled
- [ ] Classification and tagging work for chat content
- [ ] Real-time processing stays under 300ms
- [ ] Async processing queues properly without blocking chat
- [ ] Assistant fragments get enhanced metadata and processing
- [ ] Performance monitoring shows processing metrics
- [ ] Zero degradation in chat responsiveness
- [ ] Embeddings enable better search and recall for chat content

## Files to Create/Modify
### New Files
- `app/Services/ChatOptimizedSteps.php`
- `app/Services/ChatEmbeddingOptimizer.php`
- `app/Services/ChatProcessingMetrics.php`
- `database/migrations/create_fragment_processing_metrics_table.php`

### Modified Files
- `app/Actions/CreateChatFragment.php`
- `app/Actions/ProcessAssistantFragment.php`
- `config/fragments.php`
- `app/Http/Controllers/ChatApiController.php`

## Testing Strategy
- Integration tests for chat fragment pipeline end-to-end
- Performance tests to validate real-time processing requirements
- Embeddings tests to ensure chat content is properly embedded
- Load testing for concurrent chat fragment processing
- Assistant fragment processing validation
- Metrics collection and monitoring verification