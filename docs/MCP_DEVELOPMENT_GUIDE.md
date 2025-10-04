# MCP Development Guide for Fragments Engine

## Overview

This guide explains how to create and manage Model Context Protocol (MCP) servers for Fragments Engine. MCP servers provide programmatic access to system functionality through Claude Code and other MCP clients.

## Architecture

### System Separation

Fragments Engine has **two distinct command systems**:

1. **User-Facing Commands** (`/frag`, `/search`, `/todo`)
   - Defined in `fragments/commands/*/command.yaml`
   - Handled by `app/Actions/Commands/*Command.php`
   - Used directly by end-users in chat interface
   - Trigger with single slash: `/command`

2. **Developer MCP Servers** (`delegation-system`, `fragments-tools`)
   - Defined as Laravel console commands in `app/Console/Commands/*Mcp.php`
   - Registered in `.mcp.json`
   - Used by AI agents and developers through Claude Code
   - Trigger with server namespace: `/server-name method/action`

### Namespace Strategy

To avoid conflicts between user commands and MCP servers:

- **User Commands**: Single word slugs (`frag`, `search`, `todo`)
- **MCP Servers**: Hyphenated names with domain context (`delegation-system`, `fragments-tools`, `ai-providers`)
- **MCP Methods**: Slash-separated paths (`sprint/status`, `fragment/search`, `provider/test`)

## Current MCP Servers

### delegation-system
- **Purpose**: Sprint management and agent coordination
- **Commands**: `sprint/status`, `sprint/analyze`, `agent/create`, `agent/assign`, `task/analyze`, `worktree/setup`
- **File**: `app/Console/Commands/DelegationMcp.php`

## Creating New MCP Servers

### 1. Generate Scaffolding

```bash
php artisan make:mcp-server server-name --domain="Server Description"
```

This creates:
- `app/Console/Commands/{ServerName}Mcp.php`
- Registers server in `.mcp.json`
- Provides base command structure

### 2. Manual Setup

If not using scaffolding:

#### Step 1: Create Console Command

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class YourServerMcp extends Command
{
    protected $signature = 'your-server:mcp';
    protected $description = 'MCP server for your functionality';

    public function handle()
    {
        $this->info('Starting Your Server MCP...');
        
        while (true) {
            $input = trim(fgets(STDIN));
            
            if (empty($input)) {
                continue;
            }
            
            try {
                $request = json_decode($input, true);
                $response = $this->handleRequest($request);
                echo json_encode($response) . "\n";
            } catch (\Exception $e) {
                $error = [
                    'error' => [
                        'code' => -1,
                        'message' => $e->getMessage()
                    ]
                ];
                echo json_encode($error) . "\n";
            }
        }
    }
    
    private function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        
        return match ($method) {
            'resource/list' => $this->listResources(),
            'resource/get' => $this->getResource($params['id'] ?? null),
            'resource/create' => $this->createResource($params),
            default => ['error' => 'Unknown method: ' . $method]
        };
    }
    
    private function listResources(): array
    {
        // Implementation here
        return ['result' => []];
    }
    
    // Add more methods...
}
```

#### Step 2: Register in .mcp.json

```json
{
    "mcpServers": {
        "your-server": {
            "command": "php",
            "args": ["./artisan", "your-server:mcp"]
        }
    }
}
```

### 3. Method Implementation Patterns

#### Simple Data Retrieval
```php
private function getStatus(): array
{
    $data = collect(SomeModel::all())->map(function ($item) {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'status' => $item->status
        ];
    });
    
    return ['result' => $data->toArray()];
}
```

#### File System Operations
```php
private function readConfig(?string $filename): array
{
    if (!$filename) {
        return ['error' => 'Filename required'];
    }
    
    $path = config_path($filename);
    
    if (!File::exists($path)) {
        return ['error' => "Config file '{$filename}' not found"];
    }
    
    $content = File::get($path);
    
    return [
        'result' => [
            'filename' => $filename,
            'content' => $content,
            'size' => strlen($content),
            'last_modified' => date('Y-m-d H:i:s', filemtime($path))
        ]
    ];
}
```

#### External Process Execution
```php
private function runTests(?string $suite): array
{
    if (!$suite) {
        return ['error' => 'Test suite required'];
    }
    
    $command = "vendor/bin/pest --testsuite={$suite}";
    exec($command . ' 2>&1', $output, $exitCode);
    
    return [
        'result' => [
            'suite' => $suite,
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode
        ]
    ];
}
```

## Recommended MCP Server Types

### fragments-tools
**Purpose**: Fragment management and analysis
**Suggested Methods**:
- `fragment/search` - Advanced fragment search
- `fragment/analyze` - Content analysis
- `fragment/export` - Bulk export operations
- `fragment/stats` - Usage statistics

### ai-providers
**Purpose**: AI provider testing and management
**Suggested Methods**:
- `provider/test` - Test provider connectivity
- `provider/usage` - Get usage statistics
- `provider/models` - List available models
- `provider/switch` - Change active provider

### system-tools
**Purpose**: General system management
**Suggested Methods**:
- `cache/clear` - Clear application caches
- `logs/tail` - Follow log files
- `queue/status` - Check queue health
- `config/get` - Get configuration values

### test-runner
**Purpose**: Testing automation
**Suggested Methods**:
- `test/run` - Run specific test suites
- `test/coverage` - Generate coverage reports
- `test/watch` - Watch mode for TDD
- `migration/test` - Test pending migrations

## Best Practices

### 1. Error Handling
Always return structured error responses:
```php
if (!$requiredParam) {
    return ['error' => 'Parameter required'];
}

