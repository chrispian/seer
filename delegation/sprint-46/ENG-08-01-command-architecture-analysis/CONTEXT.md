# Command Architecture Analysis Context

## Current System Architecture

### Dual Command Systems
The Fragments Engine currently maintains two separate command execution paths:

#### 1. Hardcoded Commands (`app/Services/CommandRegistry.php`)
```php
protected static array $commands = [
    // Core commands (5)
    'session' => SessionCommand::class,
    'help' => HelpCommand::class,
    'clear' => ClearCommand::class,
    'search' => SearchCommand::class,
    'bookmark' => BookmarkCommand::class,

    // Advanced commands (8)
    'frag' => FragCommand::class,
    'vault' => VaultCommand::class,
    'project' => ProjectCommand::class,
    'context' => ContextCommand::class,
    'compose' => ComposeCommand::class,
    'recall' => RecallCommand::class,
    'todo' => TodoCommand::class,
    'inbox' => InboxCommand::class,

    // Utility commands (5)
    'join' => JoinCommand::class,
    'channels' => ChannelsCommand::class,
    'name' => NameCommand::class,
    'routing' => RoutingCommand::class,
    // Plus aliases: 's', 't', 'j', 'v', 'p', 'ctx', 'in', 'c'
];
```

#### 2. File-based Commands (`fragments/commands/*.yaml`)
Currently 15 commands using YAML DSL:
- `settings`, `setup`, `note`, `shell-test`, `accept`
- `inbox`, `types-ui`, `news-digest`, `todo`, `recall`
- `reminder`, `search`, `scheduler`, `link`, `inbox-api`

### Current Command Controller Logic
```php
// CommandController.php - Line 41-107
try {
    // First: Try hardcoded commands
    $commandClass = CommandRegistry::find($commandName);
    $commandInstance = app($commandClass);
    $response = $commandInstance->handle($commandRequest);
} catch (\InvalidArgumentException $e) {
    // Fallback: Try file-based commands
    $dbCommand = CommandRegistryModel::where('slug', $commandName)->first();
    if ($dbCommand) {
        $runner = app(CommandRunner::class);
        $result = $runner->execute($commandName, $arguments);
    }
}
```

## DSL Framework Capabilities

### Available Step Types
From `app/Services/Commands/DSL/Steps/`:
- **NotifyStep**: User notifications and navigation
- **TransformStep**: Data transformation with templates
- **FragmentCreateStep**: Fragment creation and management
- **AiGenerateStep**: AI-powered content generation
- **SearchQueryStep**: Fragment search operations
- **ToolCallStep**: External tool integrations

### Template Engine
- Twig-based templating with context variables
- Access to `ctx` (user context), `env` (environment), `steps` (previous outputs)
- Dynamic content generation and data transformation

### Command Runner Features
- Step-by-step execution with error handling
- Context building and variable passing
- Dry-run support for testing
- Comprehensive logging and performance tracking

## Migration Requirements

### Functional Parity Goals
Each migrated command must maintain:
1. **Identical Response Format**: Same JSON structure and data fields
2. **Preserved Behavior**: All current functionality and edge cases
3. **Alias Support**: Maintain all command shortcuts (s, t, j, etc.)
4. **Error Handling**: Same error messages and failure modes
5. **Performance**: Equivalent or improved execution speed

### Technical Challenges

#### Complex Response Patterns
Some hardcoded commands return complex response objects:
```php
return new CommandResponse(
    type: 'success',
    message: 'Session created',
    fragments: $fragments,
    shouldResetChat: true,
    shouldOpenPanel: false,
    panelData: null,
    data: ['session_id' => $sessionId]
);
```

#### State Management
Commands that interact with:
- Session state and context
- Database transactions
- External APIs and services
- File system operations

#### Advanced Features
- Dynamic fragment creation and updates
- Complex search and filtering logic
- Multi-step workflows with conditional logic
- Integration with AI services and providers

## System Integration Points

### Frontend Integration
- Command autocomplete via `/api/autocomplete/commands`
- Command execution via `/api/commands/execute`
- Response handling in ChatIsland.tsx

### Backend Services
- Fragment storage and retrieval
- Session management
- AI provider integration
- Search and indexing systems

### Database Schema
- `command_registry` table for file-based commands
- Fragment storage and metadata
- Session and user context

## Success Criteria for Analysis Phase

### Deliverable Requirements
1. **Command Audit Matrix**: Complete analysis of all 18 hardcoded commands
2. **DSL Gap Analysis**: Identification of missing step types or capabilities
3. **Migration Complexity Assessment**: Risk and effort estimates per command
4. **Enhanced DSL Specification**: Requirements for new step types
5. **Testing Strategy**: Framework for validating migration success

### Risk Assessment Framework
- **Low Risk**: Simple commands with basic response patterns
- **Medium Risk**: Commands with moderate complexity or state dependencies
- **High Risk**: Commands with complex workflows or external integrations

## Available Resources

### Documentation
- `CLAUDE.md`: Repository guidelines and development patterns
- `CONTEXT.md`: Project architecture and system overview
- Existing YAML command examples in `fragments/commands/`

### Codebase References
- `app/Actions/Commands/`: All hardcoded command implementations
- `app/Services/Commands/DSL/`: DSL framework and step implementations
- `app/Console/Commands/Commands/`: Command management utilities

### Testing Tools
- `php artisan frag:command:test`: Built-in command testing framework
- `php artisan frag:command:cache`: Command registry management
- Existing test samples in command directories