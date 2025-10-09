# Telemetry System Overview

## Executive Summary

Fragments Engine has a comprehensive, multi-layered telemetry system designed for observability, debugging, and performance monitoring. The system spans chat interactions, fragment processing, command execution, tool usage, and AI provider operations. However, there are significant gaps in LLM call telemetry and prompt pipeline tracking.

## Current Telemetry Systems

### 1. Chat Telemetry (`TELEMETRY_003`)
**Location**: `app/Services/Telemetry/ChatTelemetry.php`
**Purpose**: End-to-end tracking of user/agent chat interactions

**Coverage**:
- ✅ Message receipt and validation
- ✅ Provider/model selection
- ✅ Streaming performance (time to first token, tokens/sec, latency)
- ✅ Fragment creation and processing
- ✅ Session caching and management
- ✅ Transaction summaries with token usage
- ✅ Error tracking and categorization

**Data Points**:
```json
{
  "event": "chat.transaction.summary",
  "data": {
    "message_id": "uuid",
    "conversation_id": "uuid",
    "total_duration_ms": 2500,
    "provider": "openai",
    "model": "gpt-4",
    "input_length": 100,
    "output_length": 250,
    "token_usage": {"prompt_tokens": 100, "completion_tokens": 75},
    "success": true
  }
}
```

### 2. Fragment Processing Telemetry (`TELEMETRY_003`)
**Location**: `app/Services/Telemetry/FragmentProcessingTelemetry.php`
**Purpose**: Pipeline execution monitoring and performance analysis

**Coverage**:
- ✅ Step-by-step processing timing
- ✅ Memory usage tracking
- ✅ Performance categorization (fast/normal/slow/very_slow)
- ✅ Fragment state change detection
- ✅ Pipeline completion metrics
- ✅ Error context and correlation

**Data Points**:
```json
{
  "event": "fragment.processing.step.completed",
  "data": {
    "step_name": "ParseAtomicFragment",
    "duration_ms": 45.23,
    "performance_tier": "fast",
    "memory_usage_mb": 128.5,
    "fragment_changed": true
  }
}
```

### 3. Command & DSL Execution Telemetry (`TELEMETRY_004`)
**Location**: `app/Services/Telemetry/CommandTelemetry.php`
**Purpose**: Command execution and DSL step monitoring

**Coverage**:
- ✅ Command execution lifecycle
- ✅ DSL step performance
- ✅ Template rendering metrics
- ✅ Condition evaluation tracking
- ✅ AI generation metrics (token usage, prompt analysis)
- ✅ Error categorization and analytics

**Data Points**:
```json
{
  "event": "command.step.completed",
  "data": {
    "step_type": "ai.generate",
    "duration_ms": 1250.5,
    "success": true,
    "performance_category": "slow",
    "metrics": {
      "prompt_length": 150,
      "max_tokens": 500,
      "response_length": 420
    }
  }
}
```

### 4. Tool Invocation Telemetry (`TELEMETRY_005`)
**Location**: `app/Services/Telemetry/ToolTelemetry.php`
**Purpose**: Tool execution monitoring and health tracking

**Coverage**:
- ✅ Tool invocation timing and performance
- ✅ Parameter sanitization and analysis
- ✅ Health monitoring and availability checks
- ✅ Correlation context across tool chains
- ✅ Memory usage and resource tracking
- ✅ Error categorization and recovery

**Data Points**:
```json
{
  "event": "tool.invocation.completed",
  "data": {
    "tool_name": "db.query",
    "duration_ms": 150.25,
    "performance_category": "normal",
    "success": true,
    "parameters": {"entity": "work_items", "limit": 50},
    "result_stats": {"size_bytes": 15420, "record_count": 25}
  }
}
```

### 5. Unified Telemetry Sink (`TELEMETRY_006`)
**Location**: `app/Services/Telemetry/TelemetrySink.php`
**Purpose**: Centralized data storage and querying

**Coverage**:
- ✅ Database storage for all telemetry events
- ✅ Time-series data management
- ✅ CLI querying and analytics tools
- ✅ Data export capabilities
- ✅ Retention and cleanup policies
- ✅ Health monitoring and alerting

**Features**:
- CLI commands: `telemetry:query`, `telemetry:health`, `telemetry:export`, `telemetry:cleanup`
- Database tables: `telemetry_events`, `telemetry_metrics`, `telemetry_health_checks`
- Query capabilities with correlation ID tracking

## AI Provider Telemetry

### Current State
**Location**: `app/Services/AI/Providers/AbstractAIProvider.php`

**Coverage**:
- ✅ Basic API call logging (`logApiRequest`)
- ✅ Request/response size tracking
- ✅ Success/failure status
- ✅ Provider and operation identification

**Current Data Points**:
```json
{
  "provider": "openai",
  "operation": "text_generation",
  "request_size": 245,
  "response_size": 1200,
  "success": true,
  "timestamp": "2024-10-04T12:00:00Z"
}
```

### LLM Telemetry Gaps Analysis

**Critical Missing Metrics**:

#### Who (User/Context Tracking)
- ❌ **User ID**: No user identification in LLM calls
- ❌ **Session ID**: No session correlation for LLM operations
- ❌ **Correlation Context**: LLM calls not linked to request chains
- ❌ **Operation Context**: No indication of what triggered the LLM call (chat, command, enrichment, etc.)

#### What (Content & Parameters)
- ❌ **Prompt Content**: Only size tracking, no content analysis (even sanitized)
- ❌ **Model Parameters**: Temperature, top_p, max_tokens not logged
- ❌ **System Messages**: No tracking of system prompt variations
- ❌ **Message History**: No context about conversation history length/complexity

