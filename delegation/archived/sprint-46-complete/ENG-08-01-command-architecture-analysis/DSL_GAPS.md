# DSL Framework Gaps Analysis

## Overview

The current DSL framework has 6 step types but needs significant extensions to support all hardcoded command functionality. This document details the gaps and required implementations.

## Current Step Types
1. **`transform`** - Template rendering and data transformation
2. **`ai.generate`** - AI-powered content generation  
3. **`fragment.create`** - Create fragments with metadata
4. **`search.query`** - Search functionality
5. **`notify`** - User notifications with panel support
6. **`tool.call`** - External tool execution

## Required Step Types for Migration

### **1. Fragment Query Operations**

#### **`fragment.query`** - Advanced Fragment Querying
```yaml
- id: get-todos
  type: fragment.query
  with:
    type: "todo"
    filters:
      state.status: "open"
      tags: ["urgent"]
    limit: 25
    order: "latest"
    with_relations: ["type"]
```

**Capabilities Needed:**
- Complex WHERE clauses with JSON path queries
- Relationship loading
- Sorting and pagination
- Multiple filter types (AND/OR logic)
- Count queries

#### **`fragment.update`** - Fragment Modification
```yaml
- id: complete-todo
  type: fragment.update
  with:
    id: "{{ ctx.fragment_id }}"
    data:
      state.status: "complete"
      state.completed_at: "{{ now }}"
```

**Capabilities Needed:**
- Update existing fragments
- JSON state modifications
- Conditional updates
- Bulk updates

#### **`fragment.delete`** - Fragment Removal
```yaml
- id: delete-fragment
  type: fragment.delete
  with:
    id: "{{ ctx.fragment_id }}"
    soft_delete: true
```

### **2. Conditional Logic**

#### **`condition`** - Branching Logic
```yaml
- id: check-arguments
  type: condition
  condition: "{{ ctx.identifier | length > 0 }}"
  then:
    - type: transform
      template: "Creating todo: {{ ctx.identifier }}"
  else:
    - type: notify
      with:
        message: "Please provide todo content"
        level: "error"
```

**Capabilities Needed:**
- Boolean expression evaluation
- Nested step execution
- Multiple condition types (if/elif/else)
- Variable existence checks

### **3. State Management**

#### **`state.get`** - Retrieve State
```yaml
- id: get-session
  type: state.get
  with:
    key: "current_session"
    scope: "user"
```

#### **`state.set`** - Store State  
```yaml
- id: save-session
  type: state.set
  with:
    key: "current_session"
    value: "{{ steps.create-session.output.id }}"
    scope: "user"
    ttl: 3600
```

**Capabilities Needed:**
- User-scoped state storage
- Session-scoped state storage
- TTL support
- State cleanup

### **4. Complex Response Handling**

#### **`response.panel`** - Panel Response Generation
```yaml
- id: show-todos
  type: response.panel
  with:
    type: "recall"
    panel_data:
      type: "todo"
      status: "{{ ctx.status }}"
      fragments: "{{ steps.query-todos.output.results }}"
      message: "Found {{ steps.query-todos.output.count }} todos"
```

#### **`response.toast`** - Toast Notification
```yaml
- id: success-message
  type: response.toast
  with:
    type: "success"
    title: "Todo Created"
    message: "{{ steps.create-todo.output.message }}"
    data:
      fragment_id: "{{ steps.create-todo.output.id }}"
```

### **5. Loop Operations**

#### **`loop`** - Iterate Over Data
```yaml
- id: process-fragments
  type: loop
  with:
    items: "{{ steps.get-fragments.output.results }}"
    steps:
      - type: fragment.update
        with:
          id: "{{ item.id }}"
          data:
            processed: true
```

### **6. User Context**

#### **`user.context`** - Get User Information
```yaml
- id: get-user
  type: user.context
  with:
    fields: ["id", "name", "settings"]
```

### **7. Database Operations**

#### **`database.query`** - Direct Database Access
```yaml
- id: complex-query
  type: database.query
  with:
    query: |
      SELECT f.*, t.label as type_label 
      FROM fragments f 
      JOIN types t ON f.type_id = t.id 
      WHERE f.created_at > ?
    params: ["{{ ctx.since_date }}"]
```

## Implementation Priority

### **Phase 1: Core Extensions (Required for basic migrations)**
1. **`fragment.query`** - Essential for most commands
2. **`fragment.update`** - Needed for todo/bookmark operations  
3. **`condition`** - Required for complex logic
4. **`response.panel`** - Needed for proper UI responses

### **Phase 2: Enhanced Functionality**
1. **`state.get`** / **`state.set`** - Session management
2. **`response.toast`** - Better UX feedback
3. **`user.context`** - User-aware operations

### **Phase 3: Advanced Features**
1. **`loop`** - Batch operations
2. **`fragment.delete`** - Complete CRUD support
3. **`database.query`** - Complex queries

## Template Engine Enhancements

