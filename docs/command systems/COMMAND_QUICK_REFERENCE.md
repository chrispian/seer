# Command System Quick Reference

**Last Updated**: 2025-10-09  
**System**: Pure PHP (YAML deprecated)

---

## For AI Agents: Critical Rules 🚨

### ⛔ NEVER DO THIS
- ❌ Don't revert commands to YAML
- ❌ Don't create duplicate registry entries
- ❌ Don't reference non-existent command classes
- ❌ Don't hardcode component names without checking they exist

### ✅ ALWAYS DO THIS
- ✅ Check `CommandRegistry::$phpCommands` before modifying
- ✅ Verify command class exists in `app/Commands/`
- ✅ Verify component exists in `resources/js/components/`
- ✅ Follow existing command patterns (use `SearchCommand.php` as reference)

---

## Command Structure Template

### Minimal Command

```php
<?php

namespace App\Commands;

class ExampleCommand extends BaseCommand
{
    protected ?string $argument = null;

    public function __construct(?string $argument = null)
    {
        $this->argument = $argument;
    }

    public function handle(): array
    {
        $data = $this->getData();
        
        return [
            'type' => 'example',
            'component' => 'ExampleListModal',
            'data' => $data
        ];
    }
    
    private function getData(): array
    {
        // Your data fetching logic here
        return [];
    }
    
    public static function getName(): string
    {
        return 'Example Command';
    }
    
    public static function getDescription(): string
    {
        return 'Brief description of what this command does';
    }
    
    public static function getUsage(): string
    {
        return '/example [optional-arg]';
    }
    
    public static function getCategory(): string
    {
        return 'Category'; // Navigation, Orchestration, Utility, etc.
    }
}
```

---

## Return Structure

### Standard Response

```php
return [
    'type' => 'resource-type',      // Type identifier (fragment, task, agent, etc.)
    'component' => 'ComponentName', // React component to render
    'data' => $results              // Array of data
];
```

### Message-Only Response

```php
return [
    'type' => 'success',
    'message' => 'Operation completed successfully'
];
```

### Error Response

```php
throw new \Exception('Error message');
// Or
return [
    'type' => 'error',
    'message' => 'Something went wrong'
];
```

---

## Available Components

### Orchestration
- `SprintListModal` - Sprint listing
- `SprintDetailModal` - Sprint details with tasks
- `TaskListModal` - Task listing
- `TaskDetailModal` - Task details with activity
- `AgentProfileListModal` - Agent profiles
- `BacklogListModal` - Backlog items

### Content
- `FragmentListModal` - Generic fragment listing (used by search, recall, inbox)
- `TodoManagementModal` - Todo checklist management
- `ChannelListModal` - Channel listing

### System
- `TypeManagementModal` - Fragment type management
- `RoutingInfoModal` - Routing information display
- `UnifiedListModal` - Generic configurable list (future)

---

## Command Categories

### Navigation
- `/search` - Search fragments
- `/recall` - Memory/recall search
- `/inbox` - Inbox items
- `/frag` - Fragment lookup

### Orchestration
- `/sprints` - Sprint list
- `/tasks` - Task list
- `/backlog` - Backlog items
- `/agents` - Agent profiles

### Utility
- `/help` - Help information
- `/clear` - Clear chat
- `/channels` - Channel list
- `/routing` - Routing info
- `/types` - Type management

### Content
- `/todo` - Todo management
- `/note` - Create note
- `/notes` - Note list
- `/bookmark` - Bookmark management

---

## Registry Entry Format

```php
protected static array $phpCommands = [
    // Primary command
    'command-name' => \App\Commands\CommandNameCommand::class,
    
    // Aliases (same class, different trigger)
    'alias' => \App\Commands\CommandNameCommand::class,
    'short' => \App\Commands\CommandNameCommand::class,
];
```

### Naming Conventions

- **Command Slug**: lowercase-with-dashes (`task-list`, `sprint-detail`)
- **Command Class**: PascalCase with Command suffix (`TaskListCommand`)
- **Component**: PascalCase with Modal suffix (`TaskListModal`)

---

## Adding a New Command

### Step 1: Create Command Class

```bash
# File: app/Commands/FooListCommand.php
php artisan make:command FooListCommand  # (if scaffold exists)
```

### Step 2: Register in CommandRegistry