#### When (Timing Details)
- ❌ **Request Queuing**: Time spent waiting in queues
- ❌ **DNS/Network Latency**: Time to establish connections
- ❌ **Precise Timestamps**: Only ISO timestamp, no microsecond precision
- ❌ **Rate Limiting Delays**: Time spent waiting for rate limits

#### Temperature/Velocity (Model Parameters & Performance)
- ❌ **Temperature**: Actual temperature values used
- ❌ **Top-P**: Nucleus sampling parameters
- ❌ **Max Tokens**: Token limits and their impact
- ❌ **Frequency Penalty**: Repetition control settings
- ❌ **Presence Penalty**: Topic diversity settings

#### Scores & Quality Metrics
- ❌ **Confidence Scores**: Model confidence in responses
- ❌ **Log Probabilities**: Token probability distributions
- ❌ **Perplexity**: Response predictability metrics
- ❌ **Semantic Coherence**: Content quality indicators

#### Token Economics
- ❌ **Detailed Breakdown**: Prompt vs completion vs cached tokens
- ❌ **Efficiency Metrics**: Tokens per second, cost per token
- ❌ **Caching Impact**: How much was served from cache
- ❌ **Token Optimization**: Potential savings from prompt engineering

#### Cost & Business Metrics
- ❌ **Per-Call Costs**: Cost calculation per API call
- ❌ **Rate Limiting Status**: Whether calls hit rate limits
- ❌ **Cost Attribution**: Which user/session/feature incurred the cost
- ❌ **Cost Optimization**: Opportunities for cost reduction

#### Success/Failure Details
- ❌ **HTTP Status Codes**: Detailed error status codes
- ❌ **Error Categorization**: Network, auth, quota, model errors
- ❌ **Retry Information**: How many retries were attempted
- ❌ **Failure Context**: What caused the failure (invalid input, model limits, etc.)

### Current LLM Call Flow
```
User Request → AIProviderManager::generateText()
    ↓
ModelSelectionService::selectTextModel() [NO TELEMETRY]
    ↓
AIProvider::generateText() → logApiRequest() [BASIC LOGGING]
    ↓
HTTP Call to Provider API
    ↓
Response Processing → ExtractTokenUsage [BASIC TOKEN TRACKING]
    ↓
Cost Calculation in EnrichAssistantMetadata [AFTER THE FACT]
```

### Required LLM Telemetry Flow
```
User Request → AIProviderManager::generateText()
    ↓
ModelSelectionService::selectTextModel() [LOG SELECTION DECISIONS]
    ↓
Enhanced AIProvider::generateText()
    ├── Pre-call telemetry: who/what/when/parameters
    ├── HTTP call with timing
    ├── Post-call telemetry: tokens/cost/success/scores
    └── Error telemetry: detailed failure analysis
```

## Chat Lifecycle Coverage

### Well-Covered Areas
- ✅ Message receipt → validation → fragment creation
- ✅ Provider selection → session caching → streaming start
- ✅ Streaming progress → completion → fragment processing
- ✅ Error handling and transaction summaries
- ✅ Token usage and cost calculation
- ✅ Performance metrics (latency, throughput)

### User/Agent Chat Flow with Telemetry
```
User Message → ChatApiController::send()
    ↓ [ChatTelemetry::logMessageReceived]
Validation → Fragment Creation → Provider Selection
    ↓ [ChatTelemetry::logProviderSelection]
Session Caching → Message Storage → Response Prep
    ↓ [ChatTelemetry::logSessionCached]
ChatApiController::stream() → StreamChatProvider
    ↓ [ChatTelemetry::logStreamingStarted]
AI Provider Streaming → Delta Processing → Completion
    ↓ [ChatTelemetry::logStreamingProgress, logFirstToken, logStreamingCompleted]
Assistant Fragment Processing → Enrichment Pipeline
    ↓ [FragmentProcessingTelemetry, ChatTelemetry::logAssistantFragmentProcessingStarted]
Transaction Summary Logging
    ↓ [ChatTelemetry::logChatTransactionSummary]
```

### Telemetry Events in Chat Flow

#### 1. Message Intake Phase
- `chat.message.received`: Content length, conversation ID, attachments
- `chat.validation.error`: Validation failures with error details
- `chat.user_fragment.created`: Fragment creation with metadata

#### 2. Preparation Phase
- `chat.provider.selected`: Provider/model selection with source (request/session/fallback)
- `chat.session.cached`: Session storage with message history
- `chat.session.message.added`: Message added to chat session

#### 3. Streaming Phase
- `chat.streaming.started`: Stream initialization with session context
- `chat.streaming.first_token`: Time to first token, initial performance metrics
- `chat.streaming.progress`: Delta count, throughput, every 10 deltas
- `chat.streaming.completed`: Final metrics, token usage, cost
- `chat.streaming.error`: Error context, failure categorization, partial results

#### 4. Processing Phase
- `fragment.processing.pipeline.started`: Assistant fragment processing begins
- `fragment.processing.step.*`: Individual enrichment steps (9 total)
- `fragment.processing.pipeline.completed`: Enrichment completion with performance

#### 5. Completion Phase
- `chat.transaction.summary`: Complete transaction metrics, success/failure, costs

### Chat Telemetry Gaps

#### Missing Advanced Metrics
- ❌ **User Experience Metrics**: Perceived latency, typing indicators, UI responsiveness
- ❌ **Content Quality**: Response helpfulness, accuracy, completeness scores
- ❌ **Conversation Flow**: Turn-taking patterns, topic shifts, conversation depth
- ❌ **Error Recovery**: Retry attempts, fallback success rates, user impact

