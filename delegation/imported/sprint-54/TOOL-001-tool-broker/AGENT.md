# TOOL-001: Tool Broker MVP

## Agent Profile
**Type**: Senior Engineer/Code Reviewer  
**Specialization**: Natural Language Processing, Intent Classification, Tool Integration

## Task Overview
Implement natural language intent classification that automatically routes user messages to DSL commands and tool registry without requiring explicit slash commands, enabling conversational tool invocation.

## Context
Currently, tool/command usage requires explicit slash commands:
- User must type `/todo "buy groceries"`
- No natural language routing for "I need to buy groceries" 
- Tool discovery is manual rather than contextual
- No analytics on unmatched intents for new tool development

We need intelligent intent classification that maps natural language to available tools and commands.

## Technical Requirements

### **Tool Broker Architecture**
```php
interface ToolBroker
{
    public function analyzeIntent(string $message, ContextRequest $context): IntentAnalysis;
    public function findMatchingTools(IntentAnalysis $analysis): ToolMatches;
    public function executeToolIfConfident(ToolMatches $matches, array $context): ?ToolResult;
    public function suggestTools(string $message): array;
    public function recordUnmatchedIntent(string $message, ContextRequest $context): void;
}
```

### **Intent Classification System**
```php
class IntentClassifier
{
    // Deterministic patterns for high-confidence matching
    private array $intentPatterns = [
        'create_todo' => [
            '/^(create|add|make|new)\s+(todo|task)/i',
            '/(need to|should|must|have to|reminder)\s+(.+)/i',
            '/^(i need to|todo:?)\s+(.+)/i',
        ],
        'search_fragments' => [
            '/^(find|search|look for|show me)\s+(.+)/i',
            '/(where is|what about|recall)\s+(.+)/i',
        ],
        'create_note' => [
            '/^(note|remember|save|record):?\s+(.+)/i',
            '/^(fyi|for the record)\s+(.+)/i',
        ],
        'list_items' => [
            '/^(list|show|display)\s+(todos|tasks|notes|fragments)/i',
            '/^what (todos|tasks|notes)\s+(do i have|are there)/i',
        ],
        'help_request' => [
            '/^(help|how do i|what can)\s+(.+)/i',
            '/^(explain|show me how)\s+(.+)/i',
        ],
    ];
}
```

### **Configuration Framework**
```php
// config/fragments.php additions
'tool_broker' => [
    'enabled' => env('TOOL_BROKER_ENABLED', true),
    'auto_execute' => env('TOOL_BROKER_AUTO_EXECUTE', false), // Safety first
    'confidence_threshold' => env('TOOL_BROKER_CONFIDENCE_THRESHOLD', 0.8),
    
    'classification' => [
        'use_deterministic' => true,
        'use_ai_fallback' => env('TOOL_BROKER_AI_FALLBACK', true),
        'ai_confidence_threshold' => 0.7,
        'max_suggestions' => 3,
    ],
    
    'execution' => [
        'auto_execute_threshold' => env('TOOL_BROKER_AUTO_THRESHOLD', 0.9),
        'require_confirmation' => env('TOOL_BROKER_REQUIRE_CONFIRMATION', true),
        'timeout_seconds' => env('TOOL_BROKER_TIMEOUT', 30),
    ],
    
    'analytics' => [
        'track_unmatched_intents' => true,
        'suggest_new_tools' => true,
        'log_intent_decisions' => env('TOOL_BROKER_DEBUG', false),
    ],
    
    // Map intents to available tools/commands
    'intent_mappings' => [
        'create_todo' => [
            'primary' => 'todo',
            'alternatives' => ['note', 'remind'],
        ],
        'search_fragments' => [
            'primary' => 'search',
            'alternatives' => ['recall'],
        ],
        'create_note' => [
            'primary' => 'note',
            'alternatives' => ['frag'],
        ],
        'list_items' => [
            'primary' => 'inbox',
            'alternatives' => ['search'],
        ],
    ],
],
```

## Implementation Plan

