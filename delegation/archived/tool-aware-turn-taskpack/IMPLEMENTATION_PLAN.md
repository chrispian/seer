# Tool-Aware Turn MVP - Implementation Plan

## Status: Planning Complete - Ready for Execution

## Overview
Implement a multi-phase orchestration system that allows the chat to intelligently decide when to use tools (MCP-based or local), execute them, and compose a final response with context.

## Current State Analysis

### Existing Infrastructure ✅
- **Tool System**: `ToolRegistry`, `ShellTool`, `MCPTool`, `FileSystemTool` already exist
- **MCP Support**: MCPTool supports JSON-RPC calls to MCP servers via HTTP
- **Chat Flow**: ChatApiController handles message routing and streaming
- **Fragment System**: Fragment creation and storage for chat history
- **AI Providers**: Multiple providers (OpenAI, Anthropic, Ollama) with streaming support
- **Config System**: Tools config in `config/fragments.php`

### Missing Components 🔨
1. ContextBroker - assembles conversation context
2. Router LLM - decides if tools are needed
3. Tool Candidate Selector - picks minimal tool set
4. Tool Runner - executes MCP tool plans with tracing
5. Outcome Summarizer - summarizes tool results
6. Final Composer - creates user-facing response
7. Audit/Logging - correlation tracking for entire turn
8. DTOs - structured data objects for pipeline
9. Prompts - LLM prompts for each phase
10. Tests - acceptance tests for 4 scenarios

## Implementation Phases

### Phase 1: Foundation (DTOs & Contracts)
**Location**: `app/Services/Orchestration/ToolAware/`

**Files to Create**:
```
app/Services/Orchestration/ToolAware/
├── DTOs/
│   ├── ContextBundle.php
│   ├── RouterDecision.php
│   ├── ToolPlan.php
│   ├── ExecutionTrace.php
│   ├── ToolResult.php
│   └── OutcomeSummary.php
├── Contracts/
│   ├── ContextBrokerInterface.php
│   ├── RouterInterface.php
│   ├── ToolSelectorInterface.php
│   ├── ToolRunnerInterface.php
│   └── ComposerInterface.php
└── Prompts/
    ├── router_decision.txt
    ├── tool_candidates.txt
    ├── outcome_summary.txt
    └── final_composer.txt
```

**Tasks**:
- [ ] Create DTO classes with proper type hints and validation
- [ ] Create interface contracts for each component
- [ ] Copy/adapt prompt templates from taskpack
- [ ] Add config section for tool-aware-turn in `config/fragments.php`

### Phase 2: Core Components
**Location**: `app/Services/Orchestration/ToolAware/`

**Files to Create**:
```
app/Services/Orchestration/ToolAware/
├── ContextBroker.php
├── Router.php
├── ToolSelector.php
├── ToolRunner.php
├── OutcomeSummarizer.php
└── FinalComposer.php
```

**Tasks**:
- [ ] **ContextBroker**: Implement `assemble()` to build ContextBundle
  - Pull conversation history from session
  - Summarize if > 600 chars
  - Preview 5 most relevant tools from registry
  - Extract agent preferences (if any)

- [ ] **Router**: Implement `decide()` to determine if tools needed
  - Call LLM with router_decision prompt
  - Parse JSON response
  - Retry once on parse failure
  - Return RouterDecision DTO

- [ ] **ToolSelector**: Implement `selectTools()` to pick minimal tool set
  - Expand registry slice based on goal
  - Call LLM with tool_candidates prompt
  - Validate against ToolPlan schema
  - Filter by permission allowlist
  - Fill missing args from context

- [ ] **ToolRunner**: Implement `execute()` to run tool plan
  - Generate correlation_id (UUID v4)
  - Execute each step via ToolRegistry
  - Track timing (elapsed_ms per step)
  - Collect results in ExecutionTrace
  - Handle errors gracefully

- [ ] **OutcomeSummarizer**: Implement `summarize()` to create summary
  - Call LLM with outcome_summary prompt
  - Redact sensitive data from results
  - Parse JSON response
  - Return OutcomeSummary DTO

- [ ] **FinalComposer**: Implement `compose()` to create final reply
  - Take OutcomeSummary + correlation_id
  - Generate concise user-facing message
  - Include key facts and links
  - Return formatted markdown

### Phase 3: Orchestrator Pipeline
**Location**: `app/Services/Orchestration/ToolAware/`

**File to Create**:
```
app/Services/Orchestration/ToolAware/ToolAwarePipeline.php
```

**Tasks**:
- [ ] Create main orchestrator that chains all components
- [ ] Implement full pipeline flow (see pseudocode)
- [ ] Add comprehensive logging at each step
- [ ] Implement audit trail with correlation_id
- [ ] Add redaction for secrets/PII in logs
- [ ] Handle graceful degradation on errors

### Phase 4: Chat Integration
**Location**: `app/Http/Controllers/ChatApiController.php`

**Tasks**:
- [ ] Add config flag `TOOL_AWARE_TURN_ENABLED` 
- [ ] Detect when to use tool-aware vs direct LLM
- [ ] Integrate ToolAwarePipeline into chat flow
- [ ] Return tool execution results to frontend
- [ ] Update chat session with tool metadata
- [ ] Stream final composed response