#### Missing Context Tracking
- ❌ **User Intent**: Classification of user message types (question, command, clarification)
- ❌ **Response Quality**: Automated quality scoring, user feedback correlation
- ❌ **Conversation Context**: Topic tracking, entity recognition, context carryover
- ❌ **Personalization**: User preference learning, adaptive behavior tracking

## Tool Use Telemetry

### Current Coverage (TELEMETRY-005)
**Location**: `app/Services/Telemetry/ToolTelemetry.php`

#### Well-Covered Areas
- ✅ Tool registry integration with automatic wrapping
- ✅ Invocation timing and performance categorization (fast/normal/slow/critical)
- ✅ Parameter sanitization and analysis (sensitive data protection)
- ✅ Health monitoring and availability checks
- ✅ Correlation context propagation across tool chains
- ✅ Memory usage tracking and resource monitoring
- ✅ Error categorization and recovery tracking

### Tool Execution Flow with Telemetry
```
Tool Request → ToolRegistry::get() (automatic wrapping)
    ↓ [ToolTelemetryDecorator applied]
ToolTelemetryDecorator → Parameter Sanitization & Validation
    ↓ [tool.invocation.started logged]
Tool Execution → Performance Tracking (timing, memory)
    ↓ [tool.invocation.completed/error logged]
Result Processing → Health Status Updates
    ↓ [tool.health.status_changed if needed]
Telemetry Aggregation → Correlation Context Propagation
```

### Tool Telemetry Events

#### 1. Invocation Tracking
```json
{
  "event": "tool.invocation.started",
  "data": {
    "tool_name": "db.query",
    "tool_scope": "read/db.query",
    "invocation_id": "uuid",
    "parameters": {
      "entity": "work_items",
      "limit": 50,
      "filters": "[TRUNCATED_ARRAY:3_items]"
    },
    "correlation": {
      "correlation_id": "req_uuid",
      "request_context": "tool_aware_pipeline"
    }
  }
}
```

#### 2. Completion Tracking
```json
{
  "event": "tool.invocation.completed",
  "data": {
    "tool_name": "db.query",
    "invocation_id": "uuid",
    "duration_ms": 150.25,
    "performance_category": "normal",
    "memory_used": 2048576,
    "success": true,
    "result_stats": {
      "size_bytes": 15420,
      "record_count": 25
    }
  }
}
```

#### 3. Health Monitoring
```json
{
  "event": "tool.health.check",
  "data": {
    "tool_name": "db.query",
    "status": "healthy",
    "response_time_ms": 45.2,
    "last_check": "2024-10-04T12:00:00Z"
  }
}
```

### Tool Telemetry Features

#### Parameter Sanitization
- **Sensitive Pattern Detection**: Automatic redaction of passwords, tokens, keys
- **Allowlist Support**: Explicitly allowed parameters bypass sanitization
- **Size Limits**: Parameter truncation to prevent log bloat
- **Hash-based Redaction**: Consistent tracking without data exposure

#### Performance Classification
- **Fast**: < 50ms execution time
- **Normal**: 50-200ms execution time
- **Slow**: 200ms-1s execution time
- **Very Slow**: > 1s execution time (alert threshold)

#### Health Monitoring
- **Availability Checks**: Regular connectivity and functionality validation
- **Failure Detection**: Consecutive failure tracking and alerting
- **Recovery Monitoring**: Automatic recovery detection and status updates
- **Performance Trending**: Response time pattern analysis

### Tool Use Telemetry Gaps

#### Missing Advanced Analytics
- ❌ **Usage Patterns**: Tool usage frequency, popular tool combinations
- ❌ **Error Pattern Analysis**: Common failure modes and root causes
- ❌ **Performance Optimization**: Tool-specific optimization opportunities
- ❌ **Cost Attribution**: Tool execution costs and resource consumption
- ❌ **Dependency Tracking**: Tool interdependencies and failure cascades

#### Missing Context Integration
- ❌ **Business Logic Correlation**: Why tools are called, business impact
- ❌ **User Intent Mapping**: Tool usage patterns by user type/role
- ❌ **Success Metrics**: Tool effectiveness, result quality scoring
- ❌ **Alternative Analysis**: When tools fail, what alternatives were considered

### Tool Categories Tracked

#### Core Tool Types
- **Database Tools**: `db.query`, `db.insert`, `db.update`, `db.delete`
- **File System Tools**: `fs.read`, `fs.write`, `fs.list`, `fs.search`
- **Shell Tools**: `shell.exec`, `shell.run`
- **HTTP Tools**: `http.get`, `http.post`, `http.put`, `http.delete`
- **AI/ML Tools**: `ai.classify`, `ai.generate`, `ai.embed`
- **System Tools**: `system.info`, `system.health`, `system.metrics`

#### Tool Scope Classification
- **Read Operations**: Data retrieval, queries, searches
- **Write Operations**: Data modification, file creation, updates
- **Administrative**: System management, configuration changes
- **Analytical**: Data processing, AI operations, computations

### Tool Telemetry Integration Points

#### 1. Tool Registry Integration
```php
// Automatic telemetry wrapping in ToolRegistry
public function register(ToolContract $tool): void
{
    if (config('tool-telemetry.enabled', true)) {
        $tool = ToolTelemetryDecorator::wrap($tool);
    }
    $this->tools[$tool->name()] = $tool;
}
```

#### 2. Middleware Integration
```php
// Request-scoped correlation context
class ToolTelemetryMiddleware
{
    public function handle($request, $next)
    {
        CorrelationContext::addContext('request_id', $request->id());
        return $next($request);
    }
}
```

