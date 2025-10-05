# ENG-08-01: Command Architecture Analysis & Foundation

## Objective
Conduct comprehensive analysis of 18 hardcoded commands and establish the technical foundation for migrating them to the file-based YAML DSL system.

## Scope & Deliverables

### 1. Command Audit & Classification (2-3 hours)

#### Hardcoded Command Analysis
Analyze each of the 18 hardcoded commands in `app/Actions/Commands/`:

**Core Commands (5):**
- `SessionCommand`: Session management and context switching
- `HelpCommand`: Command discovery and documentation
- `ClearCommand`: Chat clearing and state reset
- `SearchCommand`: Fragment search and filtering
- `BookmarkCommand`: Fragment bookmarking operations

**Advanced Commands (8):**
- `FragCommand`: Fragment creation and management
- `VaultCommand`: Vault operations and management
- `ProjectCommand`: Project context and operations
- `ContextCommand`: Context switching and management
- `ComposeCommand`: Message composition features
- `RecallCommand`: Fragment recall (conflicts with YAML version)
- `TodoCommand`: Todo management (conflicts with YAML version)
- `InboxCommand`: Inbox operations (conflicts with YAML version)

**Utility Commands (5):**
- `JoinCommand`: Channel/session joining
- `ChannelsCommand`: Channel discovery
- `NameCommand`: Entity naming operations
- `RoutingCommand`: Message routing configuration
- Plus aliases: `s`, `t`, `j`, `v`, `p`, `ctx`, `in`, `c`

#### Classification Matrix
For each command, document:
- **Complexity Level**: Simple/Medium/Complex
- **Response Pattern**: JSON structure and special fields
- **Dependencies**: External services, database operations
- **State Management**: Session, context, or persistent state
- **Special Features**: Unique behaviors or requirements

### 2. DSL Gap Analysis (1-2 hours)

#### Current DSL Step Inventory
Review existing step types in `app/Services/Commands/DSL/Steps/`:
- `NotifyStep`: Basic notifications
- `TransformStep`: Data transformation
- `FragmentCreateStep`: Fragment operations
- `AiGenerateStep`: AI integration
- `SearchQueryStep`: Search operations
- `ToolCallStep`: External tools

#### Missing Capabilities Assessment
Identify DSL gaps for hardcoded command features:
- **Session Management**: Session creation, switching, deletion
- **Complex Responses**: Multi-field response objects with special flags
- **State Operations**: Context manipulation and persistence
- **Conditional Logic**: Multi-step workflows with branching
- **Database Transactions**: Complex data operations
- **Integration Points**: External API calls and service integration

### 3. Enhanced DSL Design (1-2 hours)

#### New Step Type Requirements
Design specifications for missing step types:

**SessionStep**: Session management operations
```yaml
- id: create-session
  type: session
  with:
    action: create  # create|switch|delete
    name: "{{ ctx.name }}"
    reset_chat: true
```

**DatabaseStep**: Direct database operations
```yaml
- id: query-fragments
  type: database
  with:
    model: Fragment
    action: query
    filters:
      type: "{{ ctx.fragment_type }}"
```

**ConditionalStep**: Branching logic
```yaml
- id: check-condition
  type: conditional
  with:
    condition: "{{ ctx.user_role == 'admin' }}"
    then:
      - type: notify
        with: { message: "Admin access granted" }
    else:
      - type: notify
        with: { message: "Access denied" }
```

**ResponseStep**: Complex response building
```yaml
- id: build-response
  type: response
  with:
    type: success
    message: "{{ steps.operation.output.message }}"
    data:
      session_id: "{{ steps.create-session.output.id }}"
    flags:
      shouldResetChat: "{{ steps.create-session.output.reset_required }}"
```

### 4. Migration Strategy & Risk Assessment (1-2 hours)

#### Risk Classification
**Low Risk Commands (6):**
- `HelpCommand`: Static content generation
- `ClearCommand`: Simple state reset
- `NameCommand`: Basic entity operations
- `ChannelsCommand`: List generation
- `RoutingCommand`: Configuration display
- Aliases: Simple redirects

**Medium Risk Commands (7):**
- `SessionCommand`: Session management with state
- `SearchCommand`: Complex query building
- `BookmarkCommand`: Database operations
- `JoinCommand`: Session joining logic
- `VaultCommand`: Vault operations
- `ProjectCommand`: Project context
- `ContextCommand`: Context switching

**High Risk Commands (5):**
- `FragCommand`: Complex fragment operations
- `ComposeCommand`: Multi-step composition
- `RecallCommand`: Conflicts with existing YAML
- `TodoCommand`: Conflicts with existing YAML
- `InboxCommand`: Conflicts with existing YAML

#### Migration Priority Matrix
1. **Phase 1**: Low risk commands (quick wins)
2. **Phase 2**: Medium risk commands (bulk migration)
3. **Phase 3**: High risk commands (careful migration)
4. **Phase 4**: Conflict resolution (YAML overlaps)

### 5. Testing Framework Enhancement (1 hour)

#### Enhanced Testing Strategy
- **Functional Testing**: Side-by-side comparison framework
- **Performance Testing**: Execution time comparison
- **Integration Testing**: End-to-end workflow validation
- **Regression Testing**: Comprehensive edge case coverage

#### Test Sample Generation
Create test samples for each command covering:
- Basic usage scenarios
- Edge cases and error conditions
- Complex parameter combinations
- Integration with other commands

## Implementation Approach

### Analysis Methodology
1. **Code Review**: Line-by-line analysis of each command class
2. **Execution Tracing**: Understanding data flow and dependencies
3. **Response Mapping**: Documenting output patterns and formats
4. **Dependency Analysis**: Identifying service and database dependencies

### Documentation Standards
- **Command Analysis Template**: Standardized analysis format
- **Risk Assessment Matrix**: Consistent risk evaluation criteria
- **Migration Planning**: Step-by-step migration roadmap
- **Testing Specifications**: Comprehensive test case definitions

## Expected Outcomes

### Technical Foundation
- Complete understanding of all hardcoded command capabilities
- Enhanced DSL framework ready for complex command patterns
- Risk-assessed migration strategy with clear priorities
- Comprehensive testing framework for validation

### Strategic Benefits
- Reduced migration risk through thorough analysis
- Enhanced DSL capabilities for future command development
- Clear roadmap for systematic migration execution
- Foundation for unified command architecture

## Success Metrics

### Completeness Metrics
- ✅ 18 commands fully analyzed and documented
- ✅ All DSL gaps identified and specified
- ✅ Migration strategy with risk assessment complete
- ✅ Enhanced testing framework implemented

### Quality Metrics
- ✅ Zero functional requirements missed in analysis
- ✅ All edge cases and dependencies documented
- ✅ Migration risks accurately assessed and mitigated
- ✅ Testing framework covers all command scenarios

This foundation phase ensures that subsequent migration phases can proceed with confidence, complete understanding of requirements, and comprehensive risk mitigation strategies.