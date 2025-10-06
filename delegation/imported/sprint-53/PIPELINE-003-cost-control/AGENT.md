# PIPELINE-003: Cost Control & Deterministic Priority

## Agent Profile
**Type**: Infrastructure Specialist  
**Specialization**: Performance Optimization, Cost Management, AI Resource Management

## Task Overview
Implement intelligent cost controls that prioritize deterministic processing over AI with configurable thresholds, reducing AI API costs while maintaining processing quality and system intelligence.

## Context
Currently, AI enrichment steps (type inference, tagging, enrichment) run for every fragment regardless of whether deterministic methods could provide sufficient results. This creates unnecessary costs and latency. We need smart routing that uses AI only when deterministic confidence is low.

## Technical Requirements

### **Cost Control Architecture**
```php
interface CostController
{
    public function shouldUseAI(string $operation, Fragment $fragment): bool;
    public function getDeterministicConfidence(Fragment $fragment, string $operation): float;
    public function recordCostDecision(string $operation, bool $usedAI, float $cost): void;
    public function getCostMetrics(string $period = '24h'): CostMetrics;
}
```

### **Deterministic-First Processing**
Implement deterministic alternatives that run before AI:
- **Type Classification**: Rule-based classification before AI inference
- **Tag Generation**: Pattern-based tagging before AI suggestions  
- **Metadata Extraction**: Regex/rule extraction before AI enrichment
- **Cost Tracking**: Monitor AI usage and costs per operation

### **Configuration Framework**
```php
// config/fragments.php additions
'cost_control' => [
    'enabled' => env('FRAGMENT_COST_CONTROL_ENABLED', true),
    'deterministic_first' => env('FRAGMENT_DETERMINISTIC_FIRST', true),
    
    'confidence_thresholds' => [
        'type_inference' => env('CONFIDENCE_THRESHOLD_TYPE', 0.8),
        'tag_generation' => env('CONFIDENCE_THRESHOLD_TAGS', 0.7),
        'enrichment' => env('CONFIDENCE_THRESHOLD_ENRICHMENT', 0.6),
    ],
    
    'cost_limits' => [
        'daily_ai_calls' => env('DAILY_AI_CALL_LIMIT', 1000),
        'hourly_ai_calls' => env('HOURLY_AI_CALL_LIMIT', 100),
        'cost_per_operation' => [
            'type_inference' => 0.001,    // $0.001 per call
            'enrichment' => 0.005,        // $0.005 per call
            'tagging' => 0.002,           // $0.002 per call
        ],
    ],
    
    'fallback_behavior' => [
        'on_limit_exceeded' => env('COST_LIMIT_BEHAVIOR', 'deterministic_only'), // or 'queue_delayed'
        'on_ai_failure' => env('AI_FAILURE_BEHAVIOR', 'use_deterministic'),
        'on_low_confidence' => env('LOW_CONFIDENCE_BEHAVIOR', 'use_ai'),
    ],
],
```

## Implementation Plan

### **Phase 1: Deterministic Confidence Engine**
```php
class DeterministicConfidenceEngine
{
    public function __construct(
        private TypeClassifier $typeClassifier,
        private TagExtractor $tagExtractor,
        private MetadataExtractor $metadataExtractor
    ) {}

    public function analyzeFragment(Fragment $fragment): ConfidenceAnalysis
    {
        $analysis = new ConfidenceAnalysis($fragment);
        
        // Type classification confidence
        $typeResult = $this->typeClassifier->classifyDeterministic($fragment);
        $analysis->setTypeConfidence($typeResult['confidence'], $typeResult['type']);
        
        // Tag extraction confidence
        $tagResult = $this->tagExtractor->extractDeterministic($fragment);
        $analysis->setTagConfidence($tagResult['confidence'], $tagResult['tags']);
        
        // Metadata extraction confidence
        $metadataResult = $this->metadataExtractor->extractDeterministic($fragment);
        $analysis->setMetadataConfidence($metadataResult['confidence'], $metadataResult['metadata']);
        
        return $analysis;
    }

    public function shouldUseAI(string $operation, Fragment $fragment): bool
    {
        $analysis = $this->analyzeFragment($fragment);
        $threshold = config("fragments.cost_control.confidence_thresholds.{$operation}", 0.7);
        
        $confidence = match($operation) {
            'type_inference' => $analysis->getTypeConfidence(),
            'tag_generation' => $analysis->getTagConfidence(),
            'enrichment' => $analysis->getMetadataConfidence(),
            default => 0.0
        };
        
        // Use AI if deterministic confidence is below threshold
        return $confidence < $threshold;
    }
}
```