```php
// app/Services/CommandRegistry.php
protected static array $phpCommands = [
    // ... existing commands
    'foo' => \App\Commands\FooListCommand::class,
    'foo-list' => \App\Commands\FooListCommand::class,
    'f' => \App\Commands\FooListCommand::class,  // alias
];
```

### Step 3: Add Frontend Component (if needed)

```typescript
// resources/js/components/foo/FooListModal.tsx
export function FooListModal({ isOpen, onClose, data }) {
  // Your modal implementation
}
```

### Step 4: Add Component Routing

```typescript
// resources/js/islands/chat/CommandResultModal.tsx
switch (currentResult.component) {
  // ... existing cases
  case 'FooListModal':
    return (
      <FooListModal
        isOpen={isOpen}
        onClose={onClose}
        data={currentResult.data}
      />
    )
}
```

### Step 5: Test

```bash
# In chat UI
/foo
/foo-list
/f
```

---

## Common Patterns

### List Command with Pagination

```php
class FooListCommand extends BaseCommand
{
    public function handle(): array
    {
        $items = \App\Models\Foo::query()
            ->latest()
            ->limit(50)
            ->get();
            
        return [
            'type' => 'foo',
            'component' => 'FooListModal',
            'data' => $items->toArray()
        ];
    }
}
```

### Search Command with Query

```php
class FooSearchCommand extends BaseCommand
{
    protected ?string $query = null;

    public function __construct(?string $query = null)
    {
        $this->query = $query;
    }

    public function handle(): array
    {
        $results = \App\Models\Foo::query()
            ->when($this->query, function ($q) {
                $q->where('name', 'like', "%{$this->query}%");
            })
            ->limit(50)
            ->get();
            
        return [
            'type' => 'foo',
            'component' => 'FooListModal',
            'data' => $results->toArray()
        ];
    }
}
```

### Detail Command with ID

```php
class FooDetailCommand extends BaseCommand
{
    protected ?string $id = null;

    public function __construct(?string $id = null)
    {
        $this->id = $id;
    }

    public function handle(): array
    {
        $item = \App\Models\Foo::find($this->id);
        
        if (!$item) {
            throw new \Exception("Foo not found: {$this->id}");
        }
            
        return [
            'type' => 'foo-detail',
            'component' => 'FooDetailModal',
            'data' => $item->toArray()
        ];
    }
}
```

---

## Troubleshooting

### Command Not Found

```
Error: Command not recognized: foo
```

**Fix**: Add command to `CommandRegistry::$phpCommands`

### Component Not Rendering

```
Warning: Unknown component: FooModal
```

**Fix**: 
1. Check component exists in `resources/js/components/`
2. Add case in `CommandResultModal.tsx`

### Class Not Found

```
Error: Class App\Commands\FooCommand not found
```

**Fix**: 
1. Create command class file
2. Run `composer dump-autoload`

### Data Not Displaying

**Check**:
1. Command returns `data` array
2. Component expects correct data structure
3. Console for JavaScript errors

---

## Testing Checklist

Before committing command changes:

- [ ] Command class exists in `app/Commands/`
- [ ] Command registered in `CommandRegistry::$phpCommands`
- [ ] Required methods implemented: `handle()`, `getName()`, `getDescription()`, `getUsage()`, `getCategory()`
- [ ] Component exists in `resources/js/components/`
- [ ] Component case added to `CommandResultModal.tsx`
- [ ] Tested in chat UI: `/command-name`
- [ ] Tested with arguments: `/command-name arg1 arg2`
- [ ] PHP syntax valid: `php -l app/Commands/FooCommand.php`
- [ ] Autoloader regenerated: `composer dump-autoload`

---

## Quick Links

- **Command Registry**: `app/Services/CommandRegistry.php`
- **Command Classes**: `app/Commands/`
- **Modal Components**: `resources/js/components/*/`
- **Modal Routing**: `resources/js/islands/chat/CommandResultModal.tsx`
- **Controller**: `app/Http/Controllers/CommandController.php`

---

## Example Commands to Study

- **Simple**: `ClearCommand.php` - Message-only response
- **List**: `SearchCommand.php` - Standard list with data
- **Detail**: `TaskDetailCommand.php` - Detail view with relations
- **Complex**: `HelpCommand.php` - Dynamic registry-driven

---

**Remember**: When in doubt, check existing commands. They are your best documentation! 📚
