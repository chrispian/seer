# Chat Message Flow: Complete Pipeline Documentation

**Last Updated**: October 14, 2025  
**Purpose**: Document the complete journey of a chat message from user input to final response, including all decision points, model selections, and content mutations.

---

## Overview

When a user sends a message in the chat interface, it flows through multiple layers:

1. **Frontend** → React ChatIsland component
2. **Backend Entry** → ChatApiController  
3. **Session Validation** → RetrieveChatSession action
4. **Fragment Creation** → CreateChatFragment action
5. **Tool-Aware Pipeline** → Router → (optional) ToolSelector → ToolRunner → OutcomeSummarizer → FinalComposer
6. **Response Fragment** → CreateChatFragment (for assistant)
7. **Session Update** → ChatSession::addMessage
8. **Frontend Display** → EventSource streaming back to UI

---

## Phase 1: Frontend → Backend Entry

### 1.1 User Input (`ChatIsland.tsx`)

**Location**: `resources/js/islands/chat/ChatIsland.tsx`

**Trigger**: User types message and presses Enter or clicks Send button

**Process**:
```typescript
const handleSubmit = async (e) => {
  // 1. Create optimistic user message in UI
  const userMessage = { id: tempId, role: 'user', md: content }
  setMessages(m => [...m, userMessage])
  
  // 2. POST to /api/chat/message
  const response = await fetch('/api/chat/message', {
    method: 'POST',
    body: JSON.stringify({ 
      content,           // User's message text
      session_id,        // Current chat session ID
      conversation_id    // UUID for this conversation
    })
  })
  
  // 3. Get message_id from response
  const { message_id, user_fragment_id } = await response.json()
  
  // 4. Open SSE stream to /api/chat/stream/{message_id}
  const eventSource = new EventSource(`/api/chat/stream/${message_id}`)
}
```

**Content Mutations**: None (user text passed as-is)

**Model Selection**: None yet

---

## Phase 2: Backend Entry Point

### 2.1 ChatApiController::message()

**Location**: `app/Http/Controllers/ChatApiController.php:79-237`

**Process**:
```php
public function message(Request $request)
{
    // 1. VALIDATION - Validate input
    $validated = $request->validate([
        'content' => 'required|string|max:10000',
        'session_id' => 'required|integer|exists:chat_sessions,id',
        'conversation_id' => 'required|string|uuid',
    ]);
    
    // 2. CORRELATION - Set up telemetry correlation ID
    $messageId = Str::uuid();
    CorrelationContext::setCorrelationId($messageId);
    
    // 3. SESSION RETRIEVAL - Get chat session
    $chatSession = ChatSession::find($validated['session_id']);
    
    // 4. FRAGMENT CREATION - Create user fragment
    $createChatFragment = app(CreateChatFragment::class);
    $userFragment = $createChatFragment(
        $validated['content'],
        'chat-user',  // Source
        [
            'turn' => 'prompt',
            'conversation_id' => $conversationId,
            'session_id' => $sessionId,
            'tool_aware' => true,  // Tool-aware enabled
        ]
    );
    
    // 5. SESSION MESSAGE STORAGE - Add to session
    $chatSession->addMessage([
        'id' => $userFragment->id,
        'type' => 'user',
        'message' => $validated['content'],
        'fragment_id' => $userFragment->id,
        'created_at' => now()->toISOString(),
    ]);
    
    // 6. RESPONSE - Return message ID for streaming
    return response()->json([
        'message_id' => $messageId,
        'conversation_id' => $conversationId,
        'user_fragment_id' => $userFragment->id,
    ]);
}
```

**Content Mutations**: 
- ✅ User message stored in Fragment
- ✅ User message added to ChatSession->messages JSON

**Model Selection**: None (session already has model selected)

**Queue Behavior**: 
- ❌ No queueing at this stage
- Synchronous: User fragment created immediately
- Returns immediately with message_id

---

## Phase 3: Streaming Response

