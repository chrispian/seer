# TELEMETRY-003: Fragment Processing Telemetry Decorator - Task List

## High Priority Tasks

### ✅ Task 1.1: Create Processing Telemetry Decorator
- **File**: `app/Services/Telemetry/ProcessingTelemetryDecorator.php`
- **Estimate**: 2 hours
- **Description**: Core decorator service for instrumenting processing steps with timing and outcome telemetry
- **Dependencies**: TELEMETRY-001 (correlation middleware)
- **Acceptance Criteria**:
  - `wrapStep()` method for individual step instrumentation
  - `wrapJob()` method for job-level telemetry
  - Generated key extraction for different step types
  - Error code mapping for exception handling
  - <1ms overhead per step requirement

### ✅ Task 1.2: Create Processing Telemetry Trait
- **File**: `app/Services/Telemetry/HasProcessingTelemetry.php`
- **Estimate**: 45 minutes
- **Description**: Reusable trait for easy telemetry integration in action classes
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - `withTelemetry()` helper method
  - Automatic step name detection from class name
  - Context preparation helpers
  - Compatible with existing action architecture

### ✅ Task 1.3: Implement Key Extraction Logic
- **File**: `app/Services/Telemetry/ProcessingTelemetryDecorator.php` (enhancement)
- **Estimate**: 45 minutes
- **Description**: Extract meaningful metadata keys from step results without logging content
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Handle ExtractJsonMetadata result format
  - Handle SuggestTags result format  
  - Handle GenerateAutoTitle result format
  - Handle ParseAtomicFragment result format
  - Privacy-compliant (no content logging)

## Job Instrumentation Tasks

### ✅ Task 2.1: Update ProcessFragmentJob with Job-Level Telemetry
- **File**: `app/Jobs/ProcessFragmentJob.php`
- **Estimate**: 1.5 hours
- **Description**: Instrument main fragment processing job with comprehensive telemetry
- **Dependencies**: Task 1.1, TELEMETRY-001 (correlation)
- **Acceptance Criteria**:
  - Job start/completion/failure events logged
  - Individual processing steps wrapped with telemetry
  - Integration with correlation context
  - Test mode handling maintained
  - Database transaction safety preserved

### ✅ Task 2.2: Handle ProcessFragmentJob Test Mode
- **File**: `app/Jobs/ProcessFragmentJob.php` (enhancement)
- **Estimate**: 30 minutes
- **Description**: Ensure telemetry works correctly in unit test environment
- **Dependencies**: Task 2.1
- **Acceptance Criteria**:
  - Test mode steps instrumented
  - No interference with existing test logic
  - Telemetry validation in test environment

## Action Instrumentation Tasks

### ✅ Task 3.1: Update ExtractJsonMetadata with Telemetry
- **File**: `app/Actions/ExtractJsonMetadata.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry instrumentation to JSON metadata extraction
- **Dependencies**: Task 1.2
- **Acceptance Criteria**:
  - Uses HasProcessingTelemetry trait
  - Wraps extraction logic with telemetry
  - Logs metadata field keys (not values)
  - Maintains existing return value and behavior

### ✅ Task 3.2: Update EnrichAssistantMetadata with Telemetry
- **File**: `app/Actions/EnrichAssistantMetadata.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry instrumentation to assistant metadata enrichment
- **Dependencies**: Task 1.2
- **Acceptance Criteria**:
  - Uses HasProcessingTelemetry trait
  - Wraps enrichment logic with telemetry
  - Logs enrichment outcome without content
  - Maintains existing behavior

### ✅ Task 3.3: Update SuggestTags with Telemetry
- **File**: `app/Actions/SuggestTags.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry instrumentation to tag suggestion process
- **Dependencies**: Task 1.2
- **Acceptance Criteria**:
  - Uses HasProcessingTelemetry trait
  - Wraps tag suggestion logic with telemetry
  - Logs tag count and IDs (not tag content)
  - Maintains existing return value format

### ✅ Task 3.4: Update ParseAtomicFragment with Telemetry
- **File**: `app/Actions/ParseAtomicFragment.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry instrumentation to fragment parsing
- **Dependencies**: Task 1.2
- **Acceptance Criteria**:
  - Uses HasProcessingTelemetry trait
  - Wraps parsing logic with telemetry
  - Logs fragment ID and type
  - Maintains existing parsing behavior

