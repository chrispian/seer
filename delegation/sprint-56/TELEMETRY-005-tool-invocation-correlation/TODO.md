# TELEMETRY-005: Enhanced Tool Invocation Correlation - Task List

## High Priority Tasks

### ✅ Task 1.1: Create Database Migration for Correlation Fields
- **File**: `database/migrations/2025_01_04_add_correlation_fields_to_tool_invocations.php`
- **Estimate**: 45 minutes
- **Description**: Add correlation fields and indexes to existing tool_invocations table
- **Dependencies**: None (enhances existing table)
- **Acceptance Criteria**:
  - Add message_id, conversation_id, command_execution_id, correlation_id, processing_job_id fields
  - Create indexes for correlation queries (correlation_id, message_id, command_execution_id, etc.)
  - Backward compatible with existing tool_invocations data
  - Proper rollback functionality in down() method

### ✅ Task 1.2: Create ToolInvocation Eloquent Model (Optional)
- **File**: `app/Models/ToolInvocation.php`
- **Estimate**: 30 minutes
- **Description**: Create optional Eloquent model for enhanced query capabilities
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - UUID primary key configuration
  - Fillable fields for correlation data
  - JSON casting for request/response fields
  - Correlation query scopes (byCorrelation, byMessage, byCommandExecution)
  - Proper relationships if needed

### ✅ Task 1.3: Run Migration and Validate Schema
- **File**: Database migration execution
- **Estimate**: 15 minutes
- **Description**: Execute migration and validate new schema structure
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Migration runs successfully without errors
  - New fields and indexes created correctly
  - Existing tool_invocations data preserved
  - Database performance not degraded

## Tool Invocation Enhancement Tasks

### ✅ Task 2.1: Create ToolInvocationLogger Service
- **File**: `app/Services/Telemetry/ToolInvocationLogger.php`
- **Estimate**: 1.5 hours
- **Description**: Enhanced service for logging tool invocations with correlation context
- **Dependencies**: Task 1.1, TELEMETRY-001 (correlation context)
- **Acceptance Criteria**:
  - `log()` method with correlation context gathering
  - Integration with CorrelationContext from TELEMETRY-001
  - Query methods for correlation analysis (queryByCorrelation, queryByMessage, etc.)
  - Backward compatibility with existing tool invocation format
  - Automatic correlation context detection from request/job context

### ✅ Task 2.2: Implement Correlation Context Gathering
- **File**: `app/Services/Telemetry/ToolInvocationLogger.php` (enhancement)
- **Estimate**: 45 minutes
- **Description**: Smart correlation context detection from various sources
- **Dependencies**: Task 2.1, TELEMETRY-001, TELEMETRY-002, TELEMETRY-003, TELEMETRY-004
- **Acceptance Criteria**:
  - Automatically detect correlation_id from request middleware
  - Extract command_execution_id from command context
  - Extract message_id and conversation_id from chat context
  - Extract processing_job_id from fragment processing context
  - Graceful handling of missing context (nullable fields)

## Tool Integration Updates

### ✅ Task 3.1: Update ToolCallStep with Enhanced Logging
- **File**: `app/Services/Commands/DSL/Steps/ToolCallStep.php`
- **Estimate**: 45 minutes
- **Description**: Replace existing tool invocation logging with enhanced correlation logging
- **Dependencies**: Task 2.1
- **Acceptance Criteria**:
  - Replace DB::table() insert with ToolInvocationLogger::log()
  - Pass command execution context from TELEMETRY-004
  - Pass chat context when available
  - Maintain existing functionality and error handling
  - No breaking changes to DSL step behavior

### ✅ Task 3.2: Update Chat-to-Tool Context Propagation
- **File**: `app/Http/Controllers/ChatApiController.php` (if applicable)
- **Estimate**: 30 minutes
- **Description**: Ensure chat context propagates to tool invocations via commands
- **Dependencies**: Task 3.1, TELEMETRY-002
- **Acceptance Criteria**:
  - Chat message context (message_id, conversation_id) passed to commands
  - Commands that invoke tools inherit chat context
  - Session context included when available
  - No impact on existing chat functionality

### ✅ Task 3.3: Update Fragment Processing Tool Context
- **File**: `app/Jobs/ProcessFragmentJob.php` (if applicable)
- **Estimate**: 30 minutes
- **Description**: Ensure fragment processing context propagates to tool invocations
- **Dependencies**: Task 3.1, TELEMETRY-003
- **Acceptance Criteria**:
  - Processing job context (processing_job_id) passed to tools
  - Fragment context maintained through tool invocations
  - Integration with processing telemetry decorator
  - No impact on fragment processing performance

## Query Interface & Analysis Tasks

### ✅ Task 4.1: Enhance Tool Invocations Console Command
- **File**: `app/Console/Commands/Tools/ToolInvocationsCommand.php`
- **Estimate**: 45 minutes
- **Description**: Add correlation query options to existing tool invocations command
- **Dependencies**: Task 1.1, Task 2.1
- **Acceptance Criteria**:
  - Add --correlation-id, --message-id, --command-execution-id options
  - Enhanced table display with correlation IDs
  - Backward compatibility with existing command functionality
  - Performance optimization for correlation queries

### ✅ Task 4.2: Create Correlation Analysis Command
- **File**: `app/Console/Commands/Telemetry/AnalyzeCorrelationCommand.php`
- **Estimate**: 1 hour
- **Description**: New command for comprehensive correlation analysis
- **Dependencies**: Task 2.1, integration with other telemetry systems
- **Acceptance Criteria**:
  - Analyze all activity for a given correlation ID
  - Show tool invocations, command executions, chat messages
  - Support table and JSON output formats
  - Performance optimized for correlation queries
  - Useful for debugging and request tracing