### 3.1 ChatApiController::stream()

**Location**: `app/Http/Controllers/ChatApiController.php:239-255`

**Process**:
```php
public function stream(string $messageId)
{
    // 1. RETRIEVE SESSION - Get session data from cache
    $session = app(RetrieveChatSession::class)($messageId);
    
    // 2. CHECK TOOL-AWARE - Determine routing
    if ($session['provider'] === 'tool-aware') {
        return $this->streamToolAware($messageId, $session);
    }
    
    // 3. STANDARD STREAMING - Direct LLM streaming (not used currently)
    return new StreamedResponse(...);
}
```

**Content Mutations**: None (just routing)

**Model Selection**: Reads from session cache
- `$session['provider']` - e.g., "tool-aware", "openai", "anthropic"
- `$session['model']` - e.g., "gpt-4o-mini", "qwen/qwen3-coder"

**Queue Behavior**: 
- ❌ No queueing
- Synchronous streaming response

---

### 3.2 ChatApiController::streamToolAware()

**Location**: `app/Http/Controllers/ChatApiController.php:411-519`

**Process**:
```php
protected function streamToolAware(string $messageId, array $session)
{
    return new StreamedResponse(function () use ($messageId, $session) {
        // Set up response buffering
        @ini_set('output_buffering', 'off');
        
        // Get pipeline instance
        $pipeline = app(ToolAwarePipeline::class);
        
        // Extract session details
        $sessionId = $session['session_id'];
        $conversationId = $session['conversation_id'];
        $userMessage = $session['messages'][0]['content'];
        
        // EXECUTE STREAMING PIPELINE - Generator yields events
        foreach ($pipeline->executeStreaming($sessionId, $userMessage, $conversationId, $messageId) as $event) {
            // STREAM EVENT - Send to frontend
            echo 'data: '.json_encode($event)."\n\n";
            @ob_flush();
            @flush();
            
            // TRACK FINAL MESSAGE - Capture for fragment creation
            if ($event['type'] === 'final_message') {
                $finalMessage = $event['message'];
                $usedTools = $event['used_tools'];
                $correlationId = $event['correlation_id'] ?? null;
                $aiProvider = $event['ai_provider'] ?? null;
                $aiModel = $event['ai_model'] ?? null;
            }
        }
        
        // CREATE ASSISTANT FRAGMENT - After streaming completes
        if ($finalMessage) {
            $assistantFragment = $createChatFragment(
                $finalMessage,
                'chat-agent',
                [
                    'turn' => 'completion',
                    'conversation_id' => $conversationId,
                    'session_id' => $sessionId,
                    'tool_aware' => true,
                    'used_tools' => $usedTools,
                    'correlation_id' => $correlationId,
                    'user_fragment_id' => $userFragmentId,
                    'ai_provider' => $aiProvider,
                    'ai_model' => $aiModel,
                ]
            );
            
            // ADD TO SESSION
            $chatSession->addMessage([
                'id' => $assistantFragment->id,
                'type' => 'assistant',
                'message' => $finalMessage,
                'fragment_id' => $assistantFragment->id,
                'created_at' => now()->toISOString(),
                'tool_aware' => true,
                'used_tools' => $usedTools,
            ]);
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
}
```

**Content Mutations**: 
- ✅ Assistant message stored in Fragment (AFTER streaming completes)
- ✅ Assistant message added to ChatSession->messages JSON

**Model Selection**: Passed to pipeline from session

**Queue Behavior**: 
- ❌ No queueing - completely synchronous
- **IMPORTANT**: Streaming response keeps HTTP connection open
- User can navigate away, but pipeline will continue running
- ⚠️ If user closes browser, connection terminates and pipeline stops

**Streaming Details**:
- ✅ ALL responses are streamed via Server-Sent Events (SSE)
- Events yielded by pipeline are sent immediately to frontend
- Response is NOT queued - it's a long-lived HTTP connection

---

## Phase 4: Tool-Aware Pipeline