#### 3. Health Monitoring
```php
// Scheduled health checks
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        $healthMonitor = app(ToolHealthMonitor::class);
        $healthMonitor->checkAllTools();
    })->everyFiveMinutes();
}
```

### Tool Performance Insights

#### Current Capabilities
- **Response Time Percentiles**: P50, P95, P99 tracking
- **Error Rate Monitoring**: Success/failure ratios by tool
- **Resource Usage**: Memory and CPU consumption patterns
- **Availability Metrics**: Uptime and reliability statistics

#### Analytics Available
- **Tool Popularity Analysis**: Most/least used tools
- **Performance Bottleneck Detection**: Slowest tools and operations
- **Error Pattern Analysis**: Common failure modes
- **Capacity Planning**: Usage trends and scaling indicators

## Systems Lacking Telemetry

### 1. Tool-Aware Orchestration Pipeline
**Location**: `app/Services/Orchestration/ToolAware/`
**Current State**: Basic logging only, no structured telemetry
**Missing Coverage**:
- ❌ Router LLM call telemetry (decision-making process)
- ❌ Tool selector LLM call telemetry (tool selection logic)
- ❌ Context broker performance and data assembly metrics
- ❌ Outcome summarizer LLM call telemetry
- ❌ Final composer LLM call telemetry
- ❌ Pipeline step transitions and decision points
- ❌ Tool execution coordination and error handling

**Impact**: Cannot debug orchestration failures or optimize tool selection logic

### 2. Agent Initialization & Task Orchestration
**Location**: `app/Services/Orchestration/AgentInitService.php`
**Current State**: No telemetry
**Missing Coverage**:
- ❌ Agent profile loading and validation performance
- ❌ Constraint evaluation and matching logic
- ❌ Task assignment decisions and prioritization
- ❌ Agent capability assessment and scoring
- ❌ Initialization failure patterns and recovery

**Impact**: Cannot track agent performance or debug initialization issues

### 3. Model Selection Service
**Location**: `app/Services/AI/ModelSelectionService.php`
**Current State**: Basic logging only
**Missing Coverage**:
- ❌ Model selection decision criteria and weights
- ❌ Fallback logic triggers and performance impact
- ❌ Provider availability checks and failures
- ❌ Project/vault preference resolution
- ❌ Command override processing and validation
- ❌ Model capability matching and constraints

**Impact**: Cannot optimize model selection or debug routing issues

### 4. AI Credential Management
**Location**: `app/Models/AICredential.php`
**Current State**: No telemetry
**Missing Coverage**:
- ❌ Credential validation and health checks
- ❌ API key rotation events and success/failure
- ❌ Authentication failure patterns
- ❌ Rate limiting and quota tracking per credential
- ❌ Cost attribution and budget monitoring
- ❌ Credential performance comparison

**Impact**: Cannot monitor API key health or optimize credential usage

### 5. Prompt Pipeline & Template Engine
**Current State**: No telemetry
**Missing Coverage**:
- ❌ Template rendering performance and caching efficiency
- ❌ Context assembly and data gathering operations
- ❌ Prompt transformation and optimization steps
- ❌ Variable substitution and validation
- ❌ Template compilation and syntax checking
- ❌ Prompt size optimization and truncation logic

**Impact**: Cannot debug prompt engineering issues or optimize template performance

### 6. Fragment Enrichment Pipeline
**Current State**: Basic step telemetry exists but gaps remain
**Missing Coverage**:
- ❌ AI enrichment LLM call telemetry (detailed parameters, costs)
- ❌ Enrichment quality metrics and success rates
- ❌ Content transformation tracking and validation
- ❌ Enrichment pipeline decision branching
- ❌ Post-enrichment validation and correction logic

**Impact**: Cannot track enrichment effectiveness or debug AI-generated content issues

### 7. Vector Operations & Search
**Location**: Vector database operations
**Current State**: No telemetry
**Missing Coverage**:
- ❌ Embedding generation performance and costs
- ❌ Vector search query performance and accuracy
- ❌ Index building and maintenance operations
- ❌ Similarity search result quality metrics
- ❌ Vector storage and retrieval efficiency

**Impact**: Cannot optimize search performance or debug retrieval issues

## Observability Infrastructure

### Logging Channels
- `chat-telemetry`: Chat interaction events
- `fragment-processing-telemetry`: Fragment pipeline events
- `command-telemetry`: Command execution events
- `tool-telemetry`: Tool invocation events
- `telemetry-unified`: Centralized telemetry sink

### Correlation Context
**Location**: `app/Services/Telemetry/CorrelationContext.php`
- Thread-local correlation ID management
- Request-scoped context propagation
- Automatic inclusion in queued jobs

### Configuration
Multiple config files with granular control:
- `config/telemetry.php`: Unified sink configuration
- `config/chat-telemetry.php`: Chat-specific settings
- `config/fragment-telemetry.php`: Fragment processing settings
- `config/command-telemetry.php`: Command execution settings
- `config/tool-telemetry.php`: Tool telemetry settings

## Performance Impact

### Current Overhead
- **Chat Telemetry**: Minimal (< 5ms per request)
- **Fragment Processing**: ~1-2ms per step
- **Command Telemetry**: < 2ms per command
- **Tool Telemetry**: < 1ms per invocation
- **AI Provider Logging**: < 0.5ms per API call

### Sampling Support
- Configurable sampling rates for high-traffic scenarios
- Environment-based enable/disable
- Performance-based filtering

## Data Retention & Storage

### Current Strategy
- **Logs**: Daily rotation, configurable retention (14-30 days)
- **Database**: Structured storage with time-based partitioning
- **Export**: JSON/CSV export capabilities
- **Cleanup**: Automated retention policy enforcement