### **Phase 1: Deterministic Intent Classifier**
```php
<?php

namespace App\Services;

use App\DTOs\IntentAnalysis;
use App\DTOs\ContextRequest;
use Illuminate\Support\Facades\Log;

class DeterministicIntentClassifier
{
    private array $intentPatterns;
    
    public function __construct()
    {
        $this->intentPatterns = [
            'create_todo' => [
                'patterns' => [
                    '/^(create|add|make|new)\s+(todo|task)/i',
                    '/(need to|should|must|have to|reminder)\s+(.+)/i',
                    '/^(i need to|todo:?)\s+(.+)/i',
                    '/^(remember to|don\'t forget to)\s+(.+)/i',
                ],
                'confidence_boost' => 0.2,
                'parameters' => ['todo_text'],
            ],
            
            'search_fragments' => [
                'patterns' => [
                    '/^(find|search|look for|show me)\s+(.+)/i',
                    '/(where is|what about|recall)\s+(.+)/i',
                    '/^(do you remember|have i|did i)\s+(.+)/i',
                ],
                'confidence_boost' => 0.15,
                'parameters' => ['search_query'],
            ],
            
            'create_note' => [
                'patterns' => [
                    '/^(note|remember|save|record):?\s+(.+)/i',
                    '/^(fyi|for the record|important)\s+(.+)/i',
                    '/^(thoughts|idea):?\s+(.+)/i',
                ],
                'confidence_boost' => 0.15,
                'parameters' => ['note_content'],
            ],
            
            'list_items' => [
                'patterns' => [
                    '/^(list|show|display)\s+(todos|tasks|notes|fragments)/i',
                    '/^what (todos|tasks|notes)\s+(do i have|are there)/i',
                    '/^(my|all)\s+(todos|tasks|notes|fragments)/i',
                ],
                'confidence_boost' => 0.25,
                'parameters' => ['item_type'],
            ],
            
            'help_request' => [
                'patterns' => [
                    '/^(help|how do i|what can)\s+(.+)/i',
                    '/^(explain|show me how|teach me)\s+(.+)/i',
                    '/^(what|how)\s+(is|are|do|does)\s+(.+)/i',
                ],
                'confidence_boost' => 0.1,
                'parameters' => ['help_topic'],
            ],
        ];
    }

    public function classifyIntent(string $message, ContextRequest $context): IntentAnalysis
    {
        $message = trim($message);
        $matches = [];
        
        foreach ($this->intentPatterns as $intentName => $config) {
            $confidence = $this->calculateIntentConfidence($message, $config);
            
            if ($confidence > 0) {
                $parameters = $this->extractParameters($message, $config);
                
                $matches[$intentName] = [
                    'intent' => $intentName,
                    'confidence' => $confidence,
                    'parameters' => $parameters,
                    'method' => 'deterministic_patterns',
                ];
            }
        }
        
        // Sort by confidence
        uasort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        
        $bestMatch = !empty($matches) ? reset($matches) : null;
        
        return new IntentAnalysis(
            originalMessage: $message,
            detectedIntent: $bestMatch['intent'] ?? null,
            confidence: $bestMatch['confidence'] ?? 0.0,
            parameters: $bestMatch['parameters'] ?? [],
            allMatches: $matches,
            method: 'deterministic',
            context: $context
        );
    }
    
    private function calculateIntentConfidence(string $message, array $config): float
    {
        $confidence = 0.0;
        $patternMatches = 0;
        
        foreach ($config['patterns'] as $pattern) {
            if (preg_match($pattern, $message)) {
                $patternMatches++;
                $confidence += 0.3; // Base confidence per pattern match
            }
        }
        
        if ($patternMatches > 0) {
            // Boost for multiple pattern matches
            $confidence += $patternMatches * 0.1;
            
            // Apply intent-specific confidence boost
            $confidence += $config['confidence_boost'] ?? 0.0;
            
            // Normalize to 0-1 range
            $confidence = min($confidence, 1.0);
        }
        
        return round($confidence, 2);
    }
    
    private function extractParameters(string $message, array $config): array
    {
        $parameters = [];
        
        foreach ($config['patterns'] as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                // Extract capture groups as parameters
                if (count($matches) > 1) {
                    // Use parameter names if defined, otherwise use generic names
                    $paramNames = $config['parameters'] ?? ['param1', 'param2', 'param3'];
                    
                    for ($i = 1; $i < count($matches) && $i <= count($paramNames); $i++) {
                        if (!empty(trim($matches[$i]))) {
                            $parameters[$paramNames[$i-1]] = trim($matches[$i]);
                        }
                    }
                }
                break; // Use first matching pattern
            }
        }
        
        return $parameters;
    }
}
```

