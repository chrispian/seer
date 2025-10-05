# ENG-10-03: MCP Tool Executor Service

## Agent Profile
**Type**: Senior Backend Engineer
**Expertise**: Laravel services, MCP protocol, async execution, error handling
**Focus**: Service implementation, protocol integration, performance optimization

## Mission
Implement the core service for executing MCP tools, including tool discovery, parameter validation, execution, and result capture.

## Current Context
- Laravel MCP 0.2.0 provides base MCP functionality
- Tool-crate server will be registered (ENG-10-01)
- Detection system will identify tool commands (ENG-10-02)
- Need execution layer to run tools and capture output

## Skills Required
- Laravel service architecture
- MCP protocol understanding
- Async/streaming execution patterns
- Error handling and recovery
- Result transformation and formatting

## Success Metrics
- Successfully executes all tool-crate tools
- Proper parameter validation before execution
- Comprehensive error handling with recovery
- Tool discovery and schema retrieval working
- Execution time <100ms for simple tools
- Supports both sync and async execution

## Deliverables

### 1. MCPToolExecutor Service
```php
namespace App\Services\Tools;

class MCPToolExecutor
{
    public function execute(string $tool, array $params, array $context = []): ToolResult
    public function executeAsync(string $tool, array $params, callable $onProgress = null): ToolResult
    public function listTools(): array
    public function getToolSchema(string $tool): array
    public function validateParameters(string $tool, array $params): ValidationResult
}
```

### 2. ToolResult DTO
```php
namespace App\DTOs;

class ToolResult
{
    public bool $success;
    public mixed $output;
    public ?string $error;
    public array $metadata;
    public float $executionTime;
    public string $tool;
    public array $parameters;
}
```

### 3. Tool Discovery Service
```php
namespace App\Services\Tools;

class ToolDiscoveryService
{
    public function discoverTools(string $server): array
    public function getToolCapabilities(string $tool): array
    public function searchTools(string $query): array
}
```

### 4. Error Handling
- Custom exceptions for tool failures
- Retry logic for transient failures
- Graceful degradation
- User-friendly error messages

## Technical Approach

### Execution Flow
```
Tool Request → Validate Parameters → Execute Tool → Capture Output → Transform Result
                    ↓ (fail)              ↓ (error)
                Return Error          Handle Error → Retry/Fail
```

### Core Implementation
```php
public function execute(string $tool, array $params, array $context = []): ToolResult
{
    $startTime = microtime(true);
    
    try {
        // Validate parameters
        $validation = $this->validateParameters($tool, $params);
        if (!$validation->passes()) {
            throw new InvalidParametersException($validation->errors());
        }
        
        // Get MCP server
        $server = $this->mcpRegistry->getServer('tool-crate');
        
        // Execute tool
        $response = $server->callTool($tool, $params);
        
        // Process response
        return new ToolResult(
            success: true,
            output: $response->content,
            error: null,
            metadata: [
                'server' => 'tool-crate',
                'context' => $context,
            ],
            executionTime: microtime(true) - $startTime,
            tool: $tool,
            parameters: $params
        );
        
    } catch (\Exception $e) {
        return $this->handleExecutionError($e, $tool, $params, $startTime);
    }
}
```

### Async Execution
```php
public function executeAsync(string $tool, array $params, callable $onProgress = null): ToolResult
{
    return dispatch(new ExecuteToolJob($tool, $params))
        ->onProgress($onProgress)
        ->onQueue('tools');
}
```

### Tool Discovery
```php
public function listTools(): array
{
    $server = $this->mcpRegistry->getServer('tool-crate');
    $tools = $server->listTools();
    
    return array_map(function($tool) {
        return [
            'name' => $tool->name,
            'description' => $tool->description,
            'parameters' => $tool->inputSchema,
            'category' => $this->categorize($tool),
        ];
    }, $tools);
}
```

## Integration Points

### With Detection System
```php
// In ChatApiController
$detector = app(ToolCommandDetector::class);
$toolCall = $detector->extractToolCall($message);

if ($toolCall && $toolCall->type === CommandType::MCP_TOOL) {
    $executor = app(MCPToolExecutor::class);
    $result = $executor->execute($toolCall->tool, $toolCall->parameters);
}
```

### With Streaming
```php
$executor->executeAsync($tool, $params, function($progress) use ($stream) {
    $stream->send([
        'type' => 'tool_progress',
        'data' => $progress
    ]);
});
```

## Error Handling Strategy

### Error Types
1. **Validation Errors**: Invalid parameters
2. **Connection Errors**: MCP server unavailable
3. **Execution Errors**: Tool execution failed
4. **Timeout Errors**: Tool took too long
5. **Permission Errors**: Insufficient access

### Recovery Strategies
- Automatic retry for transient failures
- Fallback to alternative tools
- Clear error messages for user
- Logging for debugging

## Testing Plan
1. Unit tests for executor logic
2. Integration tests with MCP server
3. Test all tool-crate tools
4. Error scenario testing
5. Performance benchmarks
6. Async execution tests

## Dependencies
- Laravel MCP package
- Tool-crate server (ENG-10-01)
- Detection system (ENG-10-02)
- Laravel queue system (for async)

## Time Estimate
4-5 hours total:
- 1.5 hours: Core executor implementation
- 1 hour: Tool discovery and validation
- 1 hour: Error handling and recovery
- 1 hour: Async execution support
- 30 min: Testing and refinement