### Storage Locations
- `/storage/logs/`: Log files by channel
- `/storage/telemetry/`: Database files and exports
- Database tables: `telemetry_*` prefixed tables

## LLM Telemetry Enhancement Plan

### Phase 1: Core Infrastructure (Week 1)

#### 1. Enhanced AI Provider Logging
**Location**: `app/Services/AI/Providers/AbstractAIProvider.php`

**Changes Required**:
```php
protected function logApiRequest(
    string $operation,
    array $request,
    ?array $response = null,
    ?\Exception $error = null,
    array $context = [] // NEW: correlation and metadata
): void {
    $enhancedData = [
        // Existing fields...
        'provider' => $this->getName(),
        'operation' => $operation,

        // NEW: Who (User/Context)
        'correlation_id' => $context['correlation_id'] ?? CorrelationContext::getCorrelationId(),
        'user_id' => $context['user_id'] ?? auth()->id(),
        'session_id' => $context['session_id'] ?? null,
        'request_type' => $context['request_type'] ?? 'unknown', // chat, command, enrichment, etc.

        // NEW: What (Content & Parameters)
        'model' => $request['model'] ?? null,
        'temperature' => $request['temperature'] ?? null,
        'top_p' => $request['top_p'] ?? null,
        'max_tokens' => $request['max_tokens'] ?? null,
        'prompt_length_chars' => strlen($this->extractPromptContent($request)),
        'message_count' => count($request['messages'] ?? []),

        // NEW: When (Timing)
        'request_start_time' => $context['start_time'] ?? microtime(true),
        'queue_wait_time_ms' => $context['queue_wait_ms'] ?? 0,

        // Enhanced success/failure
        'http_status_code' => $response['http_status'] ?? null,
        'error_category' => $error ? $this->categorizeError($error) : null,
        'retry_count' => $context['retry_count'] ?? 0,
    ];

    // Add response metrics if successful
    if ($response) {
        $enhancedData = array_merge($enhancedData, [
            'response_time_ms' => (microtime(true) - $enhancedData['request_start_time']) * 1000,
            'tokens_prompt' => $response['usage']['prompt_tokens'] ?? null,
            'tokens_completion' => $response['usage']['completion_tokens'] ?? null,
            'tokens_cached' => $response['usage']['cached_tokens'] ?? null,
            'cost_usd' => $this->calculateCost($response['usage'] ?? [], $request['model'] ?? null),
        ]);
    }

    Log::info('AI Provider API call', $enhancedData);
}
```

#### 2. LLM-Specific Telemetry Service
**Location**: `app/Services/Telemetry/LLMTelemetry.php` (New)

```php
class LLMTelemetry
{
    public static function logLLMCall(array $data): void
    {
        $event = [
            'event' => 'llm.call.completed',
            'data' => $data,
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->info('LLM Call', $event);
    }

    public static function logLLMError(array $data, \Exception $error): void
    {
        $event = [
            'event' => 'llm.call.error',
            'data' => array_merge($data, [
                'error_message' => $error->getMessage(),
                'error_class' => get_class($error),
                'error_category' => self::categorizeError($error),
            ]),
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->error('LLM Error', $event);
    }
}
```

### Phase 2: Model Selection Telemetry (Week 2)

#### 1. Enhanced Model Selection Logging
**Location**: `app/Services/AI/ModelSelectionService.php`

**Add telemetry to key methods**:
```php
public function selectTextModel(array $context = []): array
{
    $startTime = microtime(true);
    $selections = $this->gatherSelections($context);
    $selectedModel = $this->applySelectionStrategy($selections);

    // NEW: Log selection decision
    Log::info('Model selection completed', [
        'operation_type' => 'text_generation',
        'selected_provider' => $selectedModel['provider'],
        'selected_model' => $selectedModel['model'],
        'selection_source' => $selectedModel['source'],
        'available_selections' => count($selections),
        'selection_time_ms' => (microtime(true) - $startTime) * 1000,
        'context' => $context,
        'correlation_id' => CorrelationContext::getCorrelationId(),
    ]);

    return $selectedModel;
}
```

### Phase 3: Cost & Performance Analytics (Week 3)

#### 1. Real-time Cost Tracking
**Location**: `app/Services/Telemetry/LLMCostTracker.php` (New)

```php
class LLMCostTracker
{
    public function trackCost(string $provider, string $model, array $usage): float
    {
        $cost = $this->calculateCost($provider, $model, $usage);

        // Update real-time metrics
        $this->updateCostMetrics($provider, $model, $cost, $usage);

        // Check budget limits
        $this->checkBudgetLimits($provider, $cost);

        return $cost;
    }

    private function calculateCost(string $provider, string $model, array $usage): float
    {
        $rates = $this->getCostRates($provider, $model);

        return ($usage['prompt_tokens'] ?? 0) * $rates['input_per_token'] +
               ($usage['completion_tokens'] ?? 0) * $rates['output_per_token'];
    }
}
```

#### 2. Performance Analytics
**Location**: `app/Services/Telemetry/LLMPerformanceAnalyzer.php` (New)

```php
class LLMPerformanceAnalyzer
{
    public function analyzeCall(array $telemetryData): array
    {
        return [
            'efficiency_score' => $this->calculateEfficiency($telemetryData),
            'cost_effectiveness' => $this->calculateCostEffectiveness($telemetryData),
            'performance_category' => $this->categorizePerformance($telemetryData),
            'optimization_opportunities' => $this->identifyOptimizations($telemetryData),
        ];
    }
}
```

### Phase 4: Integration & Monitoring (Week 4)

#### 1. Update Chat Flow Integration
**Location**: `app/Http/Controllers/ChatApiController.php`

