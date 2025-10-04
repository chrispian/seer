# MCP Servers Overview - Fragments Engine

## Available MCP Servers

Fragments Engine provides several MCP servers for programmatic access to system functionality. Each server operates independently and provides specific domain functionality.

### Current Servers

| Server | Purpose | Command | Key Methods |
|--------|---------|---------|-------------|
| **delegation-system** | Sprint management and agent coordination | `delegation:mcp` | `sprint/status`, `agent/create`, `worktree/setup` |
| **fragments-tools** | Fragment management and analysis | `fragments-tools:mcp` | `fragment/search`, `fragment/analyze`, `fragment/export` |
| **ai-providers** | AI provider testing and management | `ai-providers:mcp` | `provider/test`, `provider/usage`, `provider/models` |
| **system-tools** | General system management | `system-tools:mcp` | `cache/clear`, `logs/tail`, `queue/status` |
| **laravel-boost** | Laravel Boost integration | `boost:mcp` | (Boost-specific methods) |

## Quick Start

### Using MCP Servers in Claude Code

```bash
# Check delegation system status
/delegation-system sprint/status

# Search fragments
/fragments-tools fragment/search query="authentication"

# Test AI provider
/ai-providers provider/test provider="openai"

# Clear system cache
/system-tools cache/clear
```

### Creating New MCP Servers

Use the scaffolding command to create new servers:

```bash
# Basic server with default methods
php artisan make:mcp-server my-tools --domain="My custom tools"

# Server with specific methods
php artisan make:mcp-server database-tools \
  --domain="Database management utilities" \
  --methods="migration/run,backup/create,schema/analyze"

# Skip auto-registration in .mcp.json
php artisan make:mcp-server temp-server --no-register
```

## Architecture

### Namespace Strategy

- **User Commands**: Single word (`/frag`, `/search`, `/todo`)
- **MCP Servers**: Hyphenated names (`/server-name method/action`)
- **No Conflicts**: User commands and MCP servers operate in separate namespaces

### File Structure

```
app/Console/Commands/
├── DelegationMcp.php           # delegation-system server
├── FragmentsToolsMcp.php       # fragments-tools server
├── AiProvidersMcp.php          # ai-providers server
├── SystemToolsMcp.php          # system-tools server
└── MakeMcpServer.php           # Scaffolding command

docs/
├── MCP_DEVELOPMENT_GUIDE.md    # Comprehensive development guide
├── MCP_SERVERS_OVERVIEW.md     # This file
└── mcp-servers/
    ├── delegation-system.md    # Individual server docs
    ├── fragments-tools.md
    ├── ai-providers.md
    └── system-tools.md

.mcp.json                       # MCP server registry
```

## Server Implementation Patterns

### Basic Server Structure

All MCP servers follow this pattern:

1. **Laravel Console Command** extending `Illuminate\Console\Command`
2. **JSON-RPC Protocol** over stdin/stdout
3. **Method Routing** using PHP 8 `match` expressions
4. **Structured Responses** with consistent error handling

### Example Method Implementation

```php
private function listItems(array $params): array
{
    try {
        $items = SomeModel::all()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'status' => $item->status
            ];
        });
        
        return [
            'result' => [
                'operation' => 'listItems',
                'items' => $items->toArray(),
                'count' => $items->count(),
                'timestamp' => now()->toISOString()
            ]
        ];
    } catch (\Exception $e) {
        return ['error' => 'Failed to list items: ' . $e->getMessage()];
    }
}
```

## Testing MCP Servers

### Local Testing

```bash
# Test server directly
php artisan server-name:mcp

# Send test JSON request
echo '{"method":"method/name","params":{"key":"value"}}' | php artisan server-name:mcp
```

### Integration Testing

```bash
# Test through Claude Code (requires MCP client)
/server-name method/name key=value

# Test specific functionality
/fragments-tools fragment/search query="test"
/delegation-system sprint/status
/ai-providers provider/test provider="anthropic"
```

## Common Use Cases

### Development Workflow
- **Sprint Management**: `/delegation-system` for coordinating development work
- **Testing**: Custom test-runner server for automated testing
- **System Monitoring**: `/system-tools` for health checks and maintenance

### Content Management
- **Fragment Operations**: `/fragments-tools` for advanced fragment manipulation
- **Data Analysis**: Custom analytics servers for usage insights
- **Export/Import**: Bulk operations and data migration

### AI/ML Operations
- **Provider Management**: `/ai-providers` for testing and switching AI services
- **Model Testing**: Custom servers for specific AI workflows
- **Performance Monitoring**: AI usage and cost tracking

## Best Practices

### 1. Server Design
- **Single Responsibility**: Each server should have a focused domain
- **Consistent Naming**: Use hyphenated names for servers, slash-separated for methods
- **Error Handling**: Always return structured error responses

### 2. Method Implementation
- **Parameter Validation**: Validate all inputs before processing
- **Structured Responses**: Use consistent response format with operation metadata
- **Exception Handling**: Catch and convert exceptions to error responses

### 3. Documentation
- **Method Documentation**: Document all methods in server class comments
- **Usage Examples**: Provide clear examples in individual server docs
- **Testing Instructions**: Include test commands for each server

## Integration with Fragments Engine

### Laravel Features
- **Models & Eloquent**: Direct database access
- **Jobs & Queues**: Async processing capabilities
- **Events**: System notifications
- **Cache**: Performance optimization
- **File Storage**: File operations

### Fragments-Specific
- **Fragment Models**: Search and manipulation
- **AI Services**: Provider management
- **Command System**: Integration with user commands
- **Background Jobs**: Queue processing

## Security Considerations

### Input Validation
```php
// Validate file paths
$filename = basename($params['filename'] ?? '');
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
    return ['error' => 'Invalid filename'];
}
```

### Command Injection Prevention
```php
// Never pass user input directly to shell
$allowedCommands = ['migrate', 'seed', 'cache:clear'];
if (!in_array($command, $allowedCommands)) {
    return ['error' => 'Command not allowed'];
}
```

### Rate Limiting
```php
// Implement rate limiting for expensive operations
$key = 'mcp_operation_' . $method . '_' . request()->ip();
if (Cache::get($key, 0) > 10) {
    return ['error' => 'Rate limit exceeded'];
}
Cache::increment($key, 1);
Cache::expire($key, 60);
```

## Troubleshooting

### Common Issues

1. **Server Not Found**: Check `.mcp.json` registration
2. **Method Not Working**: Verify method name in `match` expression
3. **JSON Errors**: Ensure response is valid JSON
4. **Permission Issues**: Check file system permissions for file operations

### Debugging Tips

```php
// Add logging to methods
\Log::info('MCP: Method called', ['method' => $method, 'params' => $params]);

// Validate JSON encoding
$response = ['result' => $data];
$json = json_encode($response);
if (json_last_error() !== JSON_ERROR_NONE) {
    return ['error' => 'JSON encoding failed: ' . json_last_error_msg()];
}
```

## Future Enhancements

### Planned Servers
- **test-runner**: Automated testing workflows
- **backup-tools**: System backup and restore
- **analytics**: Usage and performance analytics
- **deployment**: Deployment automation

### Potential Features
- **Authentication**: User-based access control
- **Rate Limiting**: Built-in rate limiting
- **Caching**: Response caching for expensive operations
- **Webhooks**: External system integration

This MCP server architecture provides a powerful foundation for extending Fragments Engine functionality while maintaining clear separation from user-facing commands.