### **Phase 2: Tool Matching and Execution**
```php
class ToolMatcher
{
    public function __construct(
        private CommandRegistry $commandRegistry,
        private ToolRegistry $toolRegistry
    ) {}

    public function findMatchingTools(IntentAnalysis $analysis): ToolMatches
    {
        $mappings = config('fragments.tool_broker.intent_mappings');
        $intent = $analysis->detectedIntent;
        
        if (!$intent || !isset($mappings[$intent])) {
            return new ToolMatches([]);
        }
        
        $mapping = $mappings[$intent];
        $matches = [];
        
        // Primary tool match
        $primaryTool = $this->getToolInfo($mapping['primary']);
        if ($primaryTool) {
            $matches[] = [
                'tool' => $mapping['primary'],
                'type' => 'command',
                'confidence' => $analysis->confidence,
                'parameters' => $this->mapParametersForTool($mapping['primary'], $analysis->parameters),
                'info' => $primaryTool,
                'priority' => 'primary',
            ];
        }
        
        // Alternative tools
        foreach ($mapping['alternatives'] ?? [] as $altTool) {
            $toolInfo = $this->getToolInfo($altTool);
            if ($toolInfo) {
                $matches[] = [
                    'tool' => $altTool,
                    'type' => 'command',
                    'confidence' => $analysis->confidence * 0.8, // Slight reduction for alternatives
                    'parameters' => $this->mapParametersForTool($altTool, $analysis->parameters),
                    'info' => $toolInfo,
                    'priority' => 'alternative',
                ];
            }
        }
        
        return new ToolMatches($matches);
    }
    
    private function mapParametersForTool(string $tool, array $parameters): array
    {
        // Map extracted parameters to tool-specific parameter names
        return match($tool) {
            'todo' => [
                'identifier' => $parameters['todo_text'] ?? $parameters['param1'] ?? '',
            ],
            'search' => [
                'query' => $parameters['search_query'] ?? $parameters['param1'] ?? '',
            ],
            'note' => [
                'content' => $parameters['note_content'] ?? $parameters['param1'] ?? '',
            ],
            'inbox' => [
                'filter' => $parameters['item_type'] ?? 'pending',
            ],
            default => $parameters,
        };
    }
    
    public function executeToolIfConfident(ToolMatches $matches, array $context): ?ToolResult
    {
        $autoExecuteThreshold = config('fragments.tool_broker.execution.auto_execute_threshold');
        $autoExecuteEnabled = config('fragments.tool_broker.auto_execute');
        
        if (!$autoExecuteEnabled || empty($matches->tools)) {
            return null;
        }
        
        $bestMatch = $matches->getBestMatch();
        
        if ($bestMatch['confidence'] < $autoExecuteThreshold) {
            return null;
        }
        
        // Execute the tool
        try {
            Log::info('🔧 Tool Broker: Auto-executing tool', [
                'tool' => $bestMatch['tool'],
                'confidence' => $bestMatch['confidence'],
                'parameters' => $bestMatch['parameters'],
            ]);
            
            $commandRunner = app(\App\Services\Commands\DSL\CommandRunner::class);
            $result = $commandRunner->execute($bestMatch['tool'], $bestMatch['parameters']);
            
            return new ToolResult(
                success: $result['success'],
                tool: $bestMatch['tool'],
                result: $result,
                confidence: $bestMatch['confidence'],
                executionTime: $result['performance']['total_duration_ms'] ?? 0
            );
            
        } catch (\Exception $e) {
            Log::error('🔧 Tool Broker: Tool execution failed', [
                'tool' => $bestMatch['tool'],
                'error' => $e->getMessage(),
            ]);
            
            return new ToolResult(
                success: false,
                tool: $bestMatch['tool'],
                error: $e->getMessage(),
                confidence: $bestMatch['confidence']
            );
        }
    }
}
```

