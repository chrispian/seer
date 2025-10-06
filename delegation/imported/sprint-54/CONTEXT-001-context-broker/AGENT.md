# CONTEXT-001: Context Broker Implementation

## Agent Profile
**Type**: Senior Engineer/Code Reviewer  
**Specialization**: Context Management, Prompt Engineering, Chat Systems Architecture

## Task Overview
Replace hard-coded chat context with a dynamic `ContextBroker` service that assembles system prompts, chat history, memory, project context, and user preferences into optimized message arrays for AI providers.

## Context
Currently, chat context is minimal and hard-coded:
```php
$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => $data['content']],
];
```

This provides no personalization, project context, memory, or chat history. We need dynamic context assembly that adapts to user, project, conversation state, and available information.

## Technical Requirements

### **Context Broker Architecture**
```php
interface ContextBroker
{
    public function assembleContext(ContextRequest $request): ContextResult;
    public function getSystemPrompts(ContextRequest $request): array;
    public function getChatHistory(string $conversationId, int $limit = 10): array;
    public function getRelevantMemory(ContextRequest $request): array;
    public function getProjectContext(int $projectId): array;
    public function optimizeForTokens(array $messages, int $maxTokens): array;
}
```

### **Context Assembly Configuration**
```php
// config/fragments.php additions
'context_broker' => [
    'enabled' => env('CONTEXT_BROKER_ENABLED', true),
    'debug_mode' => env('CONTEXT_BROKER_DEBUG', false),
    
    'sources' => [
        'system_prompts' => [
            'enabled' => true,
            'priority' => 100,
            'sources' => ['user_preferences', 'project_settings', 'global_defaults'],
        ],
        'chat_history' => [
            'enabled' => true,
            'priority' => 90,
            'max_messages' => env('CONTEXT_HISTORY_LIMIT', 10),
            'max_tokens' => env('CONTEXT_HISTORY_TOKENS', 4000),
        ],
        'memory_fragments' => [
            'enabled' => true,
            'priority' => 80,
            'max_fragments' => env('CONTEXT_MEMORY_LIMIT', 5),
            'relevance_threshold' => env('CONTEXT_MEMORY_THRESHOLD', 0.7),
        ],
        'project_context' => [
            'enabled' => true,
            'priority' => 70,
            'include_readme' => true,
            'include_recent_activity' => true,
        ],
        'user_preferences' => [
            'enabled' => true,
            'priority' => 60,
            'include_bio' => true,
            'include_work_context' => true,
        ],
    ],
    
    'optimization' => [
        'max_total_tokens' => env('CONTEXT_MAX_TOKENS', 8000),
        'trim_strategy' => env('CONTEXT_TRIM_STRATEGY', 'oldest_first'), // oldest_first, least_relevant, balanced
        'preserve_system' => true,
        'preserve_recent' => 3, // Always keep last N messages
    ],
],
```

## Implementation Plan

