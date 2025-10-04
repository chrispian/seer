# TELEMETRY-002: Structured Chat Pipeline Logging - Task List

## High Priority Tasks

### ✅ Task 1.1: Create ChatTelemetry Helper Service
- **File**: `app/Services/Telemetry/ChatTelemetry.php`
- **Estimate**: 1 hour
- **Description**: Central service for structured chat event logging with privacy-respecting schemas
- **Dependencies**: TELEMETRY-001 (correlation middleware)
- **Acceptance Criteria**:
  - Static methods for message sent, stream started/completed/failed events
  - JSON schema validation for log events
  - Privacy compliance (metadata keys only, no content)
  - Token usage and timing support

### ✅ Task 1.2: Enhance ChatApiController with Message Telemetry
- **File**: `app/Http/Controllers/ChatApiController.php`
- **Estimate**: 1.5 hours
- **Description**: Add structured logging for chat message send events and validation failures
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Log chat.message.sent events with complete metadata
  - Log validation failures with error context
  - Include message ID, conversation ID, session ID correlation
  - Track provider, model, attachment count, content length
  - No raw content logging

### ✅ Task 1.3: Update ChatApiController Stream Context
- **File**: `app/Http/Controllers/ChatApiController.php`
- **Estimate**: 30 minutes
- **Description**: Pass comprehensive context to StreamChatProvider for telemetry
- **Dependencies**: Task 1.2
- **Acceptance Criteria**:
  - Stream context includes message ID, conversation ID, session ID
  - Provider and model information passed through
  - Fragment ID correlation maintained

## Stream Provider Tasks

### ✅ Task 2.1: Enhance StreamChatProvider Telemetry
- **File**: `app/Actions/StreamChatProvider.php`
- **Estimate**: 2 hours
- **Description**: Replace basic logging with comprehensive streaming telemetry
- **Dependencies**: Task 1.1, Task 1.3
- **Acceptance Criteria**:
  - Log stream.started, stream.completed, stream.failed events
  - Capture timing, token usage, provider/model details
  - Correlate request and response fragment IDs
  - Extract token usage from AI provider responses
  - Maintain error context for failed streams

### ✅ Task 2.2: Add Token Usage Extraction
- **File**: `app/Actions/StreamChatProvider.php`
- **Estimate**: 45 minutes
- **Description**: Extract and format token usage data from AI provider responses
- **Dependencies**: Task 2.1
- **Acceptance Criteria**:
  - Support OpenAI, Anthropic, Ollama token formats
  - Extract prompt_tokens, completion_tokens, total_tokens
  - Handle missing/null token usage gracefully
  - Normalize across different provider response formats

## Session & Fragment Tasks

### ✅ Task 3.1: Enhance Chat Session Caching Telemetry
- **File**: `app/Actions/CacheChatSession.php`
- **Estimate**: 30 minutes
- **Description**: Add structured logging for session cache operations
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Log session.cached events with session metadata
  - Include provider and model information
  - Track session ID correlation

### ✅ Task 3.2: Enhance Chat Session Retrieval Telemetry
- **File**: `app/Actions/RetrieveChatSession.php`
- **Estimate**: 30 minutes
- **Description**: Add structured logging for session retrieval operations
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Log session.retrieved events for successful retrievals
  - Log session.not_found warnings for missing sessions
  - Include session metadata when available

### ✅ Task 3.3: Enhance CreateChatFragment Telemetry
- **File**: `app/Actions/CreateChatFragment.php`
- **Estimate**: 45 minutes
- **Description**: Add fragment creation lifecycle telemetry
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Log fragment.creation_started and fragment.created events
  - Track fragment ID, type, content length (not content)
  - Note deduplication bypass for chat fragments
  - Include timing for fragment creation process

## Testing Tasks

### ✅ Task 4.1: Unit Tests for ChatTelemetry Service
- **File**: `tests/Unit/Services/Telemetry/ChatTelemetryTest.php`
- **Estimate**: 1 hour
- **Description**: Comprehensive unit tests for ChatTelemetry service methods
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Test all ChatTelemetry static methods
  - Validate log event schemas and content
  - Test token usage formatting
  - Test null/missing value handling

