# Sprint 56: Structured Telemetry Foundation

## Overview
**Duration**: 5-6 days (46 hours)  
**Theme**: Transform logging from ad-hoc string messages to structured, privacy-respecting telemetry with correlation IDs and key-based metrics

## Context Analysis

**Current Logging Gaps Identified:**
- Chat ingest (`ChatApiController`) emits no structured logs
- Fragment processing logs emoji strings without correlation IDs  
- Command executions log failures only, not success metrics
- Tool invocations exist but lack upstream correlation keys
- No request-scoped context tracking across pipeline steps
- Missing enrichment step timing and outcome data

**Privacy Requirements:**
- Single local user (NativePHP runtime)
- Store object keys (`fragment_id`, `message_id`, `command_slug`) not content
- Correlation identifiers for debugging chains
- Metadata keys only for prompt/AI chain analysis

## Task Breakdown

| Task ID | Description | Agent Profile | Estimate | Priority |
|---------|-------------|---------------|----------|----------|
| **TELEMETRY-001** | Request Correlation Middleware | Senior Backend Engineer | 6h | High |
| **TELEMETRY-002** | Structured Chat Pipeline Logging | Senior Backend Engineer | 8h | High |
| **TELEMETRY-003** | Fragment Processing Telemetry Decorator | Senior Backend Engineer | 10h | High |
| **TELEMETRY-004** | Command & DSL Execution Metrics | Senior Backend Engineer | 8h | Medium |
| **TELEMETRY-005** | Enhanced Tool Invocation Correlation | Senior Backend Engineer | 6h | Medium |
| **TELEMETRY-006** | Local Telemetry Sink & Query Interface | DevOps/Full-Stack Engineer | 8h | Low |

## Key Deliverables

1. **Request correlation middleware** - UUID tracking across entire request lifecycle
2. **Structured chat logging** - Privacy-respecting telemetry for chat pipeline  
3. **Processing telemetry decorator** - Consistent step timing and outcome tracking
4. **Command execution metrics** - DSL and command controller telemetry
5. **Enhanced tool correlation** - Link tool invocations to upstream context
6. **Local telemetry dashboard** - Query interface for offline inspection

## Privacy & Performance Goals

- **Zero raw content storage** - only object keys and metadata
- **<5ms telemetry overhead** per request
- **Correlation tracking** across jobs and async processing
- **Local-first design** for NativePHP users

## Integration Points

- Builds on existing `LogMetrics` service (`app/Services/Metrics/LogMetrics.php`)
- Leverages current `tool_invocations` table schema
- Compatible with existing logging configuration
- Prepares foundation for future prompt/chain-of-thought metadata

## Success Criteria

- All chat interactions logged with structured metadata (no content)
- Fragment processing steps instrumented with timing/outcome data
- Command executions trackable with success/failure metrics
- Tool invocations correlate to upstream UI actions
- Local dashboard provides observability without external dependencies
- <5ms average telemetry overhead per request

## Dependencies

- Existing Laravel logging infrastructure
- Current `tool_invocations` table and migration system
- Request lifecycle middleware pipeline
- Fragment processing pipeline (`ProcessFragmentJob`)

## Risk Mitigation

- **Performance Impact**: Implement telemetry with minimal overhead, async where possible
- **Privacy Compliance**: Strict key-only logging, no raw content storage
- **Backward Compatibility**: Enhance existing logs, don't break current functionality
- **Testing Strategy**: Unit tests for telemetry helpers, integration tests for correlation