# Advanced Command Migration Context

## Migration Target Commands

### Batch 2: Advanced Commands (8 commands)
These commands represent complex functionality with sophisticated workflows, external integrations, and potential system conflicts:

#### High-Complexity Commands (5)

**1. FragCommand**
- **Implementation**: `app/Actions/Commands/FragCommand.php`
- **Complexity**: High
- **Features**: Complex fragment operations, AI integration, metadata management
- **Challenges**: Multi-step workflows, external API calls, complex validation

**2. ComposeCommand** 
- **Implementation**: `app/Actions/Commands/ComposeCommand.php`
- **Complexity**: High
- **Features**: Message composition, template processing, AI integration
- **Challenges**: Multi-step composition logic, template engine integration

**3. RecallCommand** (Conflict Resolution)
- **Implementation**: `app/Actions/Commands/RecallCommand.php`
- **Existing YAML**: `fragments/commands/recall/command.yaml`
- **Complexity**: High
- **Challenges**: Reconcile dual implementations, preserve all functionality

**4. TodoCommand** (Conflict Resolution)
- **Implementation**: `app/Actions/Commands/TodoCommand.php`
- **Existing YAML**: `fragments/commands/todo/command.yaml`
- **Complexity**: High
- **Challenges**: Merge dual implementations, maintain feature parity

**5. InboxCommand** (Conflict Resolution)
- **Implementation**: `app/Actions/Commands/InboxCommand.php`
- **Existing YAML**: `fragments/commands/inbox/command.yaml`
- **Complexity**: High
- **Challenges**: Unify inbox management, resolve functional overlaps

#### Medium-Complexity Commands (3)

**6. VaultCommand**
- **Implementation**: `app/Actions/Commands/VaultCommand.php`
- **Complexity**: Medium
- **Features**: Vault management, security operations, context integration
- **Alias**: `/v`

**7. ProjectCommand**
- **Implementation**: `app/Actions/Commands/ProjectCommand.php`
- **Complexity**: Medium
- **Features**: Project context management, workspace integration
- **Alias**: `/p`

**8. ContextCommand**
- **Implementation**: `app/Actions/Commands/ContextCommand.php`
- **Complexity**: Medium
- **Features**: Context switching, state management
- **Alias**: `/ctx`

## System Conflict Analysis

### Dual Implementation Conflicts
Three commands exist in both hardcoded and YAML systems:

#### RecallCommand Conflict
**Hardcoded Features**:
- Advanced fragment recall logic
- Complex search integration
- State management capabilities

**YAML Features**:
- Basic fragment retrieval
- Simple query patterns
- Template-based responses

**Resolution Strategy**: Merge capabilities, preserve advanced features

#### TodoCommand Conflict
**Hardcoded Features**:
- Todo management operations
- Database integration
- Complex CRUD operations

**YAML Features**:
- Basic todo creation
- Simple workflow patterns
- Fragment integration

**Resolution Strategy**: Unify into comprehensive todo management

#### InboxCommand Conflict
**Hardcoded Features**:
- Advanced inbox management
- Complex filtering and processing
- Multi-step workflows

**YAML Features**:
- Basic inbox operations
- Simple message handling
- Fragment-based processing

**Resolution Strategy**: Consolidate into unified inbox system

## Advanced DSL Requirements

### Complex Workflow Patterns
Advanced commands require sophisticated DSL capabilities:

#### Multi-Step Workflows
```yaml
steps:
  - id: validate-input
    type: conditional
    with:
      condition: "{{ ctx.input | length > 0 }}"
      then:
        - type: transform
          template: "{{ ctx.input | process }}"
      else:
        - type: notify
          with: { message: "Input required" }

  - id: process-content
    type: ai.generate
    with:
      prompt: "{{ prompts.process }}"
      context: "{{ steps.validate-input.output }}"

  - id: create-fragments
    type: fragment.create
    with:
      content: "{{ steps.process-content.output }}"
      metadata:
        command: "frag"
        processed: true
```

#### External Integration Patterns
```yaml
steps:
  - id: api-call
    type: tool.call
    with:
      service: "external-api"
      endpoint: "/process"
      data: "{{ ctx.payload }}"

  - id: handle-response
    type: conditional
    with:
      condition: "{{ steps.api-call.output.success }}"
      then:
        - type: fragment.create
          with:
            content: "{{ steps.api-call.output.data }}"
      else:
        - type: notify
          with:
            message: "External service error"
            level: "error"
```

### Alias Management
Complex alias patterns:
```yaml
triggers:
  slash: "/vault"
  aliases: ["/v"]
  input_mode: "inline"
```

## Migration Strategies

### Conflict Resolution Approach
1. **Feature Analysis**: Compare hardcoded vs YAML implementations
2. **Capability Mapping**: Identify unique features in each system
3. **Unified Design**: Create comprehensive YAML that includes all features
4. **Gradual Migration**: Phase out hardcoded while testing YAML
5. **Validation**: Ensure no functionality loss

### Complex Workflow Migration
1. **Decomposition**: Break complex commands into DSL steps
2. **State Mapping**: Ensure state management equivalence
3. **Integration Points**: Maintain external service connections
4. **Error Handling**: Preserve complex error handling logic
5. **Performance**: Optimize multi-step workflows

### Testing Strategy
1. **Functional Parity**: Side-by-side comparison testing
2. **Integration Testing**: Test with all dependent systems
3. **Performance Testing**: Ensure complex workflows remain efficient
4. **Edge Case Testing**: Comprehensive boundary and error testing
5. **Conflict Resolution Testing**: Validate merged functionality

## Technical Implementation

### Enhanced DSL Requirements
Based on command analysis, implement advanced step types:

#### ConditionalStep Enhancement
```yaml
- id: complex-logic
  type: conditional
  with:
    conditions:
      - condition: "{{ ctx.user.role == 'admin' }}"
        then: [admin-workflow-steps]
      - condition: "{{ ctx.workspace.type == 'enterprise' }}"
        then: [enterprise-workflow-steps]
    default: [standard-workflow-steps]
```

#### WorkflowStep (New)
```yaml
- id: multi-step-process
  type: workflow
  with:
    steps:
      - validate-permissions
      - process-content
      - create-artifacts
      - notify-completion
    error_handling: rollback
    timeout: 300
```

#### IntegrationStep (Enhanced)
```yaml
- id: external-service
  type: integration
  with:
    service: "ai-provider"
    operation: "generate"
    parameters:
      prompt: "{{ ctx.prompt }}"
      model: "{{ ctx.model }}"
    retry_policy:
      attempts: 3
      backoff: exponential
```

## Performance Considerations

### Complex Workflow Optimization
- **Step Parallelization**: Run independent steps concurrently
- **Caching Strategy**: Cache expensive operations and API calls
- **Resource Management**: Optimize memory usage in multi-step workflows
- **Lazy Loading**: Load resources only when needed

### Database Optimization
- **Query Optimization**: Efficient database operations in DSL steps
- **Transaction Management**: Proper transaction handling in workflows
- **Connection Pooling**: Optimize database connections
- **Index Usage**: Leverage database indexes for performance

## Integration Requirements

### Backward Compatibility
- Maintain all existing command interfaces
- Preserve response formats and structures
- Support all current aliases and shortcuts
- Ensure seamless frontend integration

### Service Integration
- Maintain AI provider connections
- Preserve external API integrations
- Support file system operations
- Maintain database transaction integrity

### State Management
- Preserve session state handling
- Maintain context switching capabilities
- Support complex state persistence
- Ensure state consistency across workflows