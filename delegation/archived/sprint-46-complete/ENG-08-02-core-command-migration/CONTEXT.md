# Core Command Migration Context

## Migration Target Commands

### Batch 1: Core Commands (5 commands)
These commands represent the foundational functionality of the system and have relatively straightforward migration paths:

#### 1. SessionCommand
**Current Implementation**: `app/Actions/Commands/SessionCommand.php`
**Functionality**: Session creation, switching, and management
**Migration Complexity**: Medium
**Key Features**:
- Session creation with custom names
- Session switching and context management
- Integration with chat reset functionality
- Response includes session data and reset flags

#### 2. HelpCommand  
**Current Implementation**: `app/Actions/Commands/HelpCommand.php`
**Functionality**: Command discovery and documentation
**Migration Complexity**: Low
**Key Features**:
- Static command listing and descriptions
- Integration with command registry
- Formatted help output generation
- Simple response patterns

#### 3. ClearCommand
**Current Implementation**: `app/Actions/Commands/ClearCommand.php`
**Functionality**: Chat clearing and state reset
**Migration Complexity**: Low
**Key Features**:
- Chat message clearing
- State reset operations
- Simple success/failure responses
- Integration with frontend reset flags

#### 4. SearchCommand
**Current Implementation**: `app/Actions/Commands/SearchCommand.php`
**Functionality**: Fragment search and filtering
**Migration Complexity**: Medium
**Key Features**:
- Complex query building and execution
- Fragment filtering and pagination
- Integration with search services
- Structured result formatting

#### 5. BookmarkCommand
**Current Implementation**: `app/Actions/Commands/BookmarkCommand.php`
**Functionality**: Fragment bookmarking operations
**Migration Complexity**: Medium
**Key Features**:
- Bookmark creation and management
- Fragment relationship handling
- Database transaction management
- Status response formatting

## Current Command Response Patterns

### Standard Response Structure
Most commands return a `CommandResponse` object with:
```php
return new CommandResponse(
    type: 'success|error|info',
    message: 'Human readable message',
    fragments: [], // Optional fragment data
    shouldResetChat: false, // Optional chat reset flag
    shouldOpenPanel: false, // Optional panel control
    panelData: null, // Optional panel data
    data: [] // Optional additional data
);
```

### Session-Specific Patterns
SessionCommand includes special response fields:
```php
return new CommandResponse(
    type: 'success',
    message: 'Session created successfully',
    shouldResetChat: true,
    data: [
        'session_id' => $session->id,
        'session_name' => $session->name
    ]
);
```

### Search-Specific Patterns
SearchCommand returns structured search results:
```php
return new CommandResponse(
    type: 'success',
    message: 'Found X fragments',
    fragments: $searchResults,
    data: [
        'total_count' => $totalCount,
        'query' => $searchQuery,
        'filters_applied' => $filters
    ]
);
```

## DSL Framework Integration

### Available Step Types
Building on the enhanced DSL from ENG-08-01:

#### Enhanced NotifyStep
```yaml
- id: notify-user
  type: notify
  with:
    message: "{{ ctx.message }}"
    level: "success"
    panel_data:
      action: "navigate"
      url: "/sessions"
```

#### SessionStep (New - from ENG-08-01)
```yaml
- id: create-session
  type: session
  with:
    action: create
    name: "{{ ctx.name | default: 'New Session' }}"
    reset_chat: true
```

#### SearchStep (Enhanced)
```yaml
- id: search-fragments
  type: search
  with:
    query: "{{ ctx.query }}"
    filters:
      type: "{{ ctx.type | default: null }}"
    limit: "{{ ctx.limit | default: 20 }}"
```

#### ResponseStep (New - from ENG-08-01)
```yaml
- id: build-response
  type: response
  with:
    type: "{{ steps.operation.output.success ? 'success' : 'error' }}"
    message: "{{ steps.operation.output.message }}"
    data:
      session_id: "{{ steps.create-session.output.id }}"
    flags:
      shouldResetChat: "{{ steps.create-session.output.reset_required }}"
```

## Migration Strategy

### Phase Approach
1. **Analysis**: Document current command behavior and requirements
2. **YAML Design**: Create equivalent YAML configurations
3. **Implementation**: Implement any required new DSL steps
4. **Testing**: Comprehensive functional and integration testing
5. **Validation**: Side-by-side comparison with original commands

### Testing Framework
Utilizing `php artisan frag:command:test` with comprehensive samples:
```json
{
  "ctx": {
    "body": "test query",
    "user": {"id": 1, "name": "Test User"},
    "session": {"id": "test-session"},
    "workspace": {"id": 1}
  }
}
```

### Alias Handling
Implementing command aliases in YAML:
```yaml
triggers:
  slash: "/search"
  aliases: ["/s"]
  input_mode: "inline"
```

## Integration Points

### Frontend Compatibility
Commands must maintain compatibility with:
- ChatIsland.tsx command execution
- CommandResultModal display
- Autocomplete integration
- Response handling patterns

### Database Integration
Commands interact with:
- Session management system
- Fragment storage and indexing
- Bookmark relationships
- Search indexing services

### Service Dependencies
Commands may require:
- AI provider services
- Search and indexing services
- File system operations
- External API integrations

## Quality Assurance Requirements

### Functional Equivalence
Migrated commands must:
- Return identical response structures
- Maintain all current functionality
- Preserve error handling behavior
- Support all parameter combinations

### Performance Requirements
Migrated commands should:
- Maintain or improve execution speed
- Optimize database query patterns
- Reduce memory footprint where possible
- Scale equivalently with data size

### Integration Requirements
Migrated commands must:
- Work seamlessly with existing frontend
- Maintain API compatibility
- Support existing alias patterns
- Integrate with command autocomplete

## Available Resources

### Command Analysis (from ENG-08-01)
- Complete analysis matrix of all commands
- DSL gap analysis and requirements
- Migration risk assessment
- Enhanced DSL specifications

### Testing Infrastructure
- Enhanced command testing framework
- Comprehensive test sample library
- Performance comparison tools
- Integration testing suites

### Documentation References
- Existing YAML command examples
- DSL step implementation guides
- Command controller integration patterns
- Frontend response handling documentation