try {
    // Operation here
} catch (\Exception $e) {
    return ['error' => $e->getMessage()];
}
```

### 2. Parameter Validation
Validate and provide defaults:
```php
private function methodName(?string $param1, ?int $param2 = 10): array
{
    if (!$param1) {
        return ['error' => 'param1 is required'];
    }
    
    $param2 = max(1, min(100, $param2)); // Bounds checking
    
    // Continue with validated parameters
}
```

### 3. Response Structure
Use consistent response format:
```php
// Success
return [
    'result' => [
        'operation' => 'method_name',
        'data' => $actualData,
        'meta' => [
            'timestamp' => now()->toISOString(),
            'count' => count($actualData)
        ]
    ]
];

// Error
return [
    'error' => 'Descriptive error message'
];
```

### 4. Documentation
Include method documentation in each server:
```php
/**
 * Available Methods:
 * 
 * resource/list - List all resources
 *   Parameters: none
 *   Returns: array of resource objects
 * 
 * resource/get - Get specific resource
 *   Parameters: id (required)
 *   Returns: single resource object
 * 
 * resource/create - Create new resource
 *   Parameters: name (required), config (optional)
 *   Returns: created resource object
 */
```

## Usage Examples

### Basic Usage
```bash
# List available servers
ls .mcp.json

# Test delegation system
/delegation-system sprint/status

# Create new agent
/delegation-system agent/create role=backend-engineer name=alice
```

### Complex Operations
```bash
# Fragment analysis pipeline
/fragments-tools fragment/search query="authentication"
/fragments-tools fragment/analyze id=123
/fragments-tools fragment/export format=json

# Testing workflow
/test-runner test/run suite=Feature
/test-runner test/coverage format=html
```

## Debugging MCP Servers

### 1. Test Locally
```bash
# Test server directly
php artisan your-server:mcp

# Send test JSON
echo '{"method":"resource/list","params":{}}' | php artisan your-server:mcp
```

### 2. Enable Logging
Add logging to your methods:
```php
private function someMethod(): array
{
    \Log::info('MCP: someMethod called', ['params' => func_get_args()]);
    
    $result = // ... your logic
    
    \Log::info('MCP: someMethod result', ['result' => $result]);
    
    return $result;
}
```

### 3. Validate JSON
Ensure responses are valid JSON:
```php
$response = ['result' => $data];
$json = json_encode($response);

if (json_last_error() !== JSON_ERROR_NONE) {
    return ['error' => 'JSON encoding failed: ' . json_last_error_msg()];
}

echo $json . "\n";
```

## Security Considerations

### 1. Input Sanitization
Always sanitize file paths and system commands:
```php
private function readFile(?string $filename): array
{
    if (!$filename) {
        return ['error' => 'Filename required'];
    }
    
    // Prevent directory traversal
    $filename = basename($filename);
    
    // Whitelist allowed extensions
    $allowedExtensions = ['json', 'yaml', 'md'];
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['error' => 'File type not allowed'];
    }
    
    // Continue with safe filename
}
```

### 2. Command Injection Prevention
Never pass user input directly to shell commands:
```php
// BAD
exec("git branch {$userInput}");

// GOOD
$allowedBranches = ['main', 'develop', 'staging'];
if (!in_array($userInput, $allowedBranches)) {
    return ['error' => 'Invalid branch name'];
}
exec("git branch " . escapeshellarg($userInput));
```

### 3. Rate Limiting
Consider adding rate limiting for expensive operations:
```php
private function expensiveOperation(): array
{
    $key = 'mcp_expensive_op_' . request()->ip();
    
    if (Cache::get($key, 0) > 5) {
        return ['error' => 'Rate limit exceeded'];
    }
    
    Cache::increment($key, 1);
    Cache::expire($key, 60); // 1 minute window
    
    // Continue with operation
}
```

## Integration with Existing Systems

### Laravel Features
MCP servers can leverage full Laravel functionality:
- **Models & Eloquent**: Direct database access
- **Jobs & Queues**: Async processing
- **Events**: System notifications
- **Cache**: Performance optimization
- **Validation**: Input validation
- **File Storage**: File operations

### Fragments Engine
Access Fragments-specific functionality:
- **Fragment Models**: Search and manipulation
- **AI Services**: Provider management
- **Command System**: Integration with user commands
- **Job Processing**: Background operations

This architecture ensures MCP servers remain separate from user-facing commands while providing powerful programmatic access to all system functionality.