### ✅ Task 4.2: Integration Tests for Chat Pipeline
- **File**: `tests/Feature/Telemetry/ChatPipelineTelemetryTest.php`
- **Estimate**: 1 hour
- **Description**: End-to-end telemetry validation for chat pipeline
- **Dependencies**: Tasks 1.2, 2.1, 3.3
- **Acceptance Criteria**:
  - Test complete chat send → stream → fragment creation flow
  - Validate correlation IDs propagate through pipeline
  - Test validation error logging
  - Verify token usage and timing capture

### ✅ Task 4.3: Performance Tests for Logging Overhead
- **File**: `tests/Performance/ChatTelemetryOverheadTest.php`
- **Estimate**: 45 minutes
- **Description**: Validate <2ms logging overhead requirement
- **Dependencies**: Tasks 1.1, 2.1
- **Acceptance Criteria**:
  - Benchmark chat pipeline with/without telemetry
  - Confirm <2ms additional latency per message
  - Test memory usage impact
  - Validate async logging performance

## Documentation Tasks

### ✅ Task 5.1: Update Chat Telemetry Documentation
- **File**: `docs/TELEMETRY_CHAT.md` (new)
- **Estimate**: 30 minutes
- **Description**: Document chat telemetry events and schemas
- **Dependencies**: All implementation tasks
- **Acceptance Criteria**:
  - Document all chat event types and schemas
  - Example log entries for each event type
  - Privacy guidelines for chat telemetry
  - Debugging guide using chat telemetry

### ✅ Task 5.2: Update CLAUDE.md with Chat Telemetry Patterns
- **File**: `CLAUDE.md`
- **Estimate**: 15 minutes
- **Description**: Add chat telemetry usage patterns to development guide
- **Dependencies**: Task 5.1
- **Acceptance Criteria**:
  - Usage examples for ChatTelemetry service
  - Integration patterns with correlation IDs
  - Debugging workflows with chat telemetry

## Validation Checklist

### Core Functionality
- [ ] Chat message send events logged with complete metadata
- [ ] Stream events capture timing, token usage, and outcomes
- [ ] Session operations include proper telemetry
- [ ] Fragment creation lifecycle tracked

### Privacy Compliance
- [ ] No raw message content in logs
- [ ] No user PII stored (single user = "local-default")
- [ ] Only metadata keys and measurements logged
- [ ] Attachment content not logged (count only)

### Performance Requirements
- [ ] <2ms total logging overhead per chat message
- [ ] Token usage extraction <0.5ms overhead
- [ ] Session telemetry <0.2ms overhead
- [ ] Memory usage impact <1MB per chat session

### Integration & Correlation
- [ ] Correlation IDs propagate from request to stream to fragment
- [ ] Message IDs link user input to AI response
- [ ] Session IDs correlate across multiple messages
- [ ] Fragment IDs connect telemetry to data pipeline

### Error Handling
- [ ] Validation failures logged with context
- [ ] Stream failures capture error details and timing
- [ ] Session errors (not found, invalid) logged
- [ ] Telemetry failures don't break chat functionality

## Time Estimate Summary
- **Core Implementation**: 5.5 hours
- **Testing**: 2.75 hours
- **Documentation**: 0.75 hours
- **Total**: 9 hours (with 1 hour buffer included)

## Dependencies
- **TELEMETRY-001**: Correlation middleware must be completed first
- **Existing Infrastructure**: Laravel Log facade, Fragment system, AI providers
- **Chat Pipeline**: ChatApiController, StreamChatProvider, session actions

## Risk Mitigation

### Performance Risks
- Monitor JSON serialization overhead in ChatTelemetry
- Consider lazy evaluation for complex token usage extraction
- Implement async logging for non-critical events

### Privacy Risks
- Code review for accidental content logging
- Automated tests to detect PII in log output
- Clear documentation on privacy boundaries

### Integration Risks
- Test with all AI providers (OpenAI, Anthropic, Ollama)
- Validate compatibility with existing fragment processing
- Ensure correlation works with job queue system