### **Phase 1: Core Context Broker Service**
```php
<?php

namespace App\Services;

use App\DTOs\ContextRequest;
use App\DTOs\ContextResult;
use Illuminate\Support\Facades\Log;

class ContextBroker
{
    public function __construct(
        private SystemPromptService $systemPrompts,
        private ChatHistoryService $chatHistory,
        private MemoryService $memoryService,
        private ProjectContextService $projectContext,
        private TokenOptimizer $tokenOptimizer
    ) {}

    public function assembleContext(ContextRequest $request): ContextResult
    {
        $startTime = microtime(true);
        
        Log::info('🧠 Context Broker: Assembling context', [
            'conversation_id' => $request->conversationId,
            'user_id' => $request->userId,
            'project_id' => $request->projectId,
        ]);

        $context = new ContextBuilder();
        
        // Assemble context sources by priority
        $this->addSystemPrompts($context, $request);
        $this->addChatHistory($context, $request);
        $this->addRelevantMemory($context, $request);
        $this->addProjectContext($context, $request);
        $this->addUserContext($context, $request);
        
        // Add current user message
        $context->addMessage('user', $request->userMessage);
        
        // Optimize for token limits
        $optimizedMessages = $this->tokenOptimizer->optimize(
            $context->getMessages(),
            $request->maxTokens ?? config('fragments.context_broker.optimization.max_total_tokens')
        );
        
        $assemblyTime = (microtime(true) - $startTime) * 1000;
        
        $result = new ContextResult(
            messages: $optimizedMessages,
            sources: $context->getSources(),
            tokenCount: $this->tokenOptimizer->countTokens($optimizedMessages),
            assemblyTime: $assemblyTime,
            debugInfo: $this->getDebugInfo($context, $request)
        );
        
        Log::info('🧠 Context assembled', [
            'conversation_id' => $request->conversationId,
            'message_count' => count($optimizedMessages),
            'token_count' => $result->tokenCount,
            'assembly_time_ms' => $assemblyTime,
            'sources_used' => array_keys($result->sources),
        ]);
        
        return $result;
    }

    private function addSystemPrompts(ContextBuilder $context, ContextRequest $request): void
    {
        if (!config('fragments.context_broker.sources.system_prompts.enabled')) {
            return;
        }

        $systemPrompts = $this->systemPrompts->getPromptsForRequest($request);
        
        foreach ($systemPrompts as $prompt) {
            $context->addMessage('system', $prompt['content'], [
                'source' => 'system_prompt',
                'type' => $prompt['type'],
                'priority' => $prompt['priority'] ?? 100,
            ]);
        }
    }

    private function addChatHistory(ContextBuilder $context, ContextRequest $request): void
    {
        if (!config('fragments.context_broker.sources.chat_history.enabled')) {
            return;
        }

        $maxMessages = config('fragments.context_broker.sources.chat_history.max_messages');
        $history = $this->chatHistory->getHistory($request->conversationId, $maxMessages);
        
        foreach ($history as $message) {
            $context->addMessage($message['role'], $message['content'], [
                'source' => 'chat_history',
                'timestamp' => $message['timestamp'],
                'fragment_id' => $message['fragment_id'] ?? null,
            ]);
        }
    }

    private function addRelevantMemory(ContextBuilder $context, ContextRequest $request): void
    {
        if (!config('fragments.context_broker.sources.memory_fragments.enabled')) {
            return;
        }

        $relevantMemories = $this->memoryService->findRelevantMemories(
            $request->userMessage,
            $request->userId,
            config('fragments.context_broker.sources.memory_fragments.max_fragments'),
            config('fragments.context_broker.sources.memory_fragments.relevance_threshold')
        );
        
        foreach ($relevantMemories as $memory) {
            $context->addMessage('system', 
                "Relevant context: {$memory['content']}", 
                [
                    'source' => 'memory',
                    'relevance_score' => $memory['relevance_score'],
                    'memory_type' => $memory['type'],
                    'fragment_id' => $memory['fragment_id'],
                ]
            );
        }
    }

    private function addProjectContext(ContextBuilder $context, ContextRequest $request): void
    {
        if (!$request->projectId || !config('fragments.context_broker.sources.project_context.enabled')) {
            return;
        }

        $projectInfo = $this->projectContext->getContextForProject($request->projectId);
        
        if ($projectInfo['description']) {
            $context->addMessage('system', 
                "Project context: {$projectInfo['description']}", 
                ['source' => 'project_context', 'type' => 'description']
            );
        }
        
        if ($projectInfo['recent_activity']) {
            $context->addMessage('system', 
                "Recent project activity: {$projectInfo['recent_activity']}", 
                ['source' => 'project_context', 'type' => 'activity']
            );
        }
    }
}
```

### **Phase 2: System Prompt Service**
```php
class SystemPromptService
{
    public function getPromptsForRequest(ContextRequest $request): array
    {
        $prompts = [];
        
        // Base system prompt
        $prompts[] = [
            'type' => 'base_personality',
            'priority' => 100,
            'content' => $this->getBasePersonality($request),
        ];
        
        // User-specific prompts
        if ($request->userId) {
            $userPrompts = $this->getUserPrompts($request->userId);
            $prompts = array_merge($prompts, $userPrompts);
        }
        
        // Project-specific prompts
        if ($request->projectId) {
            $projectPrompts = $this->getProjectPrompts($request->projectId);
            $prompts = array_merge($prompts, $projectPrompts);
        }
        
        // Context-specific prompts (tools available, etc.)
        $contextPrompts = $this->getContextualPrompts($request);
        $prompts = array_merge($prompts, $contextPrompts);
        
        return $prompts;
    }
    
    private function getBasePersonality(ContextRequest $request): string
    {
        $personality = "You are Claude, an AI assistant created by Anthropic.";
        
        // Add context about available tools/commands
        if ($this->hasAvailableTools($request)) {
            $personality .= " You have access to various tools and commands that can help you assist the user more effectively.";
        }
        
        // Add project context awareness
        if ($request->projectId) {
            $personality .= " You are helping with a specific project, so keep that context in mind.";
        }
        
        return $personality;
    }
    
    private function getUserPrompts(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) return [];
        
        $prompts = [];
        
        // User preferences
        if ($user->ai_personality_preference) {
            $prompts[] = [
                'type' => 'user_personality',
                'priority' => 90,
                'content' => "User prefers this interaction style: {$user->ai_personality_preference}",
            ];
        }
        
        // User context/bio
        if ($user->work_context) {
            $prompts[] = [
                'type' => 'user_context',
                'priority' => 80,
                'content' => "User's work context: {$user->work_context}",
            ];
        }
        
        return $prompts;
    }
    
    private function getContextualPrompts(ContextRequest $request): array
    {
        $prompts = [];
        
        // Available tools context
        $availableTools = $this->getAvailableTools($request);
        if (!empty($availableTools)) {
            $toolsList = implode(', ', array_keys($availableTools));
            $prompts[] = [
                'type' => 'available_tools',
                'priority' => 70,
                'content' => "You have access to these tools and commands: {$toolsList}. Use them when appropriate to help the user.",
            ];
        }
        
        // Time context
        $prompts[] = [
            'type' => 'temporal_context',
            'priority' => 60,
            'content' => "Current time: " . now()->format('Y-m-d H:i:s T'),
        ];
        
        return $prompts;
    }
}
```

