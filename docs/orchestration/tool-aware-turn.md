# Tool-Aware Turn (MVP)

## Overview

The Tool-Aware Turn system enables the chat to intelligently decide when external tools are needed, select the minimal set of tools, execute them, and compose a coherent response. This provides the foundation for agent-based workflows with MCP tool integration.

## Architecture

```
User Message
    ↓
ContextBroker → ContextBundle (conversation + tool preview)
    ↓
Router → RouterDecision (needs tools?)
    ↓
    ├─ No Tools → FinalComposer → Direct LLM Response
    ↓
    └─ Tools Needed
       ↓
       ToolSelector → ToolPlan (minimal tool set)
       ↓
       PermissionGate + StepLimiter (security)
       ↓
       ToolRunner → ExecutionTrace (with timing)
       ↓
       OutcomeSummarizer → OutcomeSummary (JSON)
       ↓
       FinalComposer → User Response
       ↓
       Audit Log (with redaction)
```

## Components

### 1. ContextBroker
**Location**: `app/Services/Orchestration/ToolAware/ContextBroker.php`

Assembles conversation context from chat session:
- Last 5 messages summarized to ~600 chars
- Agent preferences (model, provider)
- Preview of 5 most relevant tools

**Interface**: `ContextBrokerInterface`

```php
$context = $contextBroker->assemble($sessionId, $userMessage);
// Returns: ContextBundle
```

### 2. Router
**Location**: `app/Services/Orchestration/ToolAware/Router.php`

LLM-powered decision engine:
- Calls LLM with router_decision prompt
- Returns JSON: `{needs_tools, rationale, high_level_goal}`
- Retries once on JSON parse failure

**Interface**: `RouterInterface`

```php
$decision = $router->decide($context);
// Returns: RouterDecision
```

### 3. ToolSelector
**Location**: `app/Services/Orchestration/ToolAware/ToolAware/ToolSelector.php`

Selects minimal tool set:
- Expands registry slice based on goal
- Calls LLM with tool_candidates prompt
- Validates against schema
- Filters by permissions
- Fills missing args from context

**Interface**: `ToolSelectorInterface`

```php
$plan = $toolSelector->selectTools($goal, $context);
// Returns: ToolPlan
```

### 4. ToolRunner
**Location**: `app/Services/Orchestration/ToolAware/ToolRunner.php`

Executes tool plan:
- Generates correlation_id (UUID)
- Executes each step via ToolRegistry
- Tracks elapsed_ms per step
- Collects results in ExecutionTrace
- Respects max_steps_per_turn limit

**Interface**: `ToolRunnerInterface`

```php
$trace = $toolRunner->execute($plan);
// Returns: ExecutionTrace
```

### 5. OutcomeSummarizer
**Location**: `app/Services/Orchestration/ToolAware/OutcomeSummarizer.php`

Summarizes tool results:
- Calls LLM with outcome_summary prompt
- Redacts sensitive data
- Returns structured JSON
- Fallback on JSON parse failure

```php
$summary = $summarizer->summarize($trace);
// Returns: OutcomeSummary
```

### 6. FinalComposer
**Location**: `app/Services/Orchestration/ToolAware/FinalComposer.php`

Composes user-facing response:
- Natural language reply based on summary
- Includes key facts and links
- Acknowledges low confidence (<0.7)
- Falls back to direct LLM if no tools

**Interface**: `ComposerInterface`

```php
$message = $composer->compose($context, $summary, $correlationId);
// Returns: string (markdown)
```

### 7. ToolAwarePipeline
**Location**: `app/Services/Orchestration/ToolAware/ToolAwarePipeline.php`

Main orchestrator that chains all components:
- Handles full pipeline flow
- Comprehensive logging at each step
- Graceful degradation on errors
- Audit trail with redaction
- Returns structured result

```php
$result = $pipeline->execute($sessionId, $userMessage);
// Returns: [
//   'message' => string,
//   'correlation_id' => string|null,
//   'used_tools' => bool,
//   'metadata' => array
// ]
```

## Security & Guards

### PermissionGate
**Location**: `app/Services/Orchestration/ToolAware/Guards/PermissionGate.php`

Enforces allow-list per user/agent:
- Exact match: `gmail.send`
- Wildcard: `gmail.*` matches `gmail.send`, `gmail.list`
- Blocks unauthorized tools
- Logs all blocks

```php
$filteredPlan = $permissionGate->filter($plan, $userId, $agentId);
```

### StepLimiter
**Location**: `app/Services/Orchestration/ToolAware/Guards/StepLimiter.php`

Enforces max steps per turn:
- Default: 10 steps
- Truncates excessive plans
- Logs when limit exceeded

```php
$limitedPlan = $stepLimiter->limit($plan);
```

