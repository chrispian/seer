# ENG-10-02: Tool Command Detection System

## Agent Profile
**Type**: Backend Engineering Specialist
**Expertise**: Laravel services, text parsing, command routing, pattern recognition
**Focus**: Service architecture, command detection logic, integration patterns

## Mission
Create an intelligent detection system that identifies tool commands in chat messages and routes them to the appropriate executor (MCP tools or slash commands).

## Current Context
- Existing ParseSlashCommand action handles slash commands
- CommandRunner executes YAML DSL commands
- No current detection for MCP tool commands
- Chat messages flow through ChatApiController

## Skills Required
- Service layer architecture in Laravel
- Regular expression and pattern matching
- Command parsing and tokenization
- Integration with existing systems
- Error handling and validation

## Success Metrics
- Accurately detects MCP tool commands in chat messages
- Correctly routes between tool types (MCP vs slash)
- Integrates seamlessly with existing ParseSlashCommand
- Minimal performance impact (<10ms detection time)
- Clear separation of concerns

## Deliverables

### 1. ToolCommandDetector Service
```php
namespace App\Services\Tools;

class ToolCommandDetector
{
    public function isToolCommand(string $message): bool
    public function detectCommandType(string $message): CommandType
    public function extractToolCall(string $message): ?ToolCall
    public function parseToolParameters(string $message): array
}
```

### 2. Command Type Enumeration
```php
namespace App\Enums;

enum CommandType: string
{
    case SLASH_COMMAND = 'slash';
    case MCP_TOOL = 'mcp';
    case REGULAR_MESSAGE = 'message';
}
```

### 3. ToolCall DTO
```php
namespace App\DTOs;

class ToolCall
{
    public string $tool;
    public array $parameters;
    public string $originalMessage;
    public CommandType $type;
}
```

### 4. Integration Points
- Modify ChatApiController to use detector
- Route to appropriate executor based on type
- Maintain backward compatibility with slash commands

## Technical Approach

### Detection Patterns
1. **MCP Tool Pattern**: `@tool.name param1:"value" param2:value`
2. **Slash Command Pattern**: `/command arguments`
3. **Natural Language Tool**: Detect tool requests in natural language (future)

### Detection Flow
```
Chat Message
    ↓
ToolCommandDetector
    ↓
Determine Type → [MCP Tool | Slash Command | Regular Message]
    ↓              ↓              ↓
MCPToolExecutor  CommandRunner  AI Processing
```

### Implementation Strategy
1. Create base detector service
2. Implement pattern matching for MCP tools
3. Add slash command detection (delegate to existing)
4. Create routing logic
5. Add parameter extraction
6. Implement validation

## Code Examples

### Basic Detection Logic
```php
public function detectCommandType(string $message): CommandType
{
    $message = trim($message);
    
    // Check for slash command
    if (str_starts_with($message, '/')) {
        return CommandType::SLASH_COMMAND;
    }
    
    // Check for MCP tool pattern
    if (preg_match('/^@([\w\.]+)/', $message)) {
        return CommandType::MCP_TOOL;
    }
    
    // Check for tool keywords
    if ($this->containsToolKeywords($message)) {
        return CommandType::MCP_TOOL;
    }
    
    return CommandType::REGULAR_MESSAGE;
}
```

### Parameter Extraction
```php
public function extractToolCall(string $message): ?ToolCall
{
    if (!preg_match('/^@([\w\.]+)\s*(.*)/', $message, $matches)) {
        return null;
    }
    
    return new ToolCall(
        tool: $matches[1],
        parameters: $this->parseParameters($matches[2]),
        originalMessage: $message,
        type: CommandType::MCP_TOOL
    );
}
```

## Testing Plan
1. Unit tests for pattern detection
2. Test parameter extraction accuracy
3. Verify routing logic
4. Performance benchmarks
5. Integration tests with ChatApiController

## Dependencies
- Existing ParseSlashCommand action
- ChatApiController
- Laravel service container

## Time Estimate
3-4 hours total:
- 1 hour: Service creation and basic structure
- 1 hour: Pattern detection implementation
- 45 min: Parameter extraction logic
- 45 min: Integration with chat controller
- 30 min: Testing and refinement