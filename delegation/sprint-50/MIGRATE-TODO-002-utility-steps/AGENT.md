# MIGRATE-TODO-002: Utility Steps for Data Manipulation

## Agent Profile
**Type**: Senior Engineer/Code Reviewer  
**Specialization**: DSL Architecture, Data Processing, Functional Programming

## Task Overview
Create utility DSL steps (`context.merge`, `list.map`, `string.format`) to enable complex data manipulation without AI dependencies.

## Context
The DSL framework needs data manipulation capabilities for complex command flows. Currently, commands requiring data transformation resort to AI generation. These utility steps provide deterministic alternatives for common operations.

## Technical Requirements

### **Step Types to Implement**

#### **1. `context.merge` - Context Merging**
Merge data from multiple sources into execution context:
```yaml
- type: context.merge
  with:
    sources:
      - "{{ steps.parse_input.output }}"
      - "{{ steps.user_data.output }}"
      - defaults:
          status: "pending"
          created_at: "{{ now }}"
  output: merged_data
```

#### **2. `list.map` - Array Transformation**
Transform arrays with templates or filters:
```yaml
- type: list.map
  with:
    input: "{{ steps.query_results.output.results }}"
    template: 
      id: "{{ item.id }}"
      title: "{{ item.message }}"
      url: "/fragments/{{ item.id }}"
    filter: "{{ item.type == 'todo' }}"
  output: mapped_todos
```

#### **3. `string.format` - String Processing**
Format strings with templates and transformations:
```yaml
- type: string.format
  with:
    template: "Todo: {{ title }} ({{ priority }} priority)"
    data:
      title: "{{ parsed_todo.title }}"
      priority: "{{ parsed_todo.priority }}"
    transforms:
      - uppercase: "priority"
      - truncate: "title,50"
  output: formatted_string
```

#### **4. `data.transform` - General Data Transformation**
Apply transformations to structured data:
```yaml
- type: data.transform
  with:
    input: "{{ steps.raw_data.output }}"
    rules:
      - field: "due_date"
        from: "string"
        to: "carbon"
        format: "Y-m-d"
      - field: "tags"
        transform: "split"
        delimiter: ","
      - field: "priority"
        map:
          "1": "urgent"
          "2": "high"
          "3": "medium"
          "4": "low"
  output: transformed_data
```

## Implementation Plan

### **Phase 1: Context Merge Step**
Create `ContextMergeStep` for combining data sources:
- Deep merge arrays and objects
- Override policies (left-wins, right-wins, merge-arrays)
- Type coercion and validation
- Conflict resolution strategies

### **Phase 2: List Map Step**
Create `ListMapStep` for array transformations:
- Template-based mapping
- Filtering with conditions
- Nested object access
- Performance optimization for large arrays

### **Phase 3: String Format Step**
Create `StringFormatStep` for text processing:
- Template rendering with data
- Built-in transforms (uppercase, lowercase, truncate, etc.)
- Conditional formatting
- Escape handling for security

### **Phase 4: Data Transform Step**
Create `DataTransformStep` for type conversions:
- Type casting (string/int/date/boolean)
- Value mapping with lookup tables
- Array operations (split, join, filter)
- Validation and error handling

## Step Implementation Details

### **Base Utility Step Class**
```php
abstract class UtilityStep extends Step
{
    protected function validateConfig(array $config): void
    {
        // Common validation for utility steps
    }
    
    protected function renderTemplates(array $data, array $context): array
    {
        // Template rendering helper
    }
    
    protected function applyTransforms(mixed $value, array $transforms): mixed
    {
        // Apply transformation pipeline
    }
}
```

### **Error Handling Strategy**
- Graceful degradation for invalid input
- Clear error messages for configuration issues
- Type validation before transformation
- Fallback values for failed operations

### **Performance Considerations**
- Lazy evaluation for large datasets
- Memory-efficient array processing
- Caching for repeated operations
- Optimization for common patterns

## Integration with Todo Migration

### **Enhanced Todo Command Flow**
```yaml
steps:
  - type: text.parse
    id: parse_input
    with:
      input: "{{ ctx.body }}"
      parser: "todo"
  
  - type: context.merge
    id: merge_defaults
    with:
      sources:
        - "{{ steps.parse_input.output }}"
        - defaults:
            status: "pending"
            created_at: "{{ now }}"
            vault_id: "{{ ctx.vault_id }}"
  
  - type: string.format
    id: format_title
    with:
      template: "{{ title | title_case }}"
      data: "{{ steps.merge_defaults.output }}"
  
  - type: data.transform
    id: transform_state
    with:
      input: "{{ steps.merge_defaults.output }}"
      rules:
        - field: "priority"
          map:
            "urgent": 5
            "high": 4
            "medium": 3
            "low": 2
  
  - type: model.create
    with:
      model: fragment
      data:
        message: "{{ steps.format_title.output }}"
        type: "todo"
        state: "{{ steps.transform_state.output }}"
```

## Success Criteria
- [ ] All utility steps support dry run mode
- [ ] Performance <5ms per step for typical operations
- [ ] Comprehensive error handling and validation
- [ ] Template engine integration for dynamic operations
- [ ] Memory efficient for large data processing
- [ ] Complete test coverage for edge cases

## Files to Create
- `app/Services/Commands/DSL/Steps/ContextMergeStep.php`
- `app/Services/Commands/DSL/Steps/ListMapStep.php`
- `app/Services/Commands/DSL/Steps/StringFormatStep.php`
- `app/Services/Commands/DSL/Steps/DataTransformStep.php`
- `app/Services/Commands/DSL/Steps/UtilityStep.php` (base class)
- Test files for each step type

## Testing Strategy
- Unit tests for each step type with edge cases
- Performance benchmarks for large data operations
- Integration tests with template engine
- Memory usage testing for array operations
- Error handling validation for invalid configurations