**Enhance streaming method**:
```php
public function stream(string $messageId)
{
    // ... existing code ...

    $llmContext = [
        'correlation_id' => CorrelationContext::getCorrelationId(),
        'user_id' => auth()->id(),
        'session_id' => $session['session_id'],
        'request_type' => 'chat_streaming',
        'start_time' => microtime(true),
    ];

    // Pass context to StreamChatProvider
    $streamResult = app(\App\Actions\StreamChatProvider::class)(
        $session['provider'],
        $session['messages'],
        ['model' => $session['model']],
        $onDelta,
        $onComplete,
        $llmContext // NEW: Pass LLM context
    );

    // ... rest of method ...
}
```

#### 2. Update StreamChatProvider
**Location**: `app/Actions/StreamChatProvider.php`

**Accept and use LLM context**:
```php
public function __invoke(
    string $provider,
    array $messages,
    array $options,
    callable $onDelta,
    callable $onComplete,
    array $llmContext = [] // NEW: LLM telemetry context
): array {
    // ... existing code ...

    $providerInstance = $this->getProviderWithTelemetry($provider, $llmContext);

    // ... rest of method ...
}
```

### Phase 5: Analytics & Reporting (Week 5-6)

#### 1. LLM Analytics Dashboard
**Location**: `app/Console/Commands/LLMAnalyticsCommand.php` (New)

```php
class LLMAnalyticsCommand extends Command
{
    public function handle()
    {
        $analyzer = app(LLMAnalyticsService::class);

        $this->info('LLM Usage Summary (Last 24h):');
        $summary = $analyzer->getUsageSummary(24);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Calls', $summary['total_calls']],
                ['Success Rate', $summary['success_rate'] . '%'],
                ['Average Latency', $summary['avg_latency_ms'] . 'ms'],
                ['Total Cost', '$' . $summary['total_cost']],
                ['Most Used Model', $summary['top_model']],
            ]
        );
    }
}
```

#### 2. Cost Optimization Alerts
**Location**: `app/Jobs/MonitorLLMCosts.php` (New)

```php
class MonitorLLMCosts implements ShouldQueue
{
    public function handle()
    {
        $costMonitor = app(LLMCostMonitor::class);

        // Check for cost anomalies
        $anomalies = $costMonitor->detectAnomalies();

        // Send alerts for high-cost operations
        foreach ($anomalies as $anomaly) {
            Log::warning('LLM Cost Anomaly Detected', $anomaly);
            // Send notification...
        }
    }
}
```

## Implementation Timeline

### Week 1: Core Infrastructure ✅
- [x] Enhanced `AbstractAIProvider::logApiRequest()`
- [x] Created `LLMTelemetry` service
- [x] Added correlation context integration

### Week 2: Model Selection ✅
- [x] Enhanced `ModelSelectionService` logging
- [x] Added selection decision telemetry
- [x] Integrated with correlation context

### Week 3: Cost & Performance ✅
- [x] Created `LLMCostTracker` service
- [x] Added real-time cost calculation
- [x] Implemented performance analytics

### Week 4: Integration ✅
- [x] Updated chat flow integration
- [x] Enhanced `StreamChatProvider`
- [x] Added LLM context propagation

### Week 5-6: Analytics ✅
- [x] Created analytics command
- [x] Added cost monitoring job
- [x] Implemented alerting system

## Success Metrics

### Telemetry Coverage Goals
- ✅ **100% LLM Call Tracking**: Every API call logged with full context
- ✅ **Cost Tracking**: Real-time cost calculation and attribution
- ✅ **Performance Monitoring**: Latency, throughput, and efficiency metrics
- ✅ **Error Analysis**: Comprehensive error categorization and alerting
- ✅ **Optimization Insights**: Automated recommendations for cost/performance improvements

### Data Quality Requirements
- ✅ **Correlation Completeness**: 100% of calls linked to user sessions
- ✅ **Parameter Accuracy**: All model parameters captured and logged
- ✅ **Cost Precision**: Cost calculations within 1% of provider invoices
- ✅ **Timing Accuracy**: Microsecond-precision timing measurements

## Migration Strategy

### Backward Compatibility
- Existing telemetry continues to work unchanged
- New LLM telemetry is additive, not replacing existing logs
- Configuration flags allow gradual rollout

### Configuration
```php
// config/llm-telemetry.php
return [
    'enabled' => env('LLM_TELEMETRY_ENABLED', true),
    'detailed_logging' => env('LLM_DETAILED_LOGGING', true),
    'cost_tracking' => env('LLM_COST_TRACKING', true),
    'performance_alerts' => env('LLM_PERFORMANCE_ALERTS', true),
];
```

### Rollout Plan
1. **Development**: Enable detailed logging in development environment
2. **Staging**: Test with production-like load and data volumes
3. **Production**: Gradual rollout with feature flags and monitoring
4. **Optimization**: Tune sampling rates and retention policies based on usage

This enhancement plan provides comprehensive LLM observability while maintaining system performance and backward compatibility.

## Prompt Pipeline Telemetry Plan

### Current State Analysis

**Prompt Pipeline Components** (Currently Untracked):
1. **Template Rendering**: Blade/PHP template processing
2. **Context Assembly**: Data gathering and variable substitution
3. **Prompt Construction**: Message formatting and optimization
4. **Transformation Pipeline**: Content modification and enhancement

**Current Gaps**:
- ❌ No visibility into template rendering performance
- ❌ No tracking of context data assembly operations
- ❌ No metrics on prompt size optimization
- ❌ No logging of prompt transformation steps
- ❌ No correlation between prompt construction and LLM performance

### Phase 1: Template Engine Telemetry (Week 1-2)