### **Phase 2: Deterministic Classifiers**
```php
class DeterministicTypeClassifier
{
    private array $patterns = [
        'todo' => [
            '/\b(todo|task|remind|need to|should|must|have to)\b/i',
            '/\b(complete|finish|do|work on)\b/i',
        ],
        'question' => [
            '/\?/',
            '/\b(how|what|why|when|where|who|which)\b/i',
            '/\b(explain|help|assist)\b/i',
        ],
        'note' => [
            '/\b(note|remember|important|fyi)\b/i',
            '/\b(thoughts|ideas|observations)\b/i',
        ],
        'meeting' => [
            '/\b(meeting|call|discussion|standup)\b/i',
            '/\b(agenda|minutes|action items)\b/i',
        ],
        'code' => [
            '/```/',
            '/\b(function|class|def|var|const)\b/',
            '/\b(git|commit|pull request|bug|fix)\b/i',
        ],
    ];

    public function classifyDeterministic(Fragment $fragment): array
    {
        $content = $fragment->message;
        $scores = [];
        
        foreach ($this->patterns as $type => $patterns) {
            $score = 0;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $score += 1;
                }
            }
            $scores[$type] = $score;
        }
        
        // Find highest scoring type
        $maxScore = max($scores);
        $bestType = array_search($maxScore, $scores);
        
        // Calculate confidence based on score and content characteristics
        $confidence = $this->calculateConfidence($maxScore, $content, $bestType);
        
        return [
            'type' => $bestType,
            'confidence' => $confidence,
            'scores' => $scores,
            'method' => 'deterministic_patterns',
        ];
    }
    
    private function calculateConfidence(int $score, string $content, ?string $type): float
    {
        if ($score === 0) {
            return 0.0;
        }
        
        // Base confidence from pattern matches
        $baseConfidence = min($score * 0.3, 0.9);
        
        // Adjust based on content characteristics
        $wordCount = str_word_count($content);
        if ($wordCount < 3) {
            $baseConfidence *= 0.5; // Low confidence for very short content
        }
        
        // Type-specific confidence adjustments
        if ($type === 'question' && str_contains($content, '?')) {
            $baseConfidence = max($baseConfidence, 0.9);
        }
        
        if ($type === 'code' && preg_match('/```.*```/s', $content)) {
            $baseConfidence = max($baseConfidence, 0.95);
        }
        
        return round($baseConfidence, 2);
    }
}
```

### **Phase 3: Deterministic Tag Extractor**
```php
class DeterministicTagExtractor
{
    private array $tagPatterns = [
        // Explicit hashtags
        'hashtags' => '/#([a-zA-Z0-9_]+)/',
        
        // Context patterns
        'work' => '/\b(work|office|meeting|project|deadline|client)\b/i',
        'personal' => '/\b(personal|home|family|weekend|vacation)\b/i',
        'urgent' => '/\b(urgent|asap|emergency|critical|important)\b/i',
        'learning' => '/\b(learn|study|research|tutorial|course)\b/i',
        'health' => '/\b(health|doctor|exercise|diet|medical)\b/i',
        'finance' => '/\b(money|budget|expense|income|investment)\b/i',
        'travel' => '/\b(travel|trip|vacation|flight|hotel)\b/i',
        'tech' => '/\b(code|programming|github|api|database|deploy)\b/i',
    ];

    public function extractDeterministic(Fragment $fragment): array
    {
        $content = $fragment->message;
        $extractedTags = [];
        $confidence = 0.0;
        
        foreach ($this->tagPatterns as $tag => $pattern) {
            if ($tag === 'hashtags') {
                // Extract explicit hashtags
                if (preg_match_all($pattern, $content, $matches)) {
                    $extractedTags = array_merge($extractedTags, $matches[1]);
                    $confidence += 0.2 * count($matches[1]);
                }
            } else {
                // Extract implicit tags from patterns
                if (preg_match($pattern, $content)) {
                    $extractedTags[] = $tag;
                    $confidence += 0.15;
                }
            }
        }
        
        // Add priority tags based on keywords
        if (preg_match('/\b(urgent|asap|!!)\b/i', $content)) {
            $extractedTags[] = 'priority:high';
            $confidence += 0.2;
        }
        
        // Remove duplicates and limit to reasonable number
        $extractedTags = array_unique($extractedTags);
        $extractedTags = array_slice($extractedTags, 0, 8);
        
        // Normalize confidence to 0-1 range
        $confidence = min($confidence, 1.0);
        
        return [
            'tags' => $extractedTags,
            'confidence' => $confidence,
            'method' => 'deterministic_patterns',
        ];
    }
}
```

### **Phase 4: Cost Management Service**
```php
class CostManagementService
{
    private Redis $redis;
    private LoggerInterface $logger;

    public function shouldAllowAICall(string $operation, Fragment $fragment): bool
    {
        // Check cost limits
        if ($this->isOverCostLimit($operation)) {
            $this->logger->warning('AI call blocked: cost limit exceeded', [
                'operation' => $operation,
                'fragment_id' => $fragment->id,
            ]);
            return false;
        }
        
        // Check deterministic confidence
        $confidence = app(DeterministicConfidenceEngine::class)
            ->analyzeFragment($fragment)
            ->getConfidenceFor($operation);
            
        $threshold = config("fragments.cost_control.confidence_thresholds.{$operation}");
        
        if ($confidence >= $threshold) {
            $this->logger->info('AI call skipped: deterministic confidence sufficient', [
                'operation' => $operation,
                'confidence' => $confidence,
                'threshold' => $threshold,
                'fragment_id' => $fragment->id,
            ]);
            return false;
        }
        
        return true;
    }
    
