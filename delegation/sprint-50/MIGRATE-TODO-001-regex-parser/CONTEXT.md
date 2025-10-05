# MIGRATE-TODO-001: Context & Analysis

## Current Todo Command Analysis
Based on codebase examination, the current `/todo` command structure:

### **Current Implementation**
```yaml
# fragments/commands/todo/command.yaml
steps:
  - type: ai.generate
    with:
      prompt: "{{ prompts.parse }}"
      context: "{{ ctx.body }}"
    output: parsed_todo
  
  - type: model.create
    with:
      model: fragment
      data:
        message: "{{ parsed_todo.title }}"
        type: "todo"
        state: "{{ parsed_todo.state }}"
```

### **AI Dependencies Identified**
- **Parse Step**: Uses AI to interpret natural language
- **Prompt Template**: Located in `prompts/parse.md`
- **Non-deterministic**: Same input can produce different outputs
- **External Dependency**: Requires AI service availability

## Target Architecture

### **Deterministic Alternative**
```yaml
# New deterministic approach
steps:
  - type: text.parse
    with:
      input: "{{ ctx.body }}"
      parser: "todo"
      rules:
        extract_due_date: true
        extract_priority: true
        extract_tags: true
        extract_title: true
    output: parsed_todo
    
  - type: validate
    with:
      data: "{{ parsed_todo }}"
      rules:
        title: "required|string|max:255"
        priority: "in:low,medium,high,urgent"
        due_date: "nullable|date"
    
  - type: model.create
    with:
      model: fragment
      data:
        message: "{{ parsed_todo.title }}"
        type: "todo"
        state:
          status: "pending"
          priority: "{{ parsed_todo.priority }}"
          due_date: "{{ parsed_todo.due_date }}"
          tags: "{{ parsed_todo.tags }}"
```

## Regex Pattern Library

### **Due Date Patterns**
```php
private array $dueDatePatterns = [
    // Relative dates
    '/\b(today|tomorrow)\b/i' => 'relative',
    '/\bnext (week|month|year)\b/i' => 'relative_period',
    '/\bin (\d+) (days?|weeks?|months?)\b/i' => 'relative_duration',
    
    // Specific dates
    '/\b(\d{4}-\d{2}-\d{2})\b/' => 'iso_date',
    '/\b(\d{1,2}\/\d{1,2}\/\d{4})\b/' => 'us_date',
    '/\b(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)\b/i' => 'weekday',
];
```

### **Priority Patterns**
```php
private array $priorityPatterns = [
    '/\b(urgent|critical|asap)\b/i' => 'urgent',
    '/\b(important|high priority|!!)\b/i' => 'high',
    '/\b(low priority|later|someday)\b/i' => 'low',
    '/\bP([1-4])\b/i' => 'priority_number',
];
```

### **Tag Patterns**
```php
private array $tagPatterns = [
    '/#([a-zA-Z0-9_]+)/' => 'hashtag',
    '/@([a-zA-Z0-9_]+)/' => 'context',
    '/\+([\w\s]+)/' => 'project',
];
```

## Business Rule Engine

### **Default Values**
- **Priority**: "medium" if not specified
- **Status**: "pending" for new todos
- **Due Date**: null if not extractable
- **Tags**: Empty array if none found

### **Title Extraction Logic**
1. Remove detected metadata (dates, priorities, tags)
2. Take first meaningful phrase (up to first sentence/comma)
3. Trim and capitalize first letter
4. Fallback to full text if no clear title

### **Validation Rules**
- Title: Required, 1-255 characters
- Priority: Must be low/medium/high/urgent
- Due Date: Valid date or null
- Tags: Array of alphanumeric strings

## Migration Strategy

### **Phase 1: Service Implementation**
- Create `TodoTextParser` service
- Implement regex pattern matching
- Add business rule processing
- Unit test all patterns

### **Phase 2: DSL Step Creation**
- Create `TextParseStep` class
- Register in `StepFactory`
- Add configuration validation
- Integration testing

### **Phase 3: Command Migration**
- Update todo command YAML
- Add validation step
- Maintain backward compatibility
- User acceptance testing

### **Rollback Plan**
- Keep original AI-based command as `todo-ai`
- Allow gradual migration
- Feature flag for parser selection
- Performance comparison metrics

## Success Metrics
- **Accuracy**: 90%+ correct parsing vs manual validation
- **Performance**: <10ms parsing time
- **Coverage**: Handles common todo patterns
- **Reliability**: Zero external dependencies
- **Compatibility**: Existing todo interface unchanged