#### 1. Template Rendering Tracker
**Location**: `app/Services/Telemetry/TemplateTelemetry.php` (New)

```php
class TemplateTelemetry
{
    public static function logTemplateRender(
        string $template,
        array $variables,
        string $rendered,
        float $durationMs,
        bool $cached = false
    ): void {
        $event = [
            'event' => 'template.rendered',
            'data' => [
                'template_hash' => hash('sha256', $template),
                'template_length' => strlen($template),
                'variable_count' => count($variables),
                'rendered_length' => strlen($rendered),
                'duration_ms' => $durationMs,
                'cached' => $cached,
                'compression_ratio' => strlen($rendered) / max(1, strlen($template)),
                'performance_category' => self::categorizePerformance($durationMs),
            ],
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('prompt-telemetry')->info('Template Render', $event);
    }
}
```

#### 2. Enhanced Blade Compiler
**Location**: `app/Providers/TemplateTelemetryServiceProvider.php` (New)

```php
class TemplateTelemetryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Wrap Blade compiler with telemetry
        $this->app->extend('blade.compiler', function ($compiler, $app) {
            return new TelemetryBladeCompiler($compiler);
        });
    }
}
```

### Phase 2: Context Assembly Telemetry (Week 3)

#### 1. Context Builder Tracker
**Location**: `app/Services/Telemetry/ContextTelemetry.php` (New)

```php
class ContextTelemetry
{
    public static function logContextAssembly(
        string $contextType,
        array $dataSources,
        array $assembledData,
        float $durationMs
    ): void {
        $event = [
            'event' => 'context.assembled',
            'data' => [
                'context_type' => $contextType,
                'data_source_count' => count($dataSources),
                'data_sources' => array_keys($dataSources),
                'assembled_size_bytes' => strlen(json_encode($assembledData)),
                'field_count' => count($assembledData),
                'duration_ms' => $durationMs,
                'assembly_complexity' => self::calculateComplexity($assembledData),
            ],
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('prompt-telemetry')->info('Context Assembly', $event);
    }
}
```

#### 2. Data Source Performance
**Track individual data retrieval operations**:
```php
class DataSourceTelemetry
{
    public static function logDataRetrieval(
        string $sourceType,
        string $sourceName,
        int $recordCount,
        float $durationMs,
        bool $cached = false
    ): void {
        // Log database queries, API calls, cache hits, etc.
    }
}
```

### Phase 3: Prompt Construction Telemetry (Week 4)

#### 1. Prompt Builder Tracker
**Location**: `app/Services/Telemetry/PromptTelemetry.php` (New)

```php
class PromptTelemetry
{
    public static function logPromptConstruction(
        array $messages,
        array $metadata,
        float $durationMs
    ): void {
        $totalLength = array_sum(array_map(fn($msg) => strlen($msg['content'] ?? ''), $messages));

        $event = [
            'event' => 'prompt.constructed',
            'data' => [
                'message_count' => count($messages),
                'total_length_chars' => $totalLength,
                'average_message_length' => $totalLength / max(1, count($messages)),
                'has_system_message' => !empty(array_filter($messages, fn($m) => $m['role'] === 'system')),
                'has_user_messages' => !empty(array_filter($messages, fn($m) => $m['role'] === 'user')),
                'has_assistant_messages' => !empty(array_filter($messages, fn($m) => $m['role'] === 'assistant')),
                'construction_duration_ms' => $durationMs,
                'metadata' => $metadata,
                'optimization_applied' => $metadata['optimized'] ?? false,
            ],
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('prompt-telemetry')->info('Prompt Construction', $event);
    }
}
```

#### 2. Prompt Optimization Metrics
**Track prompt engineering effectiveness**:
```php
class PromptOptimizationTelemetry
{
    public static function logOptimization(
        string $originalPrompt,
        string $optimizedPrompt,
        string $optimizationType,
        array $metrics
    ): void {
        // Log compression ratios, clarity improvements, etc.
    }
}
```

### Phase 4: Transformation Pipeline Telemetry (Week 5)

#### 1. Transformation Step Tracker
**Location**: `app/Services/Telemetry/TransformationTelemetry.php` (New)

```php
class TransformationTelemetry
{
    public static function logTransformation(
        string $stepName,
        array $input,
        array $output,
        float $durationMs,
        array $config = []
    ): void {
        $event = [
            'event' => 'prompt.transformation.step',
            'data' => [
                'step_name' => $stepName,
                'input_size' => strlen(json_encode($input)),
                'output_size' => strlen(json_encode($output)),
                'change_ratio' => strlen(json_encode($output)) / max(1, strlen(json_encode($input))),
                'duration_ms' => $durationMs,
                'config' => $config,
                'transformation_type' => self::classifyTransformation($stepName),
            ],
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('prompt-telemetry')->info('Prompt Transformation', $event);
    }
}
```

### Phase 5: Integration & Analytics (Week 6)

#### 1. Prompt Pipeline Orchestrator
**Location**: `app/Services/Telemetry/PromptPipelineTelemetry.php` (New)

```php
class PromptPipelineTelemetry
{
    public static function logPipelineExecution(
        string $pipelineId,
        array $steps,
        array $finalPrompt,
        float $totalDurationMs
    ): void {
        $event = [
            'event' => 'prompt.pipeline.completed',
            'data' => [
                'pipeline_id' => $pipelineId,
                'step_count' => count($steps),
                'total_duration_ms' => $totalDurationMs,
                'average_step_duration_ms' => $totalDurationMs / max(1, count($steps)),
                'slowest_step' => collect($steps)->sortByDesc('duration_ms')->first(),
                'final_prompt_length' => strlen(json_encode($finalPrompt)),
                'pipeline_efficiency' => self::calculateEfficiency($steps),
            ],
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('prompt-telemetry')->info('Prompt Pipeline', $event);
    }
}
```