## Testing Tasks

### ✅ Task 5.1: Unit Tests for ToolInvocationLogger
- **File**: `tests/Unit/Services/Telemetry/ToolInvocationLoggerTest.php`
- **Estimate**: 1 hour
- **Description**: Comprehensive unit tests for enhanced tool invocation logging
- **Dependencies**: Task 2.1
- **Acceptance Criteria**:
  - Test tool invocation logging with all correlation fields
  - Test correlation context gathering from various sources
  - Test query methods (queryByCorrelation, queryByMessage, etc.)
  - Test backward compatibility with existing tool invocation format
  - Test handling of missing/null correlation context

### ✅ Task 5.2: Integration Tests for Tool Correlation Flow
- **File**: `tests/Feature/Telemetry/ToolInvocationCorrelationTest.php`
- **Estimate**: 1 hour
- **Description**: End-to-end tests for tool invocation correlation across systems
- **Dependencies**: Tasks 3.1, 3.2, 3.3
- **Acceptance Criteria**:
  - Test chat → command → tool correlation flow
  - Test fragment processing → tool correlation flow
  - Test direct tool invocation correlation
  - Validate correlation IDs propagate correctly
  - Test query performance for correlation analysis

### ✅ Task 5.3: Performance Tests for Correlation Queries
- **File**: `tests/Performance/ToolInvocationCorrelationPerformanceTest.php`
- **Estimate**: 30 minutes
- **Description**: Validate correlation query performance and indexing effectiveness
- **Dependencies**: Task 1.1, Task 2.1
- **Acceptance Criteria**:
  - Benchmark correlation queries with realistic data volumes
  - Validate index usage for correlation queries
  - Confirm <100ms query performance for typical correlation analysis
  - Test impact of correlation fields on insert performance (<1ms overhead)

## Documentation Tasks

### ✅ Task 6.1: Document Tool Invocation Correlation
- **File**: `docs/TELEMETRY_TOOL_CORRELATION.md` (new)
- **Estimate**: 30 minutes
- **Description**: Document tool invocation correlation patterns and usage
- **Dependencies**: All implementation tasks
- **Acceptance Criteria**:
  - Document correlation schema and field purposes
  - Example correlation queries and use cases
  - Debugging workflows with tool correlation
  - Performance guidelines for correlation queries

### ✅ Task 6.2: Update CLAUDE.md with Tool Correlation Patterns
- **File**: `CLAUDE.md`
- **Estimate**: 15 minutes
- **Description**: Add tool correlation usage to development guide
- **Dependencies**: Task 6.1
- **Acceptance Criteria**:
  - ToolInvocationLogger usage examples
  - Correlation context propagation patterns
  - Console command usage for debugging
  - Integration with other telemetry systems

## Validation Checklist

### Core Functionality
- [ ] Tool invocations correlate to chat messages, commands, and fragments
- [ ] Correlation context automatically gathered from request/job context
- [ ] Query interface supports efficient correlation analysis
- [ ] Backward compatibility maintained for existing tool invocations

### Database & Performance
- [ ] Migration adds correlation fields and indexes successfully
- [ ] Correlation queries perform well (<100ms for typical analysis)
- [ ] Tool invocation logging overhead <1ms per invocation
- [ ] Database storage impact minimal (~200-500 bytes per invocation)

### Integration & Context Flow
- [ ] Chat messages → commands → tools maintain correlation chain
- [ ] Fragment processing → tools maintain correlation context
- [ ] Direct tool invocations capture correlation from request middleware
- [ ] Context propagation works across different execution paths

### Query & Analysis Capabilities
- [ ] Console commands support correlation filtering and analysis
- [ ] Correlation analysis provides complete request tracing
- [ ] Query performance optimized with proper indexing
- [ ] Multiple output formats support different debugging needs

### Testing & Validation
- [ ] Unit tests cover all ToolInvocationLogger functionality
- [ ] Integration tests validate end-to-end correlation flow
- [ ] Performance tests confirm query and insert performance targets
- [ ] Backward compatibility tests ensure existing functionality preserved

## Time Estimate Summary
- **Database Schema**: 1.5 hours
- **Tool Invocation Enhancement**: 2.75 hours
- **Tool Integration Updates**: 1.75 hours
- **Query Interface**: 1.75 hours
- **Testing**: 2.5 hours
- **Documentation**: 0.75 hours
- **Total**: 11 hours (with 5 hour buffer included)

## Dependencies
- **TELEMETRY-001**: Correlation middleware for correlation_id context
- **TELEMETRY-002**: Chat telemetry for message_id and conversation_id context
- **TELEMETRY-003**: Processing telemetry for processing_job_id context
- **TELEMETRY-004**: Command telemetry for command_execution_id context
- **Existing Infrastructure**: tool_invocations table, ToolCallStep, console commands

## Risk Mitigation

### Database Risks
- Test migration thoroughly with existing tool_invocations data
- Monitor query performance with new indexes
- Plan rollback strategy if performance issues arise

### Integration Risks
- Ensure correlation context propagation doesn't break existing flows
- Test with all tool types and invocation patterns
- Validate backward compatibility with existing tool invocation storage

### Performance Risks
- Monitor correlation query performance under load
- Consider correlation field data retention policies
- Optimize indexes based on actual query patterns