### 4.1 ToolAwarePipeline::executeStreaming()

**Location**: `app/Services/Orchestration/ToolAware/ToolAwarePipeline.php:62-172`

**Process**:
```php
public function executeStreaming($sessionId, $userMessage, $conversationId, $messageId): \Generator
{
    // 1. START - Yield pipeline start event
    yield ['type' => 'pipeline_start', 'pipeline_id' => $pipelineId];
    
    // 2. CONTEXT ASSEMBLY - Gather conversation history, agent prefs, tools
    $context = $this->contextBroker->assemble($sessionId, $userMessage);
    yield ['type' => 'context_assembled', ...];
    
    // 3. ROUTER DECISION - Should we use tools?
    $decision = $this->router->decide($context);
    yield ['type' => 'router_decision', 'needs_tools' => $decision->needs_tools, ...];
    
    // 4A. NO TOOLS PATH - Direct response
    if (!$decision->needs_tools) {
        $message = $this->composer->compose($context, null, null);
        yield ['type' => 'final_message', 'message' => $message, 'used_tools' => false];
        yield ['type' => 'done'];
        return;
    }
    
    // 4B. TOOLS PATH - Select and execute tools
    $plan = $this->toolSelector->selectTools($decision->high_level_goal, $context);
    yield ['type' => 'tool_plan', 'selected_tools' => $plan->selected_tool_ids];
    
    // Execute tools (streaming)
    foreach ($this->toolRunner->executeStreaming($plan, ...) as $event) {
        yield $event;  // Forward tool execution events
    }
    
    // Summarize results
    $summary = $this->summarizer->summarize($trace, $context);
    yield ['type' => 'summary', ...];
    
    // Compose final response
    $message = $this->composer->compose($context, $summary, $trace->correlation_id);
    yield ['type' => 'final_message', 'message' => $message, 'used_tools' => true];
    
    yield ['type' => 'done'];
}
```

**Content Mutations**: 
- ⚠️ Context summary created (condensed conversation history)
- ⚠️ Tool results summarized
- ✅ Final message composed from summary

**Model Selection**: Multiple stages (see below)

**Queue Behavior**: 
- ❌ No queueing - generator executes synchronously
- Each `yield` sends event immediately to streaming response

---

### 4.2 ContextBroker::assemble()

**Location**: `app/Services/Orchestration/ToolAware/ContextBroker.php:19-30`

**Process**:
```php
public function assemble($sessionId, $userMessage): ContextBundle
{
    // 1. BUILD CONVERSATION SUMMARY
    $conversationSummary = $this->buildConversationSummary($sessionId);
    
    // 2. EXTRACT AGENT PREFERENCES (MODEL SELECTION SOURCE)
    $agentPrefs = $this->extractAgentPreferences($sessionId);
    
    // 3. PREVIEW RELEVANT TOOLS
    $toolPreview = $this->previewRelevantTools($userMessage);
    
    return new ContextBundle(
        user_message: $userMessage,
        conversation_summary: $conversationSummary,
        agent_prefs: $agentPrefs,  // Contains model_provider & model_name
        tool_registry_preview: $toolPreview
    );
}
```

**extractAgentPreferences()** - **PRIMARY MODEL SELECTION SOURCE**:
```php
protected function extractAgentPreferences($sessionId): array
{
    $session = ChatSession::find($sessionId);
    
    return [
        'model_provider' => $session->model_provider,  // e.g., "openai", "openrouter"
        'model_name' => $session->model_name,          // e.g., "gpt-4o-mini", "qwen/qwen3-coder"
    ];
}
```

