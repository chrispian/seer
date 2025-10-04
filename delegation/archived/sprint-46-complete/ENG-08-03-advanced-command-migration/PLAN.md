# ENG-08-03: Advanced Command Migration (Batch 2)

## Objective
Migrate 8 advanced commands including complex workflows, external integrations, and conflict resolution between dual implementations while maintaining complete functional equivalence and system performance.

## Scope & Deliverables

### 1. Conflict Resolution Phase (3-4 hours)

#### RecallCommand Unification
- **Analysis**: Compare hardcoded vs YAML implementations
- **Design**: Unified recall workflow combining all features
- **Implementation**: Enhanced recall.yaml with full functionality
- **Testing**: Validate feature parity and performance

#### TodoCommand Consolidation  
- **Analysis**: Feature comparison and gap identification
- **Design**: Comprehensive todo management workflow
- **Implementation**: Enhanced todo.yaml with merged capabilities
- **Testing**: Validate CRUD operations and integrations

#### InboxCommand Integration
- **Analysis**: Inbox management feature comparison
- **Design**: Unified inbox processing workflow
- **Implementation**: Enhanced inbox.yaml with full feature set
- **Testing**: Validate inbox operations and filtering

### 2. Complex Command Migration (3-4 hours)

#### FragCommand Migration
- **Target**: `app/Actions/Commands/FragCommand.php` → `fragments/commands/frag/command.yaml`
- **Complexity**: High - Multi-step workflows, AI integration
- **Features**: Fragment creation, metadata management, AI processing
- **Implementation**: Advanced workflow with AI integration steps

#### ComposeCommand Migration
- **Target**: `app/Actions/Commands/ComposeCommand.php` → `fragments/commands/compose/command.yaml`
- **Complexity**: High - Template processing, composition logic
- **Features**: Message composition, template engine integration
- **Implementation**: Multi-step composition workflow
- **Alias**: `/c`

### 3. Context Management Migration (2-3 hours)

#### VaultCommand Migration
- **Target**: `app/Actions/Commands/VaultCommand.php` → `fragments/commands/vault/command.yaml`
- **Features**: Vault operations, security management
- **Implementation**: Vault management workflow
- **Alias**: `/v`

#### ProjectCommand Migration
- **Target**: `app/Actions/Commands/ProjectCommand.php` → `fragments/commands/project/command.yaml`
- **Features**: Project context management, workspace integration
- **Implementation**: Project management workflow  
- **Alias**: `/p`

#### ContextCommand Migration
- **Target**: `app/Actions/Commands/ContextCommand.php` → `fragments/commands/context/command.yaml`
- **Features**: Context switching, state management
- **Implementation**: Context management workflow
- **Alias**: `/ctx`

## Implementation Strategy

### Phase 1: Conflict Resolution (3-4 hours)
**Priority**: Resolve dual implementations first to avoid confusion

#### Step 1: Feature Analysis
- Compare hardcoded vs YAML implementations
- Document unique features in each system
- Identify integration points and dependencies
- Plan unified feature set

#### Step 2: Unified Design
- Design comprehensive YAML workflows
- Incorporate all features from both systems
- Plan migration path from hardcoded to unified YAML
- Design testing strategy for validation

#### Step 3: Implementation & Testing
- Implement enhanced YAML configurations
- Test feature parity with both original systems
- Validate performance and integration
- Document migration decisions

### Phase 2: Complex Command Migration (3-4 hours)
**Priority**: Migrate high-complexity commands with advanced workflows

#### Advanced DSL Implementation
- Implement ConditionalStep enhancements
- Create WorkflowStep for multi-step processes
- Enhance IntegrationStep for external services
- Implement error handling and rollback logic

#### Complex Workflow Design
- Design multi-step workflows for FragCommand
- Plan AI integration patterns for ComposeCommand
- Implement template processing workflows
- Add comprehensive error handling

### Phase 3: Context Management Migration (2-3 hours)
**Priority**: Migrate context and state management commands

#### State Management Implementation
- Implement vault security operations
- Design project context switching
- Plan state persistence patterns
- Add context validation logic

## Technical Requirements

### Enhanced DSL Step Types