    public function recordAICall(string $operation, Fragment $fragment, float $cost): void
    {
        // Increment usage counters
        $hourKey = "ai_calls:hour:" . now()->format('Y-m-d-H');
        $dayKey = "ai_calls:day:" . now()->format('Y-m-d');
        
        $this->redis->incr($hourKey);
        $this->redis->expire($hourKey, 3600); // 1 hour TTL
        
        $this->redis->incr($dayKey);
        $this->redis->expire($dayKey, 86400); // 24 hour TTL
        
        // Record cost
        $this->redis->incrByFloat("ai_cost:day:" . now()->format('Y-m-d'), $cost);
        
        // Log for analytics
        $this->logger->info('AI call recorded', [
            'operation' => $operation,
            'fragment_id' => $fragment->id,
            'cost' => $cost,
            'hour_total' => $this->redis->get($hourKey),
            'day_total' => $this->redis->get($dayKey),
        ]);
    }
    
    private function isOverCostLimit(string $operation): bool
    {
        $hourLimit = config('fragments.cost_control.cost_limits.hourly_ai_calls');
        $dayLimit = config('fragments.cost_control.cost_limits.daily_ai_calls');
        
        $hourUsage = $this->redis->get("ai_calls:hour:" . now()->format('Y-m-d-H')) ?: 0;
        $dayUsage = $this->redis->get("ai_calls:day:" . now()->format('Y-m-d')) ?: 0;
        
        return $hourUsage >= $hourLimit || $dayUsage >= $dayLimit;
    }
}
```

### **Phase 5: Integration with Processing Pipeline**
```php
// Enhanced step execution with cost control
class CostAwareStepExecutor
{
    public function executeStep(string $stepClass, Fragment $fragment): StepResult
    {
        // Check if this is an AI step
        if ($this->isAIStep($stepClass)) {
            $operation = $this->getOperationForStep($stepClass);
            
            // Try deterministic first
            $deterministicResult = $this->tryDeterministicAlternative($stepClass, $fragment);
            
            if ($deterministicResult && $deterministicResult->isConfident()) {
                return $deterministicResult;
            }
            
            // Check if AI is allowed
            $costManager = app(CostManagementService::class);
            if (!$costManager->shouldAllowAICall($operation, $fragment)) {
                // Fall back to deterministic or skip
                return $deterministicResult ?? new SkippedStepResult($stepClass, 'cost_limit_exceeded');
            }
            
            // Proceed with AI call
            $aiResult = $this->executeAIStep($stepClass, $fragment);
            $costManager->recordAICall($operation, $fragment, $this->getStepCost($stepClass));
            
            return $aiResult;
        }
        
        // Execute non-AI steps normally
        return $this->executeRegularStep($stepClass, $fragment);
    }
    
    private function tryDeterministicAlternative(string $stepClass, Fragment $fragment): ?StepResult
    {
        return match($stepClass) {
            InferFragmentType::class => app(DeterministicTypeClassifier::class)->classify($fragment),
            SuggestTags::class => app(DeterministicTagExtractor::class)->extract($fragment),
            ExtractMetadataEntities::class => app(DeterministicMetadataExtractor::class)->extract($fragment),
            default => null
        };
    }
}
```

## Success Criteria
- [ ] 40% reduction in AI API calls through deterministic-first processing
- [ ] Configurable confidence thresholds for AI vs deterministic decisions
- [ ] Cost tracking and limits prevent runaway AI usage
- [ ] Quality metrics show minimal degradation in processing accuracy
- [ ] Real-time cost monitoring and alerting
- [ ] Graceful fallback when cost limits are exceeded
- [ ] Comprehensive logging for cost analysis and optimization

## Files to Create/Modify
### New Files
- `app/Services/DeterministicConfidenceEngine.php`
- `app/Services/DeterministicTypeClassifier.php`
- `app/Services/DeterministicTagExtractor.php`
- `app/Services/CostManagementService.php`
- `app/Services/CostAwareStepExecutor.php`
- `app/DTOs/ConfidenceAnalysis.php`
- `app/DTOs/CostMetrics.php`

### Modified Files
- `config/fragments.php`
- `app/Services/FragmentProcessingOrchestrator.php`
- `app/Actions/InferFragmentType.php`
- `app/Actions/SuggestTags.php`

## Testing Strategy
- Unit tests for deterministic classifiers with known patterns
- Integration tests for cost control limits and fallbacks
- Performance tests to validate processing speed improvements
- Cost analysis tests to measure AI usage reduction
- Quality comparison tests (deterministic vs AI results)
- Load testing with cost limits enabled