### Phase 5: Security & Guards
**Location**: `app/Services/Orchestration/ToolAware/Guards/`

**Files to Create**:
```
app/Services/Orchestration/ToolAware/Guards/
├── PermissionGate.php
├── RateLimiter.php
├── StepLimiter.php
└── Redactor.php
```

**Tasks**:
- [ ] **PermissionGate**: Enforce allow-list per user/agent
- [ ] **RateLimiter**: Rate limit MCP calls, exponential backoff
- [ ] **StepLimiter**: Cap max tool steps per turn (default: 10)
- [ ] **Redactor**: Redact secrets, API keys, PII from logs

### Phase 6: Testing
**Location**: `tests/Feature/Orchestration/`

**Files to Create**:
```
tests/Feature/Orchestration/
├── ToolAwareTurnTest.php
├── ToolAwareNoToolsTest.php
├── ToolAwareCalendarTest.php
└── ToolAwarePermissionTest.php
```

**Tasks**:
- [ ] Test 1: No tools path (direct response)
- [ ] Test 2: Calendar list path (happy path with tools)
- [ ] Test 3: JSON parse failure with retry
- [ ] Test 4: Permission-blocked tool handling
- [ ] Test 5: Tool execution error handling
- [ ] Test 6: Redaction in audit logs

### Phase 7: Documentation
**Location**: `docs/orchestration/`

**File to Create**:
```
docs/orchestration/tool-aware-turn.md
```

**Tasks**:
- [ ] Document architecture and flow
- [ ] Document configuration options
- [ ] Document security model
- [ ] Document adding new tool types
- [ ] Document debugging/troubleshooting
- [ ] Add sequence diagrams

## Configuration Schema

Add to `config/fragments.php`:
```php
'tool_aware_turn' => [
    'enabled' => env('TOOL_AWARE_TURN_ENABLED', false),
    'max_steps_per_turn' => env('TOOL_AWARE_MAX_STEPS', 10),
    'router_model' => env('TOOL_AWARE_ROUTER_MODEL', 'gpt-4o-mini'),
    'candidate_model' => env('TOOL_AWARE_CANDIDATE_MODEL', 'gpt-4o-mini'),
    'summary_model' => env('TOOL_AWARE_SUMMARY_MODEL', 'gpt-4o-mini'),
    'composer_model' => env('TOOL_AWARE_COMPOSER_MODEL', 'gpt-4o'),
    'timeout_seconds' => env('TOOL_AWARE_TIMEOUT', 60),
    'retry_on_parse_failure' => env('TOOL_AWARE_RETRY_PARSE', true),
    'audit_enabled' => env('TOOL_AWARE_AUDIT', true),
    'redact_logs' => env('TOOL_AWARE_REDACT', true),
],
```

## Acceptance Criteria Checklist

- [ ] ContextBroker.assemble() returns valid ContextBundle
- [ ] Router outputs valid JSON matching RouterDecision schema
- [ ] Tool Candidate Phase produces valid ToolPlan
- [ ] Tool Runner executes plan via MCP/ToolRegistry
- [ ] ExecutionTrace contains per-step ToolResult with timing
- [ ] Outcome Summary returns valid OutcomeSummary JSON
- [ ] Final Composer replies using summary + correlation_id
- [ ] All prompts/responses/tool IO stored in audit log
- [ ] Secrets/PII redacted from logs
- [ ] Single correlation_id tracks entire turn
- [ ] Registry slice avoids sending full tool list
- [ ] Allow-list enforcement works
- [ ] Max step limit enforced
- [ ] Retry on read-only tools only
- [ ] Demo: "What's on my calendar next week?" succeeds end-to-end

## Dependencies

### External
- None (uses existing AI providers)

### Internal
- `App\Services\Tools\ToolRegistry` - existing
- `App\Services\Tools\Providers\MCPTool` - existing
- `App\Services\AI\*` - existing AI provider system
- `App\Actions\CreateChatFragment` - existing

## Risk Mitigation

1. **LLM JSON Parse Failures**: Retry once with explicit instruction
2. **Tool Execution Timeouts**: Per-tool timeout + overall turn timeout
3. **Permission Escalation**: Explicit allow-list, no auto-escalation
4. **Infinite Loops**: Max step cap (default 10)
5. **Rate Limits**: Exponential backoff on 429/5xx
6. **Secret Leakage**: Redactor strips keys/tokens before logging

## Success Metrics

- [ ] All 4 acceptance tests pass
- [ ] End-to-end latency < 10s for calendar query
- [ ] Zero secret leaks in audit logs
- [ ] Permission gate blocks unauthorized tools
- [ ] Graceful degradation on all error paths

## Next Steps

1. Review this plan with team
2. Get approval on architecture
3. Start with Phase 1 (Foundation)
4. Implement iteratively, testing each component
5. Integration test after Phase 4
6. Security audit before production

---

**Estimated Effort**: 3-5 days for MVP
**Priority**: P0 (blocks agent orchestration features)
**Owner**: Backend team