**buildConversationSummary()**:
```php
protected function buildConversationSummary($sessionId): string
{
    $session = ChatSession::find($sessionId);
    $messages = $session->messages ?? [];
    
    // Take last 5 messages
    $recentMessages = array_slice($messages, -5);
    
    // Format: "User: ...\nAssistant: ...\n"
    $summary = '';
    foreach ($recentMessages as $msg) {
        $role = $msg['type'] === 'user' ? 'User' : 'Assistant';
        $content = Str::limit($msg['message'], 200);
        $summary .= "{$role}: {$content}\n";
    }
    
    // Limit total length
    $maxLength = Config::get('fragments.tool_aware_turn.context.max_summary_length', 600);
    return Str::limit($summary, $maxLength);
}
```

**Content Mutations**: 
- ✅ Conversation history condensed to last 5 messages
- ✅ Each message limited to 200 chars
- ✅ Total summary limited to 600 chars

**Model Selection**: 
- ✅ Reads from ChatSession (stored when user selects model in UI)
- **Source of truth**: `$session->model_provider` and `$session->model_name`
- These are passed to ALL pipeline components via `$context->agent_prefs`

---

### 4.3 Router::decide()

**Location**: `app/Services/Orchestration/ToolAware/Router.php:13-63`

**Model Selection Logic**:
```php
// 1. PREFERENCE: Use session model if available
$model = $context->agent_prefs['model_name'] 
    ?? Config::get('fragments.tool_aware_turn.models.router', 'gpt-4o-mini');

// 2. PREFERENCE: Use session provider if available  
$provider = $context->agent_prefs['model_provider'] 
    ?? $this->getProviderForModel($model);

// 3. CALL LLM
$response = $this->callLLM($prompt, $model, $provider);
```

**Model Selection Order**:
1. ✅ **First**: Session's `model_provider` and `model_name` (from user selection)
2. ⚠️ **Fallback**: Config default: `fragments.tool_aware_turn.models.router` = `"gpt-4o-mini"`
3. ⚠️ **Last resort**: Infer provider from model name using `getProviderForModel()`

**callLLM() Implementation**:
```php
protected function callLLM(string $prompt, string $model, ?string $provider = null): string
{
    // Use provided provider or infer
    if ($provider === null) {
        $provider = $this->getProviderForModel($model);
    }
    
    $providerManager = app(AIProviderManager::class);
    
    // Generate text with explicit provider/model in context
    $response = $providerManager->generateText($fullPrompt, [
        'request_type' => 'tool_routing',
        'provider' => $provider,      // Explicit provider
        'model' => $model,             // Explicit model
    ], [
        'temperature' => 0.1,
        'top_p' => 0.95,
        'max_tokens' => 500,
    ]);
    
    return $response['text'];
}
```

**Content Mutations**: 
- ✅ LLM response parsed as JSON
- ✅ Extracts: `needs_tools` (bool), `rationale` (string), `high_level_goal` (string)

**Prism Integration**:
- ✅ `AIProviderManager` checks `config('fragments.models.use_prism')`
- ✅ If true, uses `PrismProviderAdapter` instead of custom providers
- ✅ All telemetry logs show "(Prism)" marker

---

### 4.4 ToolSelector::selectTools()

**Location**: `app/Services/Orchestration/ToolAware/ToolSelector.php`

**Model Selection Logic**: (Same pattern as Router)
```php
$model = $context->agent_prefs['model_name'] 
    ?? Config::get('fragments.tool_aware_turn.models.candidate_selector', 'gpt-4o-mini');

$provider = $context->agent_prefs['model_provider'] 
    ?? $this->getProviderForModel($model);
```

**Config Fallback**: `fragments.tool_aware_turn.models.candidate_selector` = `"gpt-4o-mini"`

**Content Mutations**: 
- ✅ LLM response parsed as JSON
- ✅ Extracts: `selected_tool_ids` (array), creates ExecutionPlan

---

### 4.5 ToolRunner::executeStreaming()

**Location**: `app/Services/Orchestration/ToolAware/ToolRunner.php`