### RateLimiter
**Location**: `app/Services/Orchestration/ToolAware/Guards/RateLimiter.php`

Prevents abuse:
- 60 calls/minute per user
- 300 calls/hour per user
- 10 calls/minute per tool
- Exponential backoff calculation
- Retry logic for 429/5xx

```php
if ($rateLimiter->allow($userId, $toolId)) {
    // Execute
    $rateLimiter->hit($userId, $toolId);
}
```

### Redactor
**Location**: `app/Services/Orchestration/ToolAware/Guards/Redactor.php`

Strips sensitive data from logs:
- Email addresses
- API keys (OpenAI, Anthropic, AWS)
- Bearer tokens
- Passwords, secrets
- Credit cards, SSN
- Phone numbers

```php
$safe = $redactor->redactAll($data);
```

## Configuration

Located in `config/fragments.php` under `tool_aware_turn`:

```php
'tool_aware_turn' => [
    'enabled' => env('TOOL_AWARE_TURN_ENABLED', false),
    
    'limits' => [
        'max_steps_per_turn' => env('TOOL_AWARE_MAX_STEPS', 10),
        'timeout_seconds' => env('TOOL_AWARE_TIMEOUT', 60),
    ],

    'models' => [
        'router' => env('TOOL_AWARE_ROUTER_MODEL', 'gpt-4o-mini'),
        'candidate_selector' => env('TOOL_AWARE_CANDIDATE_MODEL', 'gpt-4o-mini'),
        'summarizer' => env('TOOL_AWARE_SUMMARY_MODEL', 'gpt-4o-mini'),
        'composer' => env('TOOL_AWARE_COMPOSER_MODEL', 'gpt-4o'),
    ],

    'features' => [
        'retry_on_parse_failure' => env('TOOL_AWARE_RETRY_PARSE', true),
        'audit_enabled' => env('TOOL_AWARE_AUDIT', true),
        'redact_logs' => env('TOOL_AWARE_REDACT', true),
    ],

    'context' => [
        'max_summary_length' => env('TOOL_AWARE_MAX_SUMMARY', 600),
        'tool_preview_count' => env('TOOL_AWARE_PREVIEW_COUNT', 5),
    ],
],
```

## Environment Variables

```bash
# Feature toggle
TOOL_AWARE_TURN_ENABLED=false

# Limits
TOOL_AWARE_MAX_STEPS=10
TOOL_AWARE_TIMEOUT=60

# Models (can be different for each stage)
TOOL_AWARE_ROUTER_MODEL=gpt-4o-mini
TOOL_AWARE_CANDIDATE_MODEL=gpt-4o-mini
TOOL_AWARE_SUMMARY_MODEL=gpt-4o-mini
TOOL_AWARE_COMPOSER_MODEL=gpt-4o

# Features
TOOL_AWARE_RETRY_PARSE=true
TOOL_AWARE_AUDIT=true
TOOL_AWARE_REDACT=true

# Context
TOOL_AWARE_MAX_SUMMARY=600
TOOL_AWARE_PREVIEW_COUNT=5
```

## Usage

### Enabling the Feature

1. Set environment variables:
```bash
TOOL_AWARE_TURN_ENABLED=true
```

2. Ensure AI provider is configured:
```bash
AI_DEFAULT_PROVIDER=openai
OPENAI_API_KEY=sk-...
```

3. Configure tool permissions:
```bash
FRAGMENT_TOOLS_ALLOWED=gmail.*,calendar.*,shell
```

### Chat Integration

Once enabled, all chat messages automatically route through the pipeline. The frontend already supports the response format via `skip_stream: true`.

**User sends**: "What's on my calendar next week?"

**System**:
1. ContextBroker assembles conversation
2. Router decides: needs tools
3. ToolSelector picks: `calendar.listEvents`
4. ToolRunner executes via MCP
5. Summarizer creates summary
6. Composer creates natural response
7. Frontend displays immediately

### Direct API Usage

```php
$pipeline = app(\App\Services\Orchestration\ToolAware\ToolAwarePipeline::class);

$result = $pipeline->execute(
    sessionId: $chatSession->id,
    userMessage: "What's on my calendar?"
);

// $result = [
//     'message' => 'You have 3 meetings next week...',
//     'correlation_id' => 'uuid',
//     'used_tools' => true,
//     'metadata' => [...]
// ]
```

## Prompts

All prompts are located in `app/Services/Orchestration/ToolAware/Prompts/`:

### router_decision.txt
Decides if tools are needed. Returns JSON with `needs_tools`, `rationale`, `high_level_goal`.

### tool_candidates.txt
Selects minimal tool set. Returns JSON with `selected_tool_ids`, `plan_steps`, `inputs_needed`.

