# Condition Step YAML Parsing Fix

**Priority**: P2 (Medium)  
**Type**: Bug Fix  
**Estimated Effort**: 2-4 hours  
**Sprint**: Backlog  

## Problem Statement

During Sprint 48 testing, a YAML parsing issue was identified with condition steps in the DSL framework. Commands like `/bookmark list` that use condition templates such as `{{ ctx.body == 'list' }}` are receiving empty condition strings instead of the template expressions.

## Current Behavior

```yaml
steps:
  - id: determine-mode
    type: condition
    condition: "{{ ctx.body == 'list' }}"  # This becomes empty string
    then:
      # ... steps
```

The condition step receives `condition: ""` instead of the template, causing "Condition step requires a condition" errors.

## Root Cause Analysis Needed

1. **YAML Loading**: Verify how CommandPack loads and parses YAML files
2. **Template Rendering**: Check if template rendering happens too early in the pipeline
3. **Step Configuration**: Analyze how step configs are passed from YAML to Step classes
4. **Context Timing**: Ensure context is available when conditions are evaluated

## Expected Behavior

Condition steps should receive the raw template string `"{{ ctx.body == 'list' }}"` and evaluate it with the current context, returning true/false for step branching.

## Technical Requirements

- [ ] Fix YAML parsing to preserve condition templates
- [ ] Ensure proper template evaluation timing in ConditionStep
- [ ] Maintain backwards compatibility with existing commands
- [ ] Add comprehensive tests for condition template scenarios

## Testing Criteria

- [ ] `/bookmark list` works correctly with proper conditional logic
- [ ] All existing condition-based commands function properly
- [ ] Template expressions in conditions evaluate correctly
- [ ] No regression in working commands (help, clear, etc.)

## Files Likely Involved

- `app/Services/Commands/DSL/Steps/ConditionStep.php`
- `app/Services/Commands/DSL/CommandRunner.php`
- Command pack loading/parsing logic
- YAML condition template rendering pipeline

## Success Metrics

- All migrated commands achieve 100% success rate in benchmarks
- Condition-based branching works correctly for all command modes
- Template expressions in conditions evaluate as expected