**Process**:
```php
public function executeStreaming($plan, ...): \Generator
{
    foreach ($plan->steps as $step) {
        // Yield tool start event
        yield ['type' => 'tool_start', 'tool_id' => $step->tool_id];
        
        // Execute tool (synchronously)
        $result = $this->invokeTool($step->tool_id, $step->args, ...);
        
        // Yield tool result event
        yield [
            'type' => 'tool_result',
            'tool_id' => $step->tool_id,
            'result' => $result,
            'success' => $result['success'],
        ];
    }
    
    // Yield execution complete
    yield ['type' => 'execution_complete', 'trace' => ...];
}
```

**Content Mutations**: 
- ✅ Tool execution results captured
- ⚠️ Results may be large (file contents, search results, etc.)

**Model Selection**: None (tools don't use LLMs)

**Queue Behavior**: 
- ❌ No queueing - tools execute synchronously
- Some tools may have approval workflows (separate queue handling)

---

### 4.6 OutcomeSummarizer::summarize()

**Location**: `app/Services/Orchestration/ToolAware/OutcomeSummarizer.php`

**Model Selection Logic**: (Same pattern)
```php
$model = $context->agent_prefs['model_name']
    ?? Config::get('fragments.tool_aware_turn.models.summarizer', 'gpt-4o-mini');

$provider = $context->agent_prefs['model_provider']
    ?? $this->getProviderForModel($model);
```

**Config Fallback**: `fragments.tool_aware_turn.models.summarizer` = `"gpt-4o-mini"`

**Content Mutations**: 
- ✅ Tool results summarized into structured JSON
- ✅ Extracts: `short_summary`, `key_facts`, `links`, `confidence_score`
- ⚠️ Large tool outputs compressed to ~300 chars per fact

---

### 4.7 FinalComposer::compose()

**Location**: `app/Services/Orchestration/ToolAware/FinalComposer.php`

**Model Selection Logic**: (Same pattern)
```php
// compose() method (when tools were used)
$model = $context->agent_prefs['model_name'] 
    ?? Config::get('fragments.tool_aware_turn.models.composer', 'gpt-4o');

$provider = $context->agent_prefs['model_provider'] 
    ?? $this->getProviderForModel($model);

// directResponse() method (no tools used)
$model = $context->agent_prefs['model_name'] 
    ?? Config::get('fragments.tool_aware_turn.models.composer', 'gpt-4o');

$provider = $context->agent_prefs['model_provider'] 
    ?? $this->getProviderForModel($model);
```

**Config Fallback**: `fragments.tool_aware_turn.models.composer` = `"gpt-4o"`

**Content Mutations**: 
- ✅ Final user-facing message generated
- ✅ Incorporates summary, conversation context, user's question
- ✅ Formatted in markdown

---

## Model Selection Summary

### Hierarchy (All Components)

1. **✅ FIRST CHOICE - Session Preferences** (Set by user in UI)
   ```php
   $model = $context->agent_prefs['model_name']      // From ChatSession
   $provider = $context->agent_prefs['model_provider'] // From ChatSession
   ```

2. **⚠️ SECOND CHOICE - Config Defaults** (Per-component)
   ```php
   Config::get('fragments.tool_aware_turn.models.router')            // gpt-4o-mini
   Config::get('fragments.tool_aware_turn.models.candidate_selector') // gpt-4o-mini
   Config::get('fragments.tool_aware_turn.models.summarizer')        // gpt-4o-mini
   Config::get('fragments.tool_aware_turn.models.composer')          // gpt-4o
   ```

3. **⚠️ LAST RESORT - Provider Inference** (Fallback logic)
   ```php
   getProviderForModel($model):
     - starts with "gpt-" or "o1-"    → "openai"
     - starts with "claude-"          → "anthropic"  
     - contains "/"                   → extract prefix (e.g., "qwen/..." → "qwen")
     - default                        → config('fragments.models.default_provider')
   ```

### When Session Model is Used

✅ **Always used** when:
- User has selected a model in the UI
- ChatSession has `model_provider` and `model_name` set
- Context is assembled with session ID

❌ **Fallback to config** when:
- No session ID provided (rare - API calls without session)
- Session model fields are null (new session, not yet selected)

---

## Content Mutation Points

### User Message Path

1. **Input**: User types "What's in the file docs/test.md?"
2. **ChatApiController**: Stored as-is → Fragment (source: chat-user)
3. **ContextBroker**: Last 5 messages condensed → 200 chars each → 600 char total summary
4. **Router**: User message + summary → LLM prompt → JSON response
5. **ToolSelector**: Goal + tools → LLM prompt → JSON response (tool IDs)
6. **ToolRunner**: Executes tools → Raw results (file contents, etc.)
7. **OutcomeSummarizer**: Tool results → LLM prompt → JSON summary (key facts, links)
8. **FinalComposer**: Summary + context → LLM prompt → Markdown response
9. **ChatApiController**: Final message → Fragment (source: chat-agent)

### Assistant Message Path

1. **FinalComposer**: Generates markdown text
2. **StreamedResponse**: Yields `final_message` event with full text
3. **ChatApiController**: Creates Fragment with full message
4. **Frontend**: Displays markdown via `react-markdown`

---

## Queue Behavior Analysis

### Current Implementation: NO QUEUING

**Why?**
- Streaming SSE responses require open HTTP connection
- Pipeline executes synchronously in same request
- User sees real-time progress (tool execution, composition)

**Implications**:
- ✅ Real-time feedback to user
- ✅ Low latency (no queue delay)
- ❌ Connection closes if user navigates away → Pipeline stops
- ❌ Server resources held during entire pipeline execution
- ❌ Long pipelines (30+ seconds) risk timeout

### Potential Future Queue Architecture

**Could queue**:
- After user fragment created
- Dispatch job: `ProcessChatMessage::dispatch($sessionId, $messageId, $userMessage)`
- Job runs pipeline, stores assistant fragment
- Broadcast completion event to frontend via WebSocket/Pusher

**Benefits**:
- ✅ User can navigate away, response delivered when ready
- ✅ Server resources freed immediately
- ✅ Failed jobs can retry

**Challenges**:
- ❌ No real-time streaming (unless WebSocket used for progress)
- ❌ Higher complexity (queue workers, broadcasting)
- ❌ User doesn't know if message is processing

---

## Event Types Streamed to Frontend

### Current Events

1. `pipeline_start` - Pipeline begins
2. `context_assembled` - Context ready
3. `router_decision` - Tools needed? Goal identified?
4. `tool_plan` - Tools selected
5. `tool_start` - Tool execution begins
6. `tool_result` - Tool completed (success/failure)
7. `summarizing` - Summarizing tool results
8. `summary` - Summary complete
9. `composing` - Final composition begins
10. `final_message` - Complete response ready
11. `done` - Pipeline complete
12. `error` - Pipeline failed

### Frontend Handling

**Location**: `resources/js/islands/chat/ChatIsland.tsx:445-520`

```typescript
eventSource.onmessage = (e) => {
  const data = JSON.parse(e.data)
  
  // Show tool execution progress
  if (data.type === 'tool_result') {
    acc += `✓ ${data.tool_id}\n`
    // Update temporary assistant message with accumulator
  }
  
  // Replace temp message with final response
  if (data.type === 'final_message') {
    acc = data.message
    // Update message in state
  }
}
```

**Display Pattern**:
- Creates temporary assistant message during pipeline
- Updates temp message with tool execution progress
- Replaces with final message when complete

---

## Configuration Reference

### Tool-Aware Pipeline Models

**Location**: `config/fragments.php`

```php
'tool_aware_turn' => [
    'models' => [
        'router' => env('TOOL_AWARE_ROUTER_MODEL', 'gpt-4o-mini'),
        'candidate_selector' => env('TOOL_AWARE_SELECTOR_MODEL', 'gpt-4o-mini'),
        'summarizer' => env('TOOL_AWARE_SUMMARIZER_MODEL', 'gpt-4o-mini'),
        'composer' => env('TOOL_AWARE_COMPOSER_MODEL', 'gpt-4o'),
    ],
],
```

**Defaults**:
- Router: `gpt-4o-mini` (faster, cheaper for routing decisions)
- Tool Selector: `gpt-4o-mini` (tool selection is simple)
- Summarizer: `gpt-4o-mini` (summarization is straightforward)
- Composer: `gpt-4o` (final response quality matters)

### Global Model Defaults

```php
'models' => [
    'use_prism' => env('AI_USE_PRISM', false),
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'default_text_model' => env('AI_DEFAULT_TEXT_MODEL', 'gpt-4o-mini'),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'ollama'),
    'fallback_text_model' => env('AI_FALLBACK_TEXT_MODEL', 'llama3:latest'),
],
```

---

## Known Issues

### 1. Anthropic Direct Credits Error

**Status**: Open issue (not P1)  
**Symptom**: "Your credit balance is too low" despite having credits  
**Likely Cause**: API key or account configuration issue  
**Workaround**: Use OpenRouter with Anthropic models  

### 2. Provider Inference from Model Name

**Status**: ✅ Fixed (Oct 14, 2025)  
**Previous Issue**: Models like `qwen/qwen3-coder` extracted provider as `"qwen"` instead of `"openrouter"`  
**Fix**: All components now use `model_provider` from session first, only infer as fallback  

---

## Testing Scenarios

### Scenario 1: OpenAI Direct

**Setup**: User selects "OpenAI: GPT-4o Mini"  
**Expected**:
- Session: `model_provider="openai"`, `model_name="gpt-4o-mini"`
- All components use OpenAI with gpt-4o-mini
- Logs show: `"provider":"openai","model":"gpt-4o-mini"`

### Scenario 2: OpenRouter Model

**Setup**: User selects "OpenRouter: Qwen 3 Coder"  
**Expected**:
- Session: `model_provider="openrouter"`, `model_name="qwen/qwen3-coder"`
- All components use OpenRouter with qwen/qwen3-coder
- Logs show: `"provider":"openrouter","model":"qwen/qwen3-coder"`

### Scenario 3: Anthropic Direct

**Setup**: User selects "Anthropic: Claude Sonnet 4.5"  
**Expected**:
- Session: `model_provider="anthropic"`, `model_name="claude-sonnet-4-5-20250929"`
- All components use Anthropic with claude-sonnet-4-5-20250929
- Logs show: `"provider":"anthropic","model":"claude-sonnet-4-5-20250929"`
- ⚠️ Currently fails with credits error (known issue)

### Scenario 4: No Session Model (Fallback)

**Setup**: New session, model not yet selected  
**Expected**:
- Router: Uses config default `gpt-4o-mini` with `openai`
- Selector: Uses config default `gpt-4o-mini` with `openai`
- Summarizer: Uses config default `gpt-4o-mini` with `openai`
- Composer: Uses config default `gpt-4o` with `openai`

---

## Prism Integration Notes

### When Prism is Enabled

**Config**: `AI_USE_PRISM=true` in `.env`

**Behavior**:
- `AIProviderManager::createProvider()` returns `PrismProviderAdapter` for all providers
- Prism handles provider-specific API details (authentication, request format, etc.)
- Telemetry logs include "(Prism)" marker
- All providers go through Prism's unified interface

### When Prism is Disabled

**Config**: `AI_USE_PRISM=false` in `.env`

**Behavior**:
- Custom provider implementations used: `OpenAIProvider`, `AnthropicProvider`, etc.
- Each provider handles own API format
- Direct API calls (not through Prism abstraction)

---

## Next Steps

### Proposed Enhancements

1. **✅ Add streaming status messages** - Show Router/Selector/Summarizer/Composer progress
2. **🔄 Investigate Anthropic credits issue** - Debug API authentication
3. **📊 Add telemetry for model selection decisions** - Track fallback usage
4. **⚡ Consider queue architecture** - Allow users to navigate away during processing