### **Current Capabilities**
- Basic variable interpolation: `{{ ctx.variable }}`
- Simple filters: `{{ value | default: "fallback" }}`
- JSON output: `{{ data | json }}`

### **Required Enhancements**

#### **1. Expression Evaluation**
```yaml
# Mathematical operations
count: "{{ steps.query.output.count + 1 }}"

# Boolean logic
show_all: "{{ ctx.status == 'all' or ctx.force == true }}"

# String operations
title: "{{ ctx.message | truncate: 50 | capitalize }}"
```

#### **2. Advanced Filters**
```yaml
# Date formatting
created: "{{ fragment.created_at | date: 'Y-m-d H:i' }}"

# Array operations
tags: "{{ fragment.tags | join: ', ' }}"
first_tag: "{{ fragment.tags | first }}"

# Conditional rendering
status: "{{ fragment.state.status | default: 'unknown' | upper }}"
```

#### **3. Control Structures**
```yaml
template: |
  {% if ctx.status == 'open' %}
    Open Todos ({{ count }})
  {% else %}
    {{ status | capitalize }} Todos ({{ count }})
  {% endif %}
```

## Command Response System Enhancement

### **Current Response Format**
```php
return response()->json([
    'success' => true,
    'type' => 'success',
    'message' => 'Basic message',
    'fragments' => [],
    'shouldOpenPanel' => false,
    'panelData' => null,
]);
```

### **Required Response Types**

#### **1. Recall Response**
```php
'type' => 'recall',
'shouldOpenPanel' => true,
'panelData' => [
    'type' => 'todo',
    'status' => 'open', 
    'fragments' => $results,
    'message' => 'Found 5 todos'
]
```

#### **2. Inbox Response** 
```php
'type' => 'inbox',
'shouldOpenPanel' => true,
'panelData' => [
    'action' => 'pending',
    'fragments' => $results,
    'type' => 'pending'
]
```

#### **3. System Response**
```php
'type' => 'system',
'shouldShowSuccessToast' => true,
'toastData' => [
    'title' => 'Todo Created',
    'message' => 'Todo added successfully',
    'fragmentId' => 123
]
```

## Error Handling Enhancement

### **Current Error Handling**
- Basic try/catch in step execution
- Simple error messages

### **Required Enhancements**

#### **1. Step-Level Error Handling**
```yaml
- id: risky-operation
  type: fragment.create
  on_error:
    - type: notify
      with:
        message: "Failed to create fragment: {{ error.message }}"
        level: "error"
  retry:
    attempts: 3
    delay: 1000
```

#### **2. Validation Steps**
```yaml
- id: validate-input
  type: validate
  with:
    rules:
      identifier: "required|min:3"
      status: "in:open,complete,pending"
  on_fail:
    - type: notify
      with:
        message: "Invalid input: {{ validation.errors | join: ', ' }}"
        level: "error"
```

## Implementation Strategy

### **1. Extend StepFactory**
Add new step types incrementally:
```php
protected array $stepClasses = [
    // Current
    'transform' => TransformStep::class,
    'ai.generate' => AiGenerateStep::class,
    'fragment.create' => FragmentCreateStep::class,
    'search.query' => SearchQueryStep::class,
    'notify' => NotifyStep::class,
    'tool.call' => ToolCallStep::class,
    
    // New - Phase 1
    'fragment.query' => FragmentQueryStep::class,
    'fragment.update' => FragmentUpdateStep::class,
    'condition' => ConditionStep::class,
    'response.panel' => ResponsePanelStep::class,
    
    // New - Phase 2
    'state.get' => StateGetStep::class,
    'state.set' => StateSetStep::class,
    'response.toast' => ResponseToastStep::class,
    'user.context' => UserContextStep::class,
];
```

### **2. Template Engine Enhancement**
Extend `TemplateEngine` with:
- Expression parser
- Advanced filter system
- Control structure support

### **3. Response System Refactor**
Create specialized response builders:
- `PanelResponseBuilder`
- `ToastResponseBuilder`  
- `SystemResponseBuilder`

## Testing Strategy

### **1. Step Unit Tests**
Each new step type needs comprehensive testing:
```php
class FragmentQueryStepTest extends TestCase
{
    public function test_basic_query_execution()
    public function test_complex_filters()
    public function test_relationship_loading()
    public function test_error_handling()
}
```

### **2. Integration Tests**
Full command execution testing:
```php
class TodoCommandMigrationTest extends TestCase
{
    public function test_todo_creation_parity()
    public function test_todo_listing_parity()
    public function test_todo_completion_parity()
}
```

### **3. Performance Testing**
Compare execution times:
- Hardcoded vs YAML performance
- Memory usage comparison
- Database query efficiency

## Success Criteria

1. **Functional Parity**: All hardcoded command features available in DSL
2. **Performance Parity**: No significant performance degradation
3. **Maintainability**: Easier to add/modify commands
4. **Consistency**: Uniform patterns across all commands
5. **Testing**: Comprehensive test coverage for all new components