### outcome_summary.txt
Summarizes results. Returns JSON with `short_summary`, `key_facts`, `links`, `confidence`.

### final_composer.txt
Composes natural user response. Returns markdown text.

## DTOs

All DTOs in `app/Services/Orchestration/ToolAware/DTOs/`:

### ContextBundle
- `conversation_summary`: string
- `user_message`: string
- `agent_prefs`: array
- `tool_registry_preview`: array

### RouterDecision
- `needs_tools`: bool
- `rationale`: string
- `high_level_goal`: string|null

### ToolPlan
- `selected_tool_ids`: string[]
- `plan_steps`: array (tool_id, args, why)
- `inputs_needed`: string[]

### ToolResult
- `tool_id`: string
- `args`: array
- `result`: mixed
- `error`: string|null
- `elapsed_ms`: float
- `success`: bool

### ExecutionTrace
- `correlation_id`: string (UUID)
- `steps`: ToolResult[]
- `total_elapsed_ms`: float

### OutcomeSummary
- `short_summary`: string
- `key_facts`: array
- `links`: array
- `confidence`: float (0.0-1.0)

## Logging & Telemetry

### Pipeline Logging
Every execution logs:
- Pipeline start/end with timing
- Each component execution
- Decision points (needs tools?)
- Tool selection
- Execution results
- Errors with stack traces

### Audit Trail
When `audit_enabled=true`:
- Full pipeline execution saved
- All prompts and responses
- Tool calls with args and results
- Correlation ID tracks entire turn
- Redacted if `redact_logs=true`

### Log Channels
- `Log::info()` - Pipeline flow
- `Log::debug()` - Detailed component data
- `Log::warning()` - Limits exceeded, retries
- `Log::error()` - Failures
- `Log::channel('daily')->info()` - Audit trail

## Error Handling

### Graceful Degradation
- Router fails → Return direct LLM response
- Tool selector fails → Return direct LLM response
- Tool execution fails → Include error in summary
- Summarizer fails → Use fallback summary
- Composer fails → Use simple formatted summary

### Retries
- Router: Retry once on JSON parse failure
- Tool selector: Retry once on JSON parse failure
- Rate limited tools: Exponential backoff

### Error Responses
All errors return valid response structure:
```php
[
    'message' => 'Error message',
    'correlation_id' => null,
    'used_tools' => false,
    'metadata' => ['error' => true, ...]
]
```

## Testing

Run tests:
```bash
php artisan test --filter=ToolAware
```

Test coverage:
- Pipeline execution (no tools, with tools)
- Permission filtering
- Step limiting
- Rate limiting
- Redaction
- DTO validation
- Error handling

## Performance

Typical timings (estimated):
- Context assembly: 10-50ms
- Router LLM call: 500-1500ms
- Tool selection LLM: 500-1500ms
- Tool execution: 100-5000ms (depends on tool)
- Summarizer LLM: 500-1500ms
- Composer LLM: 500-2000ms

**Total**: 2-12 seconds for tool-based turn

## Troubleshooting

### "Tool-aware turn not routing"
- Check `TOOL_AWARE_TURN_ENABLED=true`
- Check logs for "Tool-aware turn enabled, routing to pipeline"

### "No tools selected"
- Check tool registry has enabled tools
- Check tool permissions in `FRAGMENT_TOOLS_ALLOWED`
- Review router decision in logs

### "Permission denied"
- Add tool to allow-list: `FRAGMENT_TOOLS_ALLOWED=gmail.*,calendar.*`
- Check permission gate logs for blocked tools

### "Rate limit exceeded"
- Check RateLimiter logs for details
- Wait for backoff period
- Increase limits in RateLimiter class

### "Invalid JSON from LLM"
- Check LLM model supports structured output
- Enable retry: `TOOL_AWARE_RETRY_PARSE=true`
- Review prompt templates

## Future Enhancements

- [ ] Semantic tool matching (embeddings)
- [ ] User-specific permission DB
- [ ] Streaming tool output
- [ ] Multi-turn tool conversations
- [ ] Tool result caching
- [ ] Parallel tool execution
- [ ] Tool dependency graphs
- [ ] Custom tool prompts per tool
- [ ] Tool performance metrics dashboard

## Related Documentation

- [Exec Tool](./runners/v0-exec-tool.md)
- [MCP Integration](../mcp-servers/)
- [Tool System](../../tools/)
- Task Pack: `delegation/tool-aware-turn-taskpack/`

## API Reference

See source code documentation in:
- Interfaces: `app/Services/Orchestration/ToolAware/Contracts/`
- Implementations: `app/Services/Orchestration/ToolAware/`
- Tests: `tests/Feature/Orchestration/ToolAware*.php`
