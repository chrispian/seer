# TELEMETRY-004: Command & DSL Execution Metrics - Task List

## High Priority Tasks

### ✅ Task 1.1: Create Command Telemetry Service
- **File**: `app/Services/Telemetry/CommandTelemetry.php`
- **Estimate**: 1.5 hours
- **Description**: Central service for command and DSL step telemetry with structured event logging
- **Dependencies**: TELEMETRY-001 (correlation middleware)
- **Acceptance Criteria**:
  - Static methods for execution start/completion/failure events
  - DSL step execution and failure logging
  - Execution ID generation and correlation
  - Privacy-compliant metadata-only logging
  - Integration with correlation context

### ✅ Task 1.2: Design Command Telemetry Schemas
- **File**: `app/Services/Telemetry/CommandTelemetry.php` (enhancement)
- **Estimate**: 30 minutes
- **Description**: Define structured schemas for command and step telemetry events
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Command execution event schema with metadata fields
  - DSL step event schema with timing and outcome data
  - Error event schema with context preservation
  - Consistent field naming across event types

## Command Controller Tasks

### ✅ Task 2.1: Enhance CommandController with Execution Telemetry
- **File**: `app/Http/Controllers/CommandController.php`
- **Estimate**: 1.5 hours
- **Description**: Add comprehensive telemetry to command execution flow
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Log command execution start/completion/failure events
  - Generate execution IDs for correlation
  - Track validation failures with context
  - Integrate with existing command logging database
  - Maintain existing error handling behavior

### ✅ Task 2.2: Add Command Validation Telemetry
- **File**: `app/Http/Controllers/CommandController.php` (enhancement)
- **Estimate**: 30 minutes
- **Description**: Log command validation failures with structured context
- **Dependencies**: Task 2.1
- **Acceptance Criteria**:
  - Log validation errors with field context
  - Include request source and user information
  - No sensitive data logging (request content filtered)

## DSL Runner Enhancement Tasks

### ✅ Task 3.1: Update CommandRunner with Step Instrumentation
- **File**: `app/Services/Commands/DSL/CommandRunner.php`
- **Estimate**: 2 hours
- **Description**: Add step-level telemetry and aggregate statistics to DSL execution
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Track individual step execution timing
  - Aggregate step statistics (executed/successful/failed)
  - Enhanced slow command logging with step context
  - Dry-run execution telemetry support
  - Fragment ID and mutations count extraction

### ✅ Task 3.2: Implement Step Statistics Aggregation
- **File**: `app/Services/Commands/DSL/CommandRunner.php` (enhancement)
- **Estimate**: 45 minutes
- **Description**: Track and report step-level statistics for command completion events
- **Dependencies**: Task 3.1
- **Acceptance Criteria**:
  - Count steps executed, successful, and failed
  - Include statistics in command completion telemetry
  - Handle partial execution failures appropriately

## DSL Step Instrumentation Tasks

### ✅ Task 4.1: Create DSL Step Telemetry Trait
- **File**: `app/Services/Commands/DSL/Steps/HasStepTelemetry.php`
- **Estimate**: 1 hour
- **Description**: Reusable trait for DSL step telemetry with timing and outcome tracking
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - `logStepExecution()` wrapper method for step timing
  - Fragment ID and mutations count extraction helpers
  - Error handling with telemetry preservation
  - Compatible with existing DSL step architecture

### ✅ Task 4.2: Update DatabaseUpdateStep with Telemetry
- **File**: `app/Services/Commands/DSL/Steps/DatabaseUpdateStep.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry to database mutation operations
- **Dependencies**: Task 4.1
- **Acceptance Criteria**:
  - Uses HasStepTelemetry trait
  - Logs affected rows count as mutations
  - Timing for database operations
  - Error context for failed updates

### ✅ Task 4.3: Update FragmentUpdateStep with Telemetry
- **File**: `app/Services/Commands/DSL/Steps/FragmentUpdateStep.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry to fragment modification operations
- **Dependencies**: Task 4.1
- **Acceptance Criteria**:
  - Uses HasStepTelemetry trait
  - Logs fragment ID and updated field count
  - Timing for fragment operations
  - Integration with fragment processing telemetry

