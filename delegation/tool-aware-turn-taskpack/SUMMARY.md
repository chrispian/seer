# Tool-Aware Turn MVP - Executive Summary

## What We're Building

A multi-phase AI orchestration system that allows the chat to intelligently:
1. **Decide** if it needs tools to answer a question
2. **Select** the minimal set of tools required
3. **Execute** those tools via MCP or local providers
4. **Summarize** the results
5. **Compose** a coherent response to the user

## Example Flow

**User**: "What's on my calendar next week?"

1. **ContextBroker** assembles conversation context
2. **Router** decides: "Yes, I need tools (calendar access)"
3. **ToolSelector** picks: `calendar.listEvents` with date range
4. **ToolRunner** executes via MCP, gets back events
5. **Summarizer** creates: "3 meetings found, 2 deadlines"
6. **Composer** replies: "You have 3 meetings next week: Team standup (Mon 10am), Client review (Wed 2pm), Sprint planning (Fri 9am). Also 2 project deadlines on Thursday."

## Why This Matters

**Current State**: Chat can only answer from knowledge or conversation history. No external data access.

**After MVP**: Chat can fetch emails, calendar events, run code, query databases, send messages - all intelligently orchestrated with full audit trails.

## Architecture Overview

```
User Message
    ↓
ContextBroker → ContextBundle
    ↓
Router → needs_tools? → RouterDecision
    ↓
ToolSelector → ToolPlan (minimal set)
    ↓
ToolRunner → ExecutionTrace (with timing)
    ↓
OutcomeSummarizer → OutcomeSummary (JSON)
    ↓
FinalComposer → User Reply (markdown)
    ↓
Audit Log (correlation_id, redacted)
```

## Security Model

1. **Allow-list Enforcement**: Only pre-approved tools per user/agent
2. **Read-First Policy**: Prefer read-only tools, escalate only when needed
3. **Step Limits**: Max 10 tool calls per turn (configurable)
4. **Rate Limiting**: Exponential backoff on failures
5. **Secret Redaction**: Strip API keys, tokens, PII from logs
6. **Audit Trail**: Every prompt, tool call, result logged with correlation_id

## Implementation Phases

| Phase | What | Files | Estimated Time |
|-------|------|-------|----------------|
| 1 | DTOs, Contracts, Prompts | 15 files | 4 hours |
| 2 | Core Components | 6 classes | 1 day |
| 3 | Pipeline Orchestrator | 1 class | 4 hours |
| 4 | Chat Integration | Controller updates | 4 hours |
| 5 | Security & Guards | 4 classes | 6 hours |
| 6 | Testing | 6 test files | 1 day |
| 7 | Documentation | 1 doc | 2 hours |

**Total Estimated Effort**: 3-5 days

## Key Components

### 1. ContextBroker
- Pulls conversation history
- Summarizes context to ~500 chars
- Previews 5 most relevant tools
- Extracts agent preferences

### 2. Router
- LLM-powered decision: needs tools?
- Returns JSON with rationale
- Retries once on parse failure

### 3. ToolSelector
- Expands registry based on goal
- LLM picks minimal tool set
- Validates against allow-list
- Fills missing args from context

### 4. ToolRunner
- Executes plan via ToolRegistry/MCP
- Tracks timing per step
- Handles errors gracefully
- Generates correlation_id

### 5. OutcomeSummarizer
- LLM creates structured summary
- Extracts key facts, links
- Confidence score

### 6. FinalComposer
- Generates user-facing markdown
- Concise, includes references
- Links to correlation_id for debugging

## Acceptance Tests

1. **No Tools Path**: Simple question → direct LLM response
2. **Calendar Query**: "What's on my calendar?" → tools → results → reply
3. **Parse Failure**: Invalid JSON → retry → success
4. **Permission Block**: Unauthorized tool → graceful error or alternative

## Configuration

```env
TOOL_AWARE_TURN_ENABLED=false
TOOL_AWARE_MAX_STEPS=10
TOOL_AWARE_ROUTER_MODEL=gpt-4o-mini
TOOL_AWARE_TIMEOUT=60
TOOL_AWARE_AUDIT=true
TOOL_AWARE_REDACT=true
```

## Success Criteria

- ✅ All 4 acceptance tests pass
- ✅ End-to-end latency < 10s
- ✅ Zero secret leaks in audit logs
- ✅ Permission gate blocks unauthorized tools
- ✅ Graceful degradation on all errors

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| LLM returns invalid JSON | Retry with explicit instruction |
| Tool execution timeout | Per-tool + overall timeout limits |
| Permission escalation | Explicit allow-list, no auto-escalation |
| Infinite loops | Max step cap (10 default) |
| Rate limits | Exponential backoff on 429/5xx |
| Secret leakage | Redactor strips before logging |

## Dependencies

**External**: None (uses existing AI providers)

**Internal**:
- ToolRegistry (existing)
- MCPTool (existing)
- AI Provider system (existing)
- Fragment creation (existing)

## Next Steps

1. ✅ Review implementation plan
2. ✅ Create progress tracker
3. 🔲 Get approval to proceed
4. 🔲 Start Phase 1 (Foundation)
5. 🔲 Iterate through phases
6. 🔲 Integration test
7. 🔲 Security audit
8. 🔲 Deploy to staging

---

**Status**: 📋 Planning Complete - Awaiting Approval to Begin
**Priority**: P0
**Owner**: Backend Team
**Target Date**: 2025-10-15