#### ConditionalStep Enhancement
```yaml
- id: complex-conditional
  type: conditional
  with:
    conditions:
      - condition: "{{ ctx.user.permissions.admin }}"
        then:
          - type: vault.admin-operation
            with: { action: "{{ ctx.action }}" }
      - condition: "{{ ctx.workspace.type == 'enterprise' }}"
        then:
          - type: vault.enterprise-operation
            with: { action: "{{ ctx.action }}" }
    default:
      - type: notify
        with: { message: "Insufficient permissions" }
```

#### WorkflowStep (New)
```yaml
- id: frag-creation-workflow
  type: workflow
  with:
    steps:
      - validate-input
      - process-ai-content
      - create-fragment
      - update-metadata
      - notify-completion
    error_handling: 
      strategy: rollback
      on_error:
        - cleanup-partial-state
        - notify-failure
```

#### IntegrationStep Enhancement
```yaml
- id: ai-integration
  type: integration
  with:
    service: "ai-provider"
    operation: "generate"
    parameters:
      prompt: "{{ prompts.frag-creation }}"
      context: "{{ ctx.input }}"
      model: "{{ ctx.model | default: 'gpt-4' }}"
    retry_policy:
      attempts: 3
      backoff: exponential
      timeout: 60
```

### Alias Support Implementation
Comprehensive alias management:
```yaml
triggers:
  slash: "/fragment"
  aliases: ["/frag", "/f"]
  input_mode: "inline"
```

### Error Handling Patterns
Advanced error handling and rollback:
```yaml
steps:
  - id: main-operation
    type: fragment.create
    with:
      content: "{{ ctx.content }}"
    error_handling:
      rollback: true
      notify: true
      message: "Fragment creation failed"
```

## Testing Strategy

### Conflict Resolution Testing
- **Feature Parity**: Validate all features from both systems work
- **Performance**: Ensure unified commands perform equivalently
- **Integration**: Test with dependent systems and services
- **Migration**: Validate smooth transition from dual to unified system

### Complex Workflow Testing
- **Multi-Step Validation**: Test each step in complex workflows
- **Error Handling**: Test error conditions and rollback logic
- **Integration Points**: Test external service connections
- **Performance**: Benchmark complex workflow execution

### Alias and Compatibility Testing
- **Alias Functionality**: Test all command aliases work correctly
- **Response Format**: Validate response structure compatibility
- **Frontend Integration**: Test with ChatIsland and command modals
- **Autocomplete**: Validate command discovery and suggestions

## Risk Mitigation

### Conflict Resolution Risks
- **Feature Loss**: Comprehensive testing to ensure no functionality lost
- **Performance Impact**: Benchmark unified commands vs originals
- **Integration Breakage**: Test all dependent systems thoroughly

### Complex Migration Risks
- **Workflow Complexity**: Break down into testable steps
- **External Dependencies**: Validate all integrations work correctly
- **State Management**: Test state consistency and persistence

### Performance Risks
- **Multi-Step Overhead**: Optimize workflow execution
- **External Service Latency**: Implement proper timeout and retry logic
- **Resource Usage**: Monitor memory and CPU usage patterns

## Success Metrics

### Functional Requirements
- ✅ All 8 commands migrated with identical functionality
- ✅ Conflict resolution completed with unified implementations
- ✅ Complex workflows maintain full feature set
- ✅ All aliases and shortcuts work correctly

### Performance Requirements
- ✅ Complex workflows execute within performance thresholds
- ✅ Multi-step processes optimized for efficiency
- ✅ External integrations maintain responsiveness
- ✅ State management operations perform equivalently

### Integration Requirements
- ✅ Frontend compatibility maintained completely
- ✅ External service integrations work seamlessly
- ✅ Command discovery and autocomplete functional
- ✅ Error handling and rollback logic operational

## Deliverables

### Implementation Artifacts
- 8 migrated YAML command configurations
- Enhanced DSL step implementations (ConditionalStep, WorkflowStep, IntegrationStep)
- Conflict resolution documentation
- Comprehensive test suites

### Documentation
- Complex migration decision log
- Enhanced DSL usage patterns
- Conflict resolution strategies
- Integration validation results

### Quality Assurance
- Performance benchmark reports
- Integration test results
- Error handling validation
- Migration completion verification

This batch handles the most complex commands in the system, establishing patterns for sophisticated workflows and resolving system conflicts while maintaining full functionality and performance.