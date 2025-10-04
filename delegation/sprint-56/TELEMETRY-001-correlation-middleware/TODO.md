# TELEMETRY-001: Request Correlation Middleware - Task List

## High Priority Tasks

### ✅ Task 1.1: Create Correlation Middleware Class
- **File**: `app/Http/Middleware/InjectCorrelationId.php`
- **Estimate**: 45 minutes
- **Description**: Implement middleware that generates UUID correlation ID and injects it into request attributes and log context
- **Dependencies**: None
- **Acceptance Criteria**: 
  - Generates unique UUID per request
  - Stores in request attributes
  - Adds to Laravel Log context
  - Includes static user_id for single-user environment

### ✅ Task 1.2: Register Middleware in HTTP Kernel
- **File**: `app/Http/Kernel.php`
- **Estimate**: 15 minutes  
- **Description**: Add InjectCorrelationId to global middleware stack
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Middleware runs on all HTTP requests
  - Correct position in middleware stack
  - No conflicts with existing middleware

### ✅ Task 2.1: Create Correlation Context Helper
- **File**: `app/Services/Telemetry/CorrelationContext.php`
- **Estimate**: 30 minutes
- **Description**: Helper service for accessing correlation ID throughout application
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Static methods for getting correlation ID
  - Fallback UUID generation when no request context
  - Job context preparation helper

### ✅ Task 2.2: Create Job Correlation Trait
- **File**: `app/Jobs/HasCorrelationContext.php`
- **Estimate**: 45 minutes
- **Description**: Trait for jobs to inherit correlation context from dispatching request
- **Dependencies**: Task 2.1
- **Acceptance Criteria**:
  - Trait provides correlation context methods
  - Log context setup for job execution
  - Compatible with existing job classes

### ✅ Task 3.1: Update ProcessFragmentJob
- **File**: `app/Jobs/ProcessFragmentJob.php`
- **Estimate**: 30 minutes
- **Description**: Integrate correlation context with main fragment processing job
- **Dependencies**: Task 2.2
- **Acceptance Criteria**:
  - Uses HasCorrelationContext trait
  - Sets up correlation logging in handle method
  - All logs include correlation ID

### ✅ Task 3.2: Update Job Dispatching in Controllers
- **Files**: 
  - `app/Http/Controllers/ChatApiController.php`
  - `app/Http/Controllers/CommandController.php`
  - `app/Http/Controllers/FragmentController.php`
- **Estimate**: 45 minutes
- **Description**: Update job dispatching to include correlation context
- **Dependencies**: Task 2.2
- **Acceptance Criteria**:
  - All job dispatches use withCorrelation() method
  - Correlation IDs propagate from request to job
  - No breaking changes to existing functionality

## Testing Tasks

### ✅ Task 4.1: Unit Tests for Middleware
- **File**: `tests/Unit/Middleware/InjectCorrelationIdTest.php`
- **Estimate**: 30 minutes
- **Description**: Test correlation ID generation and injection
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Tests correlation ID presence
  - Validates UUID format
  - Tests log context injection

### ✅ Task 4.2: Integration Tests for Correlation Tracking
- **File**: `tests/Feature/Telemetry/CorrelationTrackingTest.php`  
- **Estimate**: 45 minutes
- **Description**: End-to-end correlation tracking validation
- **Dependencies**: Tasks 1.1, 3.2
- **Acceptance Criteria**:
  - Tests correlation in chat API
  - Tests correlation in command execution
  - Validates job correlation inheritance

### ✅ Task 4.3: Performance Testing
- **File**: `tests/Performance/CorrelationOverheadTest.php`
- **Estimate**: 30 minutes
- **Description**: Validate <1ms overhead requirement
- **Dependencies**: Task 1.1
- **Acceptance Criteria**:
  - Benchmark middleware overhead
  - Confirm <1ms per request impact
  - Memory usage validation

## Documentation Tasks

### ✅ Task 5.1: Update CLAUDE.md with Correlation Usage
- **File**: `CLAUDE.md`
- **Estimate**: 15 minutes
- **Description**: Document correlation ID patterns for future development
- **Dependencies**: All implementation tasks
- **Acceptance Criteria**:
  - Usage examples for getting correlation ID
  - Job correlation patterns
  - Debugging with correlation IDs

## Validation Checklist

- [ ] Correlation ID appears in all HTTP request logs
- [ ] Job logs include correlation ID from dispatching request  
- [ ] Chat API requests have end-to-end correlation tracking
- [ ] Command execution tracks correlation through DSL steps
- [ ] Fragment processing jobs maintain correlation context
- [ ] Performance overhead <1ms per request
- [ ] All tests passing
- [ ] No breaking changes to existing functionality

## Time Estimate Summary
- **Implementation Tasks**: 4.5 hours
- **Testing Tasks**: 1.75 hours  
- **Documentation**: 0.25 hours
- **Total**: 6.5 hours (with 0.5 hour buffer included)

## Dependencies
- Laravel 12 HTTP middleware system
- Existing logging infrastructure  
- Current job queue system
- No external dependencies required