### ✅ Task 3.5: Update GenerateAutoTitle with Telemetry
- **File**: `app/Actions/GenerateAutoTitle.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry instrumentation to auto title generation
- **Dependencies**: Task 1.2
- **Acceptance Criteria**:
  - Uses HasProcessingTelemetry trait
  - Wraps title generation logic with telemetry
  - Logs title generation success and length (not content)
  - Maintains existing return value behavior

### ✅ Task 3.6: Update ProcessAssistantFragment with Telemetry
- **File**: `app/Actions/ProcessAssistantFragment.php`
- **Estimate**: 30 minutes
- **Description**: Add telemetry instrumentation to assistant fragment processing
- **Dependencies**: Task 1.2
- **Acceptance Criteria**:
  - Uses HasProcessingTelemetry trait
  - Wraps assistant processing logic with telemetry
  - Logs processing outcome and timing
  - Maintains existing processing behavior

## Controller Integration Tasks

### ✅ Task 4.1: Update FragmentController Enrichment Trigger
- **File**: `app/Http/Controllers/FragmentController.php`
- **Estimate**: 45 minutes
- **Description**: Add telemetry to fragment enrichment trigger in controller
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Enrichment trigger wrapped with telemetry
  - Integration with existing debug logging
  - Correlation ID propagation to enrichment pipeline
  - Error handling preserved

## Testing Tasks

### ✅ Task 5.1: Unit Tests for ProcessingTelemetryDecorator
- **File**: `tests/Unit/Services/Telemetry/ProcessingTelemetryDecoratorTest.php`
- **Estimate**: 1 hour
- **Description**: Comprehensive unit tests for telemetry decorator functionality
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Test step wrapping with success/failure scenarios
  - Test job wrapping with context propagation
  - Test key extraction for different step types
  - Test error code mapping
  - Test timing measurement accuracy

### ✅ Task 5.2: Unit Tests for HasProcessingTelemetry Trait
- **File**: `tests/Unit/Services/Telemetry/HasProcessingTelemetryTest.php`
- **Estimate**: 30 minutes
- **Description**: Unit tests for processing telemetry trait methods
- **Dependencies**: Task 1.2
- **Acceptance Criteria**:
  - Test withTelemetry helper method
  - Test step name detection
  - Test context preparation
  - Test integration with decorator

### ✅ Task 5.3: Integration Tests for Fragment Processing Pipeline
- **File**: `tests/Feature/Telemetry/FragmentProcessingTelemetryTest.php`
- **Estimate**: 1 hour
- **Description**: End-to-end telemetry validation for fragment processing
- **Dependencies**: Tasks 2.1, 3.1-3.6
- **Acceptance Criteria**:
  - Test complete ProcessFragmentJob telemetry flow
  - Validate all processing steps emit telemetry
  - Test correlation ID propagation through pipeline
  - Test error scenarios with telemetry
  - Verify generated keys logged correctly

### ✅ Task 5.4: Performance Tests for Telemetry Overhead
- **File**: `tests/Performance/ProcessingTelemetryOverheadTest.php`
- **Estimate**: 45 minutes
- **Description**: Validate <1ms per step and <5ms total pipeline overhead
- **Dependencies**: Task 2.1, Action instrumentation tasks
- **Acceptance Criteria**:
  - Benchmark individual steps with/without telemetry
  - Benchmark complete pipeline with/without telemetry
  - Confirm <1ms overhead per step
  - Confirm <5ms total pipeline overhead
  - Memory usage impact assessment

## Documentation Tasks

### ✅ Task 6.1: Create Processing Telemetry Documentation
- **File**: `docs/TELEMETRY_PROCESSING.md` (new)
- **Estimate**: 30 minutes
- **Description**: Document processing telemetry patterns and usage
- **Dependencies**: All implementation tasks
- **Acceptance Criteria**:
  - Document all processing event types
  - Example telemetry log entries
  - Usage patterns for new processing steps
  - Performance guidelines and best practices

### ✅ Task 6.2: Update CLAUDE.md with Processing Telemetry Patterns
- **File**: `CLAUDE.md`
- **Estimate**: 15 minutes
- **Description**: Add processing telemetry usage to development guide
- **Dependencies**: Task 6.1
- **Acceptance Criteria**:
  - HasProcessingTelemetry trait usage examples
  - ProcessingTelemetryDecorator usage patterns
  - Debugging with processing telemetry

## Validation Checklist

### Core Functionality
- [ ] All fragment processing steps emit start/completion telemetry
- [ ] Job-level telemetry tracks entire ProcessFragmentJob lifecycle
- [ ] Generated object keys logged without content
- [ ] Error context captured for failed processing steps
- [ ] Correlation IDs propagate through processing pipeline

### Performance Requirements
- [ ] <1ms overhead per processing step
- [ ] <5ms total overhead for complete fragment processing
- [ ] Memory usage impact <10KB per job
- [ ] No interference with job queue retry logic

### Privacy Compliance
- [ ] No fragment content logged
- [ ] Only metadata keys and measurements stored
- [ ] Generated IDs and counts logged (not actual content)
- [ ] Error messages sanitized of sensitive information

### Integration & Correlation
- [ ] Correlation IDs from TELEMETRY-001 propagate to processing
- [ ] Job IDs correlate step telemetry within single processing run
- [ ] Fragment IDs link telemetry to specific fragments
- [ ] Context propagates from controllers to jobs to actions

### Error Handling & Debugging
- [ ] Processing failures logged with complete error context
- [ ] Step timing preserved even for failed operations
- [ ] Error codes mapped appropriately for common exception types
- [ ] Telemetry failures don't break fragment processing

## Time Estimate Summary
- **Core Infrastructure**: 3.5 hours
- **Job Instrumentation**: 2 hours
- **Action Instrumentation**: 3 hours
- **Controller Integration**: 0.75 hours
- **Testing**: 3.25 hours
- **Documentation**: 0.75 hours
- **Total**: 13.25 hours (with 3.25 hour buffer included)

## Dependencies
- **TELEMETRY-001**: Correlation middleware for context propagation
- **Laravel Infrastructure**: Jobs, actions, pipeline system
- **Fragment System**: Fragment models and processing architecture
- **Testing Framework**: PHPUnit/Pest for validation

## Risk Mitigation

### Performance Risks
- Profile telemetry overhead with realistic workloads
- Consider async telemetry for non-critical events
- Optimize JSON serialization in key extraction

### Integration Risks
- Test with all existing fragment processing scenarios
- Validate compatibility with different fragment types
- Ensure transaction safety is maintained

### Privacy Risks
- Code review all key extraction logic
- Automated tests to detect content logging
- Clear documentation on privacy boundaries