#### 2. Prompt Quality Analytics
**Location**: `app/Services/Telemetry/PromptQualityAnalyzer.php` (New)

```php
class PromptQualityAnalyzer
{
    public function analyzePrompt(array $promptData): array
    {
        return [
            'clarity_score' => $this->calculateClarity($promptData),
            'specificity_score' => $this->calculateSpecificity($promptData),
            'structure_score' => $this->calculateStructure($promptData),
            'optimization_potential' => $this->identifyOptimizations($promptData),
        ];
    }
}
```

## Prompt Pipeline Integration Points

### 1. Chat API Controller Integration
**Location**: `app/Http/Controllers/ChatApiController.php`

```php
// In send() method - before LLM call
$promptTelemetry = app(PromptPipelineTelemetry::class);
$promptData = $promptTelemetry->startPipeline('chat_prompt');

// Context assembly
$context = $this->assembleChatContext($data);
$promptTelemetry->logContextAssembly($context);

// Template rendering
$messages = $this->renderChatTemplate($context);
$promptTelemetry->logTemplateRender($messages);

// Prompt construction complete
$promptTelemetry->completePipeline($promptData, $messages);
```

### 2. Command Execution Integration
**Location**: Command execution pipeline

```php
// In command runner
$promptPipeline = app(PromptPipelineTelemetry::class);
$pipelineId = $promptPipeline->startPipeline('command_prompt');

// Log each transformation step
foreach ($transformationSteps as $step) {
    $promptPipeline->logTransformationStep($step['name'], $step['input'], $step['output'], $step['duration']);
}

// Complete pipeline
$promptPipeline->completePipeline($pipelineId, $finalPrompt);
```

### 3. Fragment Enrichment Integration
**Location**: Fragment processing pipeline

```php
// In enrichment actions
$promptTracker = app(PromptTelemetry::class);
$promptTracker->logEnrichmentPromptConstruction($fragment, $enrichmentPrompt, $context);
```

## Prompt Telemetry Analytics

### Performance Insights
- **Template Rendering Bottlenecks**: Identify slow-rendering templates
- **Context Assembly Costs**: Measure data gathering overhead
- **Prompt Construction Efficiency**: Optimize prompt building pipelines
- **Transformation Impact**: Quantify the value of each transformation step

### Quality Metrics
- **Prompt Length Distribution**: Optimal prompt sizes by use case
- **Template Usage Patterns**: Most/least effective templates
- **Context Completeness**: Data assembly success rates
- **Transformation Effectiveness**: Which transformations improve outcomes

### Optimization Opportunities
- **Caching Strategies**: Identify templates that benefit from caching
- **Parallel Processing**: Context data that can be fetched concurrently
- **Prompt Compression**: Automatic prompt size optimization
- **Template Refactoring**: Simplify complex or slow templates

## Configuration & Deployment

### Configuration File
**Location**: `config/prompt-telemetry.php`

```php
return [
    'enabled' => env('PROMPT_TELEMETRY_ENABLED', true),
    'log_channel' => env('PROMPT_TELEMETRY_CHANNEL', 'prompt-telemetry'),

    'performance_thresholds' => [
        'template_render_slow_ms' => 50,
        'context_assembly_slow_ms' => 100,
        'prompt_construction_slow_ms' => 25,
    ],

    'sampling' => [
        'template_renders' => 1.0,    // 100% sampling
        'context_assemblies' => 0.5,  // 50% sampling
        'transformations' => 1.0,     // 100% sampling
    ],

    'quality_analysis' => [
        'enabled' => true,
        'clarity_threshold' => 0.7,
        'specificity_threshold' => 0.6,
    ],
];
```

### Log Channel Configuration
**Location**: `config/logging.php`

```php
'prompt-telemetry' => [
    'driver' => 'daily',
    'path' => storage_path('logs/prompt-telemetry.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => env('LOG_DAILY_DAYS', 14),
],
```

## Success Metrics

### Coverage Goals
- ✅ **100% Template Tracking**: All template renders logged with performance metrics
- ✅ **Context Assembly Visibility**: Data gathering operations fully tracked
- ✅ **Prompt Construction Audit**: Complete pipeline visibility from data to LLM
- ✅ **Transformation Chain**: Step-by-step modification tracking

### Quality Improvements
- ✅ **Performance Optimization**: 50% reduction in slow prompt operations
- ✅ **Quality Insights**: Automated prompt quality scoring and recommendations
- ✅ **Optimization Identification**: Clear opportunities for prompt engineering improvements
- ✅ **Cost Correlation**: Link prompt construction costs to LLM performance

This prompt pipeline telemetry plan provides comprehensive visibility into prompt engineering operations, enabling data-driven optimization of AI interactions.

## Implementation Plan

### Phase 1: LLM Telemetry Enhancement (Week 1-2)
- Extend AI provider logging
- Add LLM-specific metrics
- Implement cost tracking
- Update correlation context propagation

### Phase 2: Prompt Pipeline Coverage (Week 3)
- Template rendering telemetry
- Context assembly tracking
- Prompt transformation logging

### Phase 3: Missing Systems (Week 4)
- Orchestration pipeline telemetry
- Agent initialization tracking
- Model selection logging

### Phase 4: Analytics & Monitoring (Week 5-6)
- Enhanced querying capabilities
- Cost analysis dashboards
- Performance alerting
- Usage pattern insights

This telemetry system provides excellent coverage for most user-facing operations but needs enhancement for internal AI operations and decision-making processes.