# ERROR-001: On-Error Handling Implementation

## Agent Profile
**Type**: Senior Engineer/Code Reviewer  
**Specialization**: Error Handling, DSL Architecture, Fault Tolerance

## Task Overview
Implement `on_error` configuration for DSL steps to enable custom error handling and fallback logic without requiring PHP intervention.

## Context
Currently, DSL commands fail immediately when any step encounters an error. This provides poor user experience and makes commands brittle. We need configurable error handling that allows commands to gracefully recover or provide meaningful alternatives.

## Technical Requirements

### **Error Handling Configuration**
```yaml
steps:
  - type: model.query
    id: find_todo
    with:
      model: fragment
      conditions:
        - field: id
          value: "{{ ctx.todo_id }}"
    on_error:
      action: "fallback"
      steps:
        - type: notify
          with:
            message: "Todo not found, creating new one instead"
            type: "warning"
        - type: context.merge
          with:
            sources:
              - defaults:
                  id: null
                  title: "New Todo"
            
  - type: model.update
    id: update_todo
    with:
      model: fragment
      id: "{{ steps.find_todo.output.id }}"
      data:
        state:
          status: "completed"
    on_error:
      action: "continue"
      message: "Could not update todo, but continuing..."
      
  - type: notify
    with:
      message: "Todo operation completed"
```

### **Error Action Types**

#### **1. `fallback` - Execute Alternative Steps**
Run alternative step sequence when primary step fails:
```yaml
on_error:
  action: "fallback"
  steps:
    - type: notify
      with:
        message: "Primary action failed, trying alternative..."
    - type: alternative.action
```

#### **2. `continue` - Log Error and Continue**
Record error but continue execution with default/null output:
```yaml
on_error:
  action: "continue"
  message: "Step failed but continuing execution"
  default_output: null
```

#### **3. `retry` - Retry with Different Parameters**
Retry step with modified configuration:
```yaml
on_error:
  action: "retry"
  attempts: 3
  modifications:
    - timeout: 10000
    - limit: 10
```

#### **4. `abort` - Stop with Custom Message**
Stop execution with user-friendly error message:
```yaml
on_error:
  action: "abort"
  message: "Could not complete operation: {{ error.message }}"
  notify:
    type: "error"
    title: "Operation Failed"
```

## Implementation Architecture

### **Enhanced Step Execution**
```php
class CommandRunner
{
    protected function executeStep(array $stepConfig, array $context, bool $dryRun): array
    {
        $stepResult = [
            'id' => $stepConfig['id'] ?? 'step-'.uniqid(),
            'type' => $stepConfig['type'],
            'success' => false,
            'output' => null,
            'error' => null,
            'error_handled' => false,
            'fallback_executed' => false,
        ];

        try {
            // Normal step execution
            $step = $this->stepFactory->create($stepConfig['type']);
            $renderedConfig = $this->renderStepConfig($stepConfig, $context);
            $stepResult['output'] = $step->execute($renderedConfig, $context, $dryRun);
            $stepResult['success'] = true;
            
        } catch (\Exception $e) {
            $stepResult['error'] = $e->getMessage();
            
            // Handle error according to configuration
            if (isset($stepConfig['on_error'])) {
                $errorResult = $this->handleStepError($stepConfig['on_error'], $e, $context, $dryRun);
                $stepResult = array_merge($stepResult, $errorResult);
            }
        }

        return $stepResult;
    }
    
    protected function handleStepError(array $errorConfig, \Exception $error, array $context, bool $dryRun): array
    {
        $action = $errorConfig['action'] ?? 'abort';
        $result = ['error_handled' => true];
        
        switch ($action) {
            case 'fallback':
                $result['fallback_executed'] = true;
                $result['fallback_steps'] = [];
                
                foreach ($errorConfig['steps'] ?? [] as $fallbackStep) {
                    $fallbackResult = $this->executeStep($fallbackStep, $context, $dryRun);
                    $result['fallback_steps'][] = $fallbackResult;
                    
                    // Use output from last successful fallback step
                    if ($fallbackResult['success']) {
                        $result['output'] = $fallbackResult['output'];
                        $result['success'] = true;
                    }
                }
                break;
                
            case 'continue':
                $result['success'] = true;
                $result['output'] = $errorConfig['default_output'] ?? null;
                if (isset($errorConfig['message'])) {
                    // Log continue message
                    \Log::warning('Step continued after error', [
                        'message' => $errorConfig['message'],
                        'error' => $error->getMessage(),
                    ]);
                }
                break;
                
            case 'retry':
                // Implement retry logic with modifications
                $result = $this->retryStepWithModifications($errorConfig, $context, $dryRun);
                break;
                
            case 'abort':
            default:
                $result['success'] = false;
                $result['abort_message'] = $errorConfig['message'] ?? $error->getMessage();
                break;
        }
        
        return $result;
    }
}
```

### **Error Context Enhancement**
```php
class ErrorContext
{
    public function __construct(
        public string $stepId,
        public string $stepType, 
        public string $errorMessage,
        public array $stepConfig,
        public array $executionContext,
        public ?\Exception $originalException = null
    ) {}
    
    public function toArray(): array
    {
        return [
            'step_id' => $this->stepId,
            'step_type' => $this->stepType,
            'error_message' => $this->errorMessage,
            'step_config' => $this->stepConfig,
            'context_keys' => array_keys($this->executionContext),
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

## Integration with Template Engine

### **Error Data in Templates**
```yaml
on_error:
  action: "fallback"
  steps:
    - type: notify
      with:
        message: "Step {{ error.step_type }} failed: {{ error.message }}"
        type: "error"
        data:
          step_id: "{{ error.step_id }}"
          retry_available: true
```

### **Template Variables Available**
- `{{ error.message }}` - Original error message
- `{{ error.step_type }}` - Type of step that failed
- `{{ error.step_id }}` - ID of failed step
- `{{ error.context }}` - Execution context at time of failure

## Success Criteria
- [ ] All error action types implemented and tested
- [ ] Template engine integration for error data
- [ ] Comprehensive error logging and observability
- [ ] Fallback steps execute in same context as original
- [ ] Retry logic with configurable attempts and modifications
- [ ] Performance impact <5ms for error handling overhead
- [ ] Backward compatibility with existing commands

## Files to Create/Modify
- `app/Services/Commands/DSL/CommandRunner.php` (enhance)
- `app/Services/Commands/DSL/ErrorContext.php` (new)
- `app/Services/Commands/DSL/ErrorHandler.php` (new)
- `tests/Unit/DSL/ErrorHandlingTest.php` (new)
- Documentation for error handling patterns

## Testing Strategy
- Unit tests for each error action type
- Integration tests with complex fallback scenarios
- Performance testing for error handling overhead
- Edge case testing (nested errors, recursive fallbacks)
- User experience testing with error scenarios