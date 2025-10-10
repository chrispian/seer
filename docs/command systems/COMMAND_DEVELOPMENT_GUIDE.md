# Command Development Guide

**Last Updated**: 2025-10-09  
**Architecture**: Unified Command System  
**Status**: Production Ready

---

## Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Creating a New Command](#creating-a-new-command)
4. [Command Structure](#command-structure)
5. [Context-Aware Responses](#context-aware-responses)
6. [Registering Commands](#registering-commands)
7. [Testing Commands](#testing-commands)
8. [Best Practices](#best-practices)
9. [Examples](#examples)

---

## Overview

The Fragments Engine uses a **unified command architecture** where a single command class handles execution across multiple interfaces:

- 🌐 **Web UI** - Commands triggered via slash commands in chat
- 🔌 **MCP** - Model Context Protocol for AI agents
- 💻 **CLI** - Laravel Artisan console commands

**Key Principle**: Write the command logic **once**, use it **everywhere**.

---

## Architecture

### Directory Structure

```
app/Commands/
├── BaseCommand.php                      # Base class with context detection
├── Orchestration/                       # Orchestration commands
│   ├── Sprint/
│   │   ├── ListCommand.php
│   │   ├── DetailCommand.php
│   │   ├── SaveCommand.php
│   │   ├── UpdateStatusCommand.php
│   │   └── AttachTasksCommand.php
│   ├── Task/
│   │   ├── ListCommand.php
│   │   ├── DetailCommand.php
│   │   ├── SaveCommand.php
│   │   ├── AssignCommand.php
│   │   └── UpdateStatusCommand.php
│   ├── Agent/
│   │   └── ListCommand.php
│   └── Backlog/
│       └── ListCommand.php
├── SearchCommand.php
├── HelpCommand.php
└── ...
```

### Components

1. **Command Class** (`app/Commands/`) - Core business logic
2. **Console Wrapper** (`app/Console/Commands/`) - CLI interface (thin wrapper)
3. **MCP Tool** (`app/Tools/Orchestration/`) - MCP interface (delegates to command)
4. **Command Registry** (`app/Services/CommandRegistry.php`) - Maps command names to classes

---

## Creating a New Command

### Step 1: Create Command Class

Create your command in `app/Commands/{Category}/{Name}Command.php`:

```php
<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Services\SprintOrchestrationService;

class SaveCommand extends BaseCommand
{
    // Properties for command parameters
    protected string $code;
    protected ?string $title = null;
    protected ?string $priority = null;
    
    // Constructor validates and sets parameters
    public function __construct(array $options = [])
    {
        $this->code = $options['code'] 
            ?? throw new \InvalidArgumentException('Sprint code is required');
        $this->title = $options['title'] ?? null;
        $this->priority = $options['priority'] ?? null;
    }
    
    // Handle method performs the operation
    public function handle(): array
    {
        $service = app(SprintOrchestrationService::class);
        
        $data = array_filter([
            'code' => $this->code,
            'title' => $this->title,
            'priority' => $this->priority,
        ], fn($value) => $value !== null);
        
        $sprint = $service->create($data);
        
        // Return context-aware response
        return $this->respond(
            $sprint,
            $this->context === 'web' ? 'SprintDetailModal' : null
        );
    }
    
    // Metadata for command discovery
    protected function getType(): string
    {
        return 'sprint';
    }
    
    public static function getName(): string
    {
        return 'Sprint Save';
    }
    
    public static function getDescription(): string
    {
        return 'Create or update a sprint';
    }
    
    public static function getUsage(): string
    {
        return '/sprint-save code [options]';
    }
    
    public static function getCategory(): string
    {
        return 'Orchestration';
    }
    
    // MCP input schema
    public static function getInputSchema(): array
    {
        return [
            'code' => [
                'type' => 'string',
                'description' => 'Sprint code (e.g., "SPRINT-67")',
                'required' => true,
            ],
            'title' => [
                'type' => 'string',
                'description' => 'Sprint title',
                'required' => false,
            ],
            'priority' => [
                'type' => 'string',
                'description' => 'Priority level',
                'required' => false,
            ],
        ];
    }
}
```

### Step 2: Register Command

Add to `app/Services/CommandRegistry.php`:

```php
protected static array $phpCommands = [
    // ...
    'sprint-save' => \App\Commands\Orchestration\Sprint\SaveCommand::class,
    'sprint-create' => \App\Commands\Orchestration\Sprint\SaveCommand::class, // alias
    // ...
];
```

### Step 3: Create Console Wrapper (if needed for CLI)

Create `app/Console/Commands/OrchestrationSprintSaveCommand.php`:

```php
<?php

namespace App\Console\Commands;

use App\Commands\Orchestration\Sprint\SaveCommand;
use Illuminate\Console\Command;

class OrchestrationSprintSaveCommand extends Command
{
    protected $signature = 'orchestration:sprint:save
        {code : Sprint code}
        {--title= : Sprint title}
        {--priority= : Priority level}';
    
    protected $description = 'Create or update a sprint';
    
    public function handle(): int
    {
        $command = new SaveCommand([
            'code' => $this->argument('code'),
            'title' => $this->option('title'),
            'priority' => $this->option('priority'),
        ]);
        
        $command->setContext('cli');
        $result = $command->handle();
        
        // Format output for CLI
        $this->info('Sprint saved: ' . $result['data']['code']);
        
        return self::SUCCESS;
    }
}
```

### Step 4: Create/Update MCP Tool (for AI agents)

Update or create `app/Tools/Orchestration/SprintSaveTool.php`:

```php
<?php

namespace App\Tools\Orchestration;

use App\Commands\Orchestration\Sprint\SaveCommand;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SprintSaveTool extends Tool
{
    protected string $name = 'orchestration_sprints_save';
    
    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->required(),
            'title' => $schema->string(),
            'priority' => $schema->string(),
        ];
    }
    
    public function handle(Request $request): Response
    {
        $command = new SaveCommand($request->validated());
        $command->setContext('mcp');
        $result = $command->handle();
        
        return Response::json($result);
    }
}
```

---

## Command Structure

### Required Methods

```php
// Constructor - validate and set properties
public function __construct(array $options = [])

// Execute command logic
public function handle(): array

// Return command type
protected function getType(): string

// Metadata for discovery
public static function getName(): string
public static function getDescription(): string
public static function getUsage(): string
public static function getCategory(): string

// MCP schema
public static function getInputSchema(): array
```

### BaseCommand Methods Available

```php
// Set execution context
$this->setContext('web'|'mcp'|'cli')

// Context-aware response
$this->respond($data, $component = null)

// Individual response types
$this->webResponse($data, $component)
$this->mcpResponse($data)
$this->cliResponse($data)
```

---

## Context-Aware Responses

Commands respond differently based on execution context:

### Web Context
```php
return $this->respond($data, 'SprintDetailModal');
// Returns:
// {
//   "success": true,
//   "component": "SprintDetailModal",
//   "data": {...},
//   "type": "sprint",
//   "meta": {...}
// }
```

### MCP Context
```php
return $this->respond($data, null);
// Returns:
// {
//   "success": true,
//   "data": {...},
//   "type": "sprint",
//   "meta": {...}
// }
```

### CLI Context
```php
return $this->respond($data, null);
// Same as MCP, console wrapper formats for display
```

---

## Registering Commands

### 1. Command Registry

```php
// app/Services/CommandRegistry.php
protected static array $phpCommands = [
    'command-name' => \App\Commands\Path\CommandClass::class,
    'alias' => \App\Commands\Path\CommandClass::class,
];
```

### 2. MCP Server (if exposing via MCP)

```php
// app/Servers/OrchestrationServer.php
protected array $tools = [
    SprintSaveTool::class,
    // ...
];
```

### 3. Config (enable/disable)

```php
// config/orchestration.php
'enabled_tools' => [
    'orchestration_sprints_save' => true,
    // ...
],
```

---

## Testing Commands

### Unit Testing

```php
use Tests\TestCase;
use App\Commands\Orchestration\Sprint\SaveCommand;

class SaveCommandTest extends TestCase
{
    public function test_creates_sprint()
    {
        $command = new SaveCommand([
            'code' => 'SPRINT-TEST',
            'title' => 'Test Sprint',
        ]);
        
        $command->setContext('cli');
        $result = $command->handle();
        
        $this->assertTrue($result['success']);
        $this->assertEquals('SPRINT-TEST', $result['data']['code']);
    }
}
```

### CLI Testing

```bash
php artisan orchestration:sprint:save SPRINT-TEST --title="Test Sprint"
```

### MCP Testing

Use MCP client (AnythingLLM, Claude Desktop, etc.):
```
Use orchestration_sprints_save with code: "SPRINT-TEST", title: "Test Sprint"
```

### Web Testing

In chat interface:
```
/sprint-save SPRINT-TEST title="Test Sprint"
```

---

## Best Practices

### 1. Parameter Validation

✅ **Do**: Validate in constructor
```php
public function __construct(array $options = [])
{
    $this->code = $options['code'] 
        ?? throw new \InvalidArgumentException('Code required');
}
```

❌ **Don't**: Validate in handle()

### 2. Service Delegation

✅ **Do**: Delegate business logic to services
```php
public function handle(): array
{
    $service = app(SprintOrchestrationService::class);
    return $this->respond($service->create($data));
}
```

❌ **Don't**: Put business logic in commands

### 3. Property Naming

✅ **Do**: Use descriptive properties
```php
protected string $sprintCode;
protected ?string $taskName = null;
```

❌ **Don't**: Conflict with BaseCommand properties
```php
protected string $context; // ❌ Conflicts with BaseCommand::$context
```

### 4. Null Handling

✅ **Do**: Use null for optional parameters
```php
protected ?string $title = null;
```

✅ **Do**: Filter nulls before sending to service
```php
$data = array_filter($data, fn($v) => $v !== null);
```

### 5. Response Format

✅ **Do**: Use respond() method
```php
return $this->respond($data, $component);
```

❌ **Don't**: Return raw arrays

### 6. Error Handling

✅ **Do**: Throw exceptions for invalid input
```php
if (!$this->isValid()) {
    throw new \InvalidArgumentException('Invalid input');
}
```

✅ **Do**: Let exceptions bubble up

---

## Examples

### Read Operation (List)

```php
<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\Sprint;

class ListCommand extends BaseCommand
{
    protected ?array $codes = null;
    protected int $limit = 50;
    
    public function __construct(array $options = [])
    {
        $this->codes = $options['codes'] ?? null;
        $this->limit = $options['limit'] ?? 50;
    }
    
    public function handle(): array
    {
        $query = Sprint::query();
        
        if ($this->codes) {
            $query->whereIn('code', $this->codes);
        }
        
        $sprints = $query->limit($this->limit)->get();
        
        return $this->respond(
            $sprints->toArray(),
            $this->context === 'web' ? 'SprintListModal' : null
        );
    }
    
    protected function getType(): string
    {
        return 'sprint';
    }
    
    public static function getName(): string
    {
        return 'Sprint List';
    }
    
    public static function getDescription(): string
    {
        return 'List sprints with optional filters';
    }
    
    public static function getUsage(): string
    {
        return '/sprints [options]';
    }
    
    public static function getCategory(): string
    {
        return 'Orchestration';
    }
    
    public static function getInputSchema(): array
    {
        return [
            'codes' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Filter by sprint codes',
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Max results',
                'default' => 50,
                'required' => false,
            ],
        ];
    }
}
```

### Write Operation (Update)

```php
<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Services\SprintOrchestrationService;

class UpdateStatusCommand extends BaseCommand
{
    protected string $code;
    protected string $status;
    protected ?string $note = null;
    
    public function __construct(array $options = [])
    {
        $this->code = $options['code'] 
            ?? throw new \InvalidArgumentException('Code required');
        $this->status = $options['status'] 
            ?? throw new \InvalidArgumentException('Status required');
        $this->note = $options['note'] ?? null;
    }
    
    public function handle(): array
    {
        $service = app(SprintOrchestrationService::class);
        
        $sprint = $service->updateStatus(
            $this->code,
            $this->status,
            $this->note
        );
        
        return $this->respond(
            $sprint->toArray(),
            $this->context === 'web' ? 'SprintDetailModal' : null
        );
    }
    
    protected function getType(): string
    {
        return 'sprint';
    }
    
    public static function getName(): string
    {
        return 'Sprint Update Status';
    }
    
    public static function getDescription(): string
    {
        return 'Update sprint status';
    }
    
    public static function getUsage(): string
    {
        return '/sprint-status code status [note]';
    }
    
    public static function getCategory(): string
    {
        return 'Orchestration';
    }
    
    public static function getInputSchema(): array
    {
        return [
            'code' => [
                'type' => 'string',
                'description' => 'Sprint code',
                'required' => true,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'New status',
                'required' => true,
            ],
            'note' => [
                'type' => 'string',
                'description' => 'Optional note',
                'required' => false,
            ],
        ];
    }
}
```

---

## Command Naming Conventions

### File Names
- `{Operation}Command.php` (e.g., `SaveCommand.php`, `ListCommand.php`)
- Located in appropriate namespace directory

### Class Names
- `{Operation}Command` (e.g., `SaveCommand`, `UpdateStatusCommand`)

### Command Slugs (Registry)
- `{resource}-{operation}` (e.g., `sprint-save`, `task-assign`)
- Kebab-case
- Can have multiple aliases

### MCP Tool Names
- `orchestration_{resource}_{operation}` (e.g., `orchestration_sprints_save`)
- Snake_case
- Prefixed with system name

### CLI Commands
- `{system}:{resource}:{operation}` (e.g., `orchestration:sprint:save`)
- Kebab-case with colons

---

## Troubleshooting

### Command Not Found
- Check `CommandRegistry.php` registration
- Run `composer dump-autoload`
- Verify file exists and class name matches

### Context Not Set
- Always call `setContext()` before `handle()`
- Console wrappers must set 'cli' context
- MCP tools must set 'mcp' context

### Wrong Response Format
- Use `respond()` method, not raw returns
- Check component name matches UI component
- Verify data structure matches component expectations

### MCP Tool Not Appearing
- Check `OrchestrationServer.php` tools array
- Verify tool class name spelling
- Check `config/orchestration.php` enabled_tools
- Restart MCP server connection

---

## Additional Resources

- **Architecture Docs**: `docs/command systems/COMMAND_SYSTEM_CURRENT_STATE_ANALYSIS.md`
- **Migration Plan**: `docs/command systems/COMMAND_SYSTEM_MIGRATION_PLAN.md`
- **Sprint Summaries**: `docs/command systems/SPRINT_*_SESSION_SUMMARY.md`
- **Testing Guide**: `docs/command systems/SPRINT_4_MCP_TESTING_GUIDE.md`

---

**Questions?** Review existing commands in `app/Commands/Orchestration/` for working examples.