### **Phase 3: Memory Service Integration**
```php
class MemoryService
{
    public function findRelevantMemories(
        string $query, 
        int $userId, 
        int $limit = 5, 
        float $threshold = 0.7
    ): array {
        // Use embeddings to find semantically similar fragments
        $queryEmbedding = $this->generateEmbedding($query);
        
        $relevantFragments = Fragment::where('user_id', $userId)
            ->whereNotNull('embedding')
            ->where('type', '!=', 'log') // Exclude basic log entries
            ->orderByRaw('embedding <-> ?', [$queryEmbedding])
            ->limit($limit * 2) // Get more than needed for filtering
            ->get();
        
        $memories = [];
        foreach ($relevantFragments as $fragment) {
            $similarity = $this->calculateSimilarity($queryEmbedding, $fragment->embedding);
            
            if ($similarity >= $threshold) {
                $memories[] = [
                    'content' => $this->formatMemoryContent($fragment),
                    'relevance_score' => $similarity,
                    'type' => $fragment->type,
                    'fragment_id' => $fragment->id,
                    'created_at' => $fragment->created_at,
                ];
            }
        }
        
        // Sort by relevance and limit
        usort($memories, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);
        return array_slice($memories, 0, $limit);
    }
    
    private function formatMemoryContent(Fragment $fragment): string
    {
        $content = $fragment->message;
        
        // Add context based on fragment type
        if ($fragment->type === 'todo' && isset($fragment->state['status'])) {
            $content = "[{$fragment->state['status']} todo] {$content}";
        }
        
        if ($fragment->tags) {
            $tags = implode(', ', $fragment->tags);
            $content .= " (tags: {$tags})";
        }
        
        return Str::limit($content, 200);
    }
}
```

### **Phase 4: Integration with Chat Controller**
```php
// Enhanced ChatApiController
class ChatApiController extends Controller
{
    public function __construct(
        private ContextBroker $contextBroker
    ) {}

    public function send(Request $req)
    {
        // ... existing validation and setup ...
        
        // Create context request
        $contextRequest = new ContextRequest(
            userMessage: $data['content'],
            conversationId: $conversationId,
            userId: auth()->id(),
            sessionId: $sessionId,
            projectId: $this->getCurrentProjectId(),
            maxTokens: $this->getMaxTokensForModel($useModel),
            attachments: $data['attachments'] ?? []
        );
        
        // Assemble dynamic context instead of hard-coded messages
        $contextResult = $this->contextBroker->assembleContext($contextRequest);
        
        // Cache enhanced session with full context
        app(\App\Actions\CacheChatSession::class)(
            $messageId,
            $contextResult->messages,
            $useProvider,
            $useModel,
            $userFragmentId,
            $conversationId,
            $contextResult->debugInfo // Include debug info for development
        );
        
        return response()->json([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'user_fragment_id' => $userFragmentId,
            'context_info' => config('app.debug') ? [
                'message_count' => count($contextResult->messages),
                'token_count' => $contextResult->tokenCount,
                'assembly_time_ms' => $contextResult->assemblyTime,
                'sources_used' => array_keys($contextResult->sources),
            ] : null,
        ]);
    }
}
```

## Success Criteria
- [ ] Dynamic context assembly replaces hard-coded messages
- [ ] System prompts adapt to user, project, and conversation context  
- [ ] Chat history integration provides conversational continuity
- [ ] Memory/embeddings provide relevant background context
- [ ] Token optimization keeps context within model limits
- [ ] Context assembly completes within 100ms
- [ ] Debug mode provides visibility into context sources
- [ ] Configuration allows fine-tuning context behavior

## Files to Create/Modify
### New Files
- `app/Services/ContextBroker.php`
- `app/Services/SystemPromptService.php`
- `app/Services/ChatHistoryService.php`
- `app/Services/MemoryService.php`
- `app/Services/ProjectContextService.php`
- `app/Services/TokenOptimizer.php`
- `app/DTOs/ContextRequest.php`
- `app/DTOs/ContextResult.php`
- `app/Support/ContextBuilder.php`

### Modified Files
- `app/Http/Controllers/ChatApiController.php`
- `app/Actions/CacheChatSession.php`
- `config/fragments.php`

## Testing Strategy
- Unit tests for context assembly with different configurations
- Integration tests for chat flow with dynamic context
- Performance tests to validate 100ms assembly time
- Memory relevance tests to ensure useful context selection
- Token optimization tests to validate limits are respected
- User experience tests to validate improved response quality