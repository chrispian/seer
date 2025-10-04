# ENG-08-02: Core Command Migration (Batch 1)

## Objective
Migrate 5 foundational commands (session, help, clear, search, bookmark) from hardcoded Laravel classes to file-based YAML DSL configurations while maintaining complete functional equivalence.

## Scope & Deliverables

### 1. SessionCommand Migration (2-3 hours)
**Target**: `app/Actions/Commands/SessionCommand.php` → `fragments/commands/session/command.yaml`

#### Analysis & Design
- Document current session management logic and state handling
- Design YAML workflow for session creation, switching, and deletion
- Plan integration with chat reset functionality and response flags

#### Implementation
- Create `session/command.yaml` with comprehensive session operations
- Implement enhanced SessionStep for session management
- Configure response building with session data and reset flags
- Add support for session aliases and shortcuts

#### Testing
- Create test samples for all session operations
- Validate session state management and persistence
- Test integration with chat reset functionality
- Verify response format compatibility

### 2. HelpCommand Migration (1-2 hours)
**Target**: `app/Actions/Commands/HelpCommand.php` → `fragments/commands/help/command.yaml`

#### Analysis & Design
- Document current help generation and command discovery logic
- Design YAML workflow for dynamic help content generation
- Plan integration with file-based command registry

#### Implementation
- Create `help/command.yaml` with command discovery workflow
- Configure template-based help content generation
- Implement command registry integration for dynamic listings
- Add formatted output generation

#### Testing
- Create test samples for help generation scenarios
- Validate command discovery and listing accuracy
- Test formatting and presentation consistency
- Verify integration with autocomplete system

### 3. ClearCommand Migration (1 hour)
**Target**: `app/Actions/Commands/ClearCommand.php` → `fragments/commands/clear/command.yaml`

#### Analysis & Design
- Document current chat clearing and state reset logic
- Design simple YAML workflow for state operations
- Plan response patterns for clear operations

#### Implementation
- Create `clear/command.yaml` with state reset workflow
- Configure appropriate response flags and messaging
- Implement error handling for clear operations

#### Testing
- Create test samples for clear scenarios
- Validate state reset functionality
- Test response flag compatibility with frontend
- Verify error handling behavior

### 4. SearchCommand Migration (2-3 hours)
**Target**: `app/Actions/Commands/SearchCommand.php` → `fragments/commands/search/command.yaml`

#### Analysis & Design
- Document current search query building and execution logic
- Design YAML workflow for complex search operations
- Plan integration with existing search services and indexing

#### Implementation
- Create `search/command.yaml` with enhanced search workflow
- Implement or enhance SearchStep for complex queries
- Configure result formatting and pagination
- Add support for search aliases ("/s")

#### Testing
- Create comprehensive test samples for search scenarios
- Validate query building and execution accuracy
- Test pagination and result formatting
- Verify integration with search services

### 5. BookmarkCommand Migration (2-3 hours)
**Target**: `app/Actions/Commands/BookmarkCommand.php` → `fragments/commands/bookmark/command.yaml`

#### Analysis & Design
- Document current bookmark operations and database interactions
- Design YAML workflow for bookmark management
- Plan database transaction handling in DSL

#### Implementation
- Create `bookmark/command.yaml` with bookmark operations
- Implement database operations via enhanced steps
- Configure bookmark relationship management
- Add error handling for database operations

#### Testing
- Create test samples for bookmark operations
- Validate database transaction handling
- Test bookmark relationship management
- Verify error handling and rollback behavior

## Implementation Strategy

### Migration Methodology
1. **Command Analysis**: Thorough review of current implementation
2. **YAML Design**: Create equivalent YAML configuration
3. **Step Enhancement**: Implement required DSL enhancements
4. **Testing**: Comprehensive validation against original
5. **Integration**: Ensure compatibility with existing systems

### Quality Assurance Process
- **Side-by-Side Testing**: Compare original vs migrated commands
- **Performance Benchmarking**: Ensure equivalent or improved performance
- **Integration Validation**: Test with frontend and API consumers
- **Edge Case Coverage**: Comprehensive error and boundary testing

### Dependencies
- Enhanced DSL step types from ENG-08-01
- Command testing framework
- Performance comparison tools
- Integration test suites

## Technical Requirements

### New DSL Step Requirements
Based on command analysis, implement:
- **SessionStep**: Session management operations
- **Enhanced SearchStep**: Complex query building
- **ResponseStep**: Complex response construction
- **DatabaseStep**: Direct database operations for bookmarks

### Alias Support
Implement YAML-based aliases:
```yaml
triggers:
  slash: "/search"
  aliases: ["/s"]
```

### Response Compatibility
Ensure migrated commands return identical response structures:
- Maintain all current response fields
- Preserve special flags (shouldResetChat, etc.)
- Support complex data structures
- Handle error responses consistently

## Testing Strategy

### Functional Testing
- **Input/Output Validation**: Identical responses for identical inputs
- **State Management**: Proper handling of session and context state
- **Error Handling**: Consistent error responses and recovery
- **Integration**: Seamless operation with existing systems

### Performance Testing
- **Execution Time**: Comparable or improved performance
- **Memory Usage**: Efficient resource utilization
- **Database Load**: Optimized query patterns
- **Scalability**: Performance under load

### Edge Case Testing
- **Boundary Conditions**: Empty inputs, large datasets, edge cases
- **Error Scenarios**: Network failures, database errors, invalid inputs
- **Concurrent Operations**: Multiple simultaneous command executions
- **State Conflicts**: Overlapping operations and state management

## Success Metrics

### Functional Requirements
- ✅ All 5 commands migrated with identical functionality
- ✅ Response format compatibility maintained
- ✅ State management behavior preserved
- ✅ Error handling consistency achieved

### Performance Requirements
- ✅ Command execution time within 10% of original
- ✅ Memory usage optimized or equivalent
- ✅ Database query efficiency maintained
- ✅ Frontend integration seamless

### Quality Requirements
- ✅ Comprehensive test coverage for all scenarios
- ✅ Documentation complete for all migrated commands
- ✅ Code quality meets repository standards
- ✅ Integration tests pass completely

## Risk Mitigation

### Technical Risks
- **DSL Limitations**: Implement required step enhancements before migration
- **State Management**: Careful testing of session and context handling
- **Performance Impact**: Continuous benchmarking throughout migration

### Integration Risks
- **Frontend Compatibility**: Thorough testing with ChatIsland and modals
- **API Consistency**: Validation of response format compatibility
- **Command Discovery**: Testing with autocomplete and help systems

## Deliverables

### Implementation Artifacts
- 5 complete YAML command configurations
- Enhanced DSL step implementations
- Comprehensive test sample library
- Migration validation reports

### Documentation
- Command migration documentation
- DSL enhancement specifications
- Testing framework documentation
- Integration validation results

This batch establishes the migration patterns and validates the enhanced DSL framework with foundational commands, providing confidence for the more complex migrations in subsequent batches.