### ✅ Task 4.4: Update ListMapStep with Telemetry
- **File**: `app/Services/Commands/DSL/Steps/ListMapStep.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry to list processing operations
- **Dependencies**: Task 4.1
- **Acceptance Criteria**:
  - Uses HasStepTelemetry trait
  - Logs list item count and processing outcomes
  - Timing for list operations
  - Error handling for failed list processing

### ✅ Task 4.5: Update ModelDeleteStep with Telemetry
- **File**: `app/Services/Commands/DSL/Steps/ModelDeleteStep.php`
- **Estimate**: 30 minutes
- **Description**: Enhance existing error logging with comprehensive telemetry
- **Dependencies**: Task 4.1
- **Acceptance Criteria**:
  - Uses HasStepTelemetry trait
  - Enhance existing error logging with structured telemetry
  - Log deletion count and affected models
  - Timing for delete operations

### ✅ Task 4.6: Standardize AiGenerateStep Telemetry
- **File**: `app/Services/Commands/DSL/Steps/AiGenerateStep.php`
- **Estimate**: 45 minutes
- **Description**: Standardize existing inconsistent logging with unified telemetry
- **Dependencies**: Task 4.1
- **Acceptance Criteria**:
  - Replace existing ad-hoc logging with HasStepTelemetry
  - Maintain cache hit/miss telemetry with structured format
  - Track AI generation timing and token usage
  - Preserve performance logging with consistent schema

## Testing Tasks

### ✅ Task 5.1: Unit Tests for CommandTelemetry Service
- **File**: `tests/Unit/Services/Telemetry/CommandTelemetryTest.php`
- **Estimate**: 1 hour
- **Description**: Comprehensive unit tests for command telemetry functionality
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Test execution start/completion/failure logging
  - Test step execution and failure logging
  - Validate telemetry event schemas
  - Test execution ID generation and correlation

### ✅ Task 5.2: Unit Tests for HasStepTelemetry Trait
- **File**: `tests/Unit/Services/Commands/DSL/Steps/HasStepTelemetryTest.php`
- **Estimate**: 45 minutes
- **Description**: Unit tests for DSL step telemetry trait functionality
- **Dependencies**: Task 4.1
- **Acceptance Criteria**:
  - Test step execution timing wrapper
  - Test fragment ID and mutations count extraction
  - Test error handling with telemetry preservation
  - Mock step execution scenarios

### ✅ Task 5.3: Integration Tests for Command Execution Flow
- **File**: `tests/Feature/Telemetry/CommandExecutionTelemetryTest.php`
- **Estimate**: 1 hour
- **Description**: End-to-end telemetry validation for command execution pipeline
- **Dependencies**: Tasks 2.1, 3.1, 4.1
- **Acceptance Criteria**:
  - Test complete command execution telemetry flow
  - Validate correlation IDs propagate through steps
  - Test dry-run execution telemetry
  - Test command validation failure telemetry
  - Verify step statistics aggregation

### ✅ Task 5.4: Performance Tests for Command Telemetry Overhead
- **File**: `tests/Performance/CommandTelemetryOverheadTest.php`
- **Estimate**: 45 minutes
- **Description**: Validate <3ms total telemetry overhead for command execution
- **Dependencies**: All implementation tasks
- **Acceptance Criteria**:
  - Benchmark command execution with/without telemetry
  - Benchmark individual DSL steps with/without telemetry
  - Confirm <2ms controller overhead, <0.5ms per step
  - Memory usage impact assessment

## Documentation Tasks

### ✅ Task 6.1: Create Command Telemetry Documentation
- **File**: `docs/TELEMETRY_COMMANDS.md` (new)
- **Estimate**: 30 minutes
- **Description**: Document command and DSL telemetry events and usage patterns
- **Dependencies**: All implementation tasks
- **Acceptance Criteria**:
  - Document all command and DSL event types
  - Example telemetry log entries for each event
  - Usage patterns for new DSL steps
  - Debugging workflows with command telemetry

### ✅ Task 6.2: Update CLAUDE.md with Command Telemetry Patterns
- **File**: `CLAUDE.md`
- **Estimate**: 15 minutes
- **Description**: Add command telemetry usage to development guide
- **Dependencies**: Task 6.1
- **Acceptance Criteria**:
  - CommandTelemetry service usage examples
  - HasStepTelemetry trait integration patterns
  - Debugging with command execution telemetry

## Validation Checklist

### Core Functionality
- [ ] All command executions logged with structured metadata
- [ ] DSL step performance tracked individually with timing
- [ ] Command success/failure rates measurable from telemetry
- [ ] Dry-run executions properly instrumented
- [ ] Execution IDs correlate command, steps, and tool invocations

### Performance Requirements
- [ ] <2ms telemetry overhead per command execution
- [ ] <0.5ms telemetry overhead per DSL step
- [ ] <3ms total overhead for typical 5-step command
- [ ] Memory usage impact <5KB per command execution

### Privacy Compliance
- [ ] No command content or sensitive data logged
- [ ] Only metadata, timing, and outcome data stored
- [ ] Fragment IDs and object counts logged (not content)
- [ ] Error messages sanitized of sensitive information

### Integration & Correlation
- [ ] Correlation IDs from TELEMETRY-001 propagate to command telemetry
- [ ] Execution IDs link commands to tool invocations (TELEMETRY-005)
- [ ] Step telemetry correlates within command execution
- [ ] Command telemetry integrates with scheduled execution (RunScheduledCommandJob)

### Error Handling & Debugging
- [ ] Command execution failures logged with complete context
- [ ] DSL step failures preserve timing and error details
- [ ] Validation failures provide actionable error context
- [ ] Telemetry failures don't break command execution

## Time Estimate Summary
- **Core Services**: 2 hours
- **Controller Enhancement**: 2 hours
- **DSL Runner Enhancement**: 2.75 hours
- **Step Instrumentation**: 3.25 hours
- **Testing**: 3.5 hours
- **Documentation**: 0.75 hours
- **Total**: 14.25 hours (with 6.25 hour buffer included)

## Dependencies
- **TELEMETRY-001**: Correlation middleware for request tracking
- **Existing Infrastructure**: Command system, DSL architecture, Laravel logging
- **Database**: Command executions table for enhanced logging

## Risk Mitigation

### Performance Risks
- Profile telemetry overhead with realistic command workloads
- Consider async telemetry for non-critical step events
- Optimize JSON serialization for telemetry data

### Integration Risks
- Test with all existing DSL step types
- Validate compatibility with scheduled command execution
- Ensure dry-run mode telemetry doesn't interfere with testing

### Privacy Risks
- Code review all telemetry data for content leakage
- Automated tests to detect sensitive information in logs
- Clear guidelines for what constitutes "metadata only"