### **Phase 3: Integration with Chat Flow**
```php
// Enhanced ProcessAssistantFragment to include tool analysis
class ProcessAssistantFragment
{
    public function __invoke(array $params): void
    {
        // ... existing fragment creation ...
        
        // Analyze user message for tool intents
        $toolBroker = app(ToolBroker::class);
        $contextRequest = new ContextRequest(
            userMessage: $params['user_message'],
            conversationId: $params['conversation_id'],
            userId: $params['user_id'] ?? null,
        );
        
        $intentAnalysis = $toolBroker->analyzeIntent($params['user_message'], $contextRequest);
        
        // Log intent for analytics
        if ($intentAnalysis->hasIntent()) {
            Log::info('🧠 Intent detected', [
                'intent' => $intentAnalysis->detectedIntent,
                'confidence' => $intentAnalysis->confidence,
                'conversation_id' => $params['conversation_id'],
            ]);
            
            // Find matching tools
            $toolMatches = $toolBroker->findMatchingTools($intentAnalysis);
            
            // Try auto-execution if confidence is high enough
            $toolResult = $toolBroker->executeToolIfConfident($toolMatches, $params);
            
            if ($toolResult && $toolResult->success) {
                // Include tool result in assistant response context
                $params['tool_execution_result'] = $toolResult;
            } else if ($toolMatches->hasMatches()) {
                // Include tool suggestions in response context
                $params['suggested_tools'] = $toolMatches->getSuggestions();
            }
        } else {
            // Record unmatched intent for analysis
            $toolBroker->recordUnmatchedIntent($params['user_message'], $contextRequest);
        }
        
        // Continue with existing assistant processing...
    }
}
```

### **Phase 4: Analytics and Learning**
```php
class ToolBrokerAnalytics
{
    public function recordIntentDecision(IntentAnalysis $analysis, ?ToolResult $result): void
    {
        DB::table('intent_analytics')->insert([
            'message' => $analysis->originalMessage,
            'detected_intent' => $analysis->detectedIntent,
            'confidence' => $analysis->confidence,
            'method' => $analysis->method,
            'tool_executed' => $result ? $result->tool : null,
            'execution_success' => $result ? $result->success : null,
            'conversation_id' => $analysis->context->conversationId,
            'user_id' => $analysis->context->userId,
            'created_at' => now(),
        ]);
    }
    
    public function recordUnmatchedIntent(string $message, ContextRequest $context): void
    {
        // Store for analysis of potential new tools/commands
        DB::table('unmatched_intents')->insert([
            'message' => $message,
            'conversation_id' => $context->conversationId,
            'user_id' => $context->userId,
            'created_at' => now(),
        ]);
        
        Log::info('🤔 Unmatched intent recorded', [
            'message' => Str::limit($message, 100),
            'conversation_id' => $context->conversationId,
        ]);
    }
    
    public function getSuggestionsForNewTools(): array
    {
        // Analyze unmatched intents to suggest new tools
        return DB::table('unmatched_intents')
            ->select('message', DB::raw('COUNT(*) as frequency'))
            ->where('created_at', '>=', now()->subWeek())
            ->groupBy('message')
            ->having('frequency', '>=', 3)
            ->orderBy('frequency', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
```

## Success Criteria
- [ ] 80% accuracy for common tool/command intents from natural language
- [ ] Deterministic classification provides fast, reliable routing
- [ ] AI fallback handles complex or ambiguous intents
- [ ] Auto-execution works for high-confidence matches
- [ ] Tool suggestions appear in chat for medium-confidence matches
- [ ] Analytics track unmatched intents for new tool development
- [ ] Zero performance impact on chat when tool broker is disabled
- [ ] Configuration allows fine-tuning of confidence thresholds

## Files to Create/Modify
### New Files
- `app/Services/ToolBroker.php`
- `app/Services/DeterministicIntentClassifier.php`
- `app/Services/ToolMatcher.php`
- `app/Services/ToolBrokerAnalytics.php`
- `app/DTOs/IntentAnalysis.php`
- `app/DTOs/ToolMatches.php`
- `app/DTOs/ToolResult.php`
- `database/migrations/create_intent_analytics_table.php`
- `database/migrations/create_unmatched_intents_table.php`

### Modified Files
- `app/Actions/ProcessAssistantFragment.php`
- `app/Http/Controllers/ChatApiController.php`
- `config/fragments.php`

## Testing Strategy
- Unit tests for intent classification with various message patterns
- Integration tests for tool matching and execution
- Performance tests to ensure minimal impact on chat flow
- Accuracy tests with real user message examples
- Analytics tests to verify unmatched intent tracking
- A/B testing for tool suggestion effectiveness