# YAML Command Migration Plan
## Priority: P0 - CRITICAL
## Timeline: 3-5 Days Total

---

## Migration Strategy

### Principles
1. **No Breaking Changes** - Maintain backward compatibility
2. **Incremental Migration** - One command at a time
3. **Test Before Remove** - Verify PHP version works before removing YAML
4. **Cache Awareness** - Clear caches after each migration
5. **Documentation** - Update docs with each migration

---

## Phase 1: Critical Commands (Day 1)
These commands are used frequently and their absence causes confusion.

### 1. `/help` Command
```php
// app/Commands/HelpCommand.php already exists
// Need: Database entry + Frontend component

// Migration SQL:
INSERT INTO commands (
    command, name, description, category, type_slug,
    handler_class, ui_modal_container, 
    available_in_slash, available_in_cli, available_in_mcp,
    is_active, created_at, updated_at
) VALUES (
    '/help', 
    'Help System', 
    'Display available commands grouped by category',
    'System',
    null,  -- No type association needed
    'App\\Commands\\HelpCommand',
    'HelpModal',
    true, false, true,
    true, NOW(), NOW()
);

// Frontend component needed: resources/js/components/system/HelpModal.tsx
```

### 2. `/search` Command
```php
// app/Commands/SearchCommand.php already exists
// Need: Database entry only (uses existing FragmentListModal)

// Migration SQL:
INSERT INTO commands (
    command, name, description, category, type_slug,
    handler_class, ui_modal_container,
    available_in_slash, available_in_cli, available_in_mcp,
    is_active, created_at, updated_at
) VALUES (
    '/search',
    'Search',
    'Search through all fragments and content',
    'Navigation', 
    null,  -- Works across all types
    'App\\Commands\\SearchCommand',
    'FragmentListModal',  -- Reuses existing modal
    true, false, true,
    true, NOW(), NOW()
);
```

### 3. `/todo` Command
```php
// app/Commands/TodoCommand.php already exists
// Complex: Has unified todo system that needs verification

// Migration SQL:
INSERT INTO commands (
    command, name, description, category, type_slug,
    handler_class, ui_modal_container,
    available_in_slash, available_in_cli, available_in_mcp,
    navigation_config, is_active, created_at, updated_at
) VALUES (
    '/todo',
    'Todo Management',
    'Manage personal todo items',
    'Productivity',
    'todo',  -- Links to fragment type
    'App\\Commands\\TodoCommand',
    'TodoManagementModal',
    true, false, true,
    '{"data_prop": "todos", "item_key": "id"}',
    true, NOW(), NOW()
);
```

---

## Phase 2: Workflow Commands (Day 2)
Commands critical for message/task workflows.

### 4. `/accept` Command
```php
// Create: app/Commands/Workflow/AcceptCommand.php
namespace App\Commands\Workflow;

use App\Commands\BaseCommand;
use App\Models\Fragment;

class AcceptCommand extends BaseCommand {
    protected ?string $fragmentId = null;
    
    public function __construct(?string $argument = null) {
        $this->fragmentId = $argument;
    }
    
    public function handle(): array {
        if (!$this->fragmentId) {
            return $this->error('Fragment ID required');
        }
        
        $fragment = Fragment::find($this->fragmentId);
        if (!$fragment) {
            return $this->error('Fragment not found');
        }
        
        // Mark as accepted
        $fragment->metadata = array_merge(
            $fragment->metadata ?? [],
            ['accepted' => true, 'accepted_at' => now()->toIso8601String()]
        );
        $fragment->save();
        
        return $this->respond([
            'message' => 'Fragment accepted',
            'fragment' => $fragment
        ]);
    }
}
```

### 5. `/channels` Command
```php
// Create: app/Commands/ChannelsCommand.php (if not exists)
// Reuse existing ChannelListModal component

INSERT INTO commands (
    command, name, description, category, type_slug,
    handler_class, ui_modal_container,
    available_in_slash, available_in_cli, available_in_mcp,
    is_active, created_at, updated_at
) VALUES (
    '/channels',
    'Channels',
    'List available communication channels',
    'Communication',
    'channel',
    'App\\Commands\\ChannelsCommand',
    'ChannelListModal',
    true, false, true,
    true, NOW(), NOW()
);
```

### 6. `/inbox` Command
```php
// app/Commands/InboxCommand.php should exist
// Verify and add to database

INSERT INTO commands (
    command, name, description, category, type_slug,
    handler_class, ui_modal_container,
    available_in_slash, available_in_cli, available_in_mcp,
    navigation_config, is_active, created_at, updated_at
) VALUES (
    '/inbox',
    'Inbox',
    'View pending messages and tasks',
    'Communication',
    null,
    'App\\Commands\\InboxCommand',
    'InboxModal',
    true, false, true,
    '{"data_prop": "messages", "item_key": "id", "detail_command": "/message-detail"}',
    true, NOW(), NOW()
);
```

---

## Phase 3: Batch Migration (Day 3-4)
Lower priority commands that can be migrated in batches.

### System Commands Group
```sql
-- /clear
INSERT INTO commands (command, name, category, handler_class, available_in_slash)
VALUES ('/clear', 'Clear Screen', 'System', 'App\\Commands\\ClearCommand', true);

-- /ping
INSERT INTO commands (command, name, category, handler_class, available_in_slash)
VALUES ('/ping', 'Ping', 'System', 'App\\Commands\\PingCommand', true);

-- /setup
INSERT INTO commands (command, name, category, handler_class, ui_modal_container, available_in_slash)
VALUES ('/setup', 'Setup Wizard', 'System', 'App\\Commands\\SetupCommand', 'SetupWizard', true);
```

### Content Commands Group
```sql
-- /bookmark
INSERT INTO commands (command, name, category, type_slug, handler_class, ui_modal_container, available_in_slash)
VALUES ('/bookmark', 'Bookmarks', 'Content', 'bookmark', 'App\\Commands\\BookmarkListCommand', 'BookmarkListModal', true);

-- /note
INSERT INTO commands (command, name, category, type_slug, handler_class, ui_modal_container, available_in_slash)
VALUES ('/note', 'Notes', 'Content', 'note', 'App\\Commands\\NoteListCommand', 'DataManagementModal', true);

-- /recall
INSERT INTO commands (command, name, category, handler_class, available_in_slash)
VALUES ('/recall', 'Recall Memory', 'Content', 'App\\Commands\\RecallCommand', true);
```

---

## Phase 4: Cleanup (Day 5)
Remove YAML system entirely.

### 1. Remove YAML Files
```bash
#!/bin/bash
# backup first
tar -czf yaml_commands_backup_$(date +%Y%m%d).tar.gz fragments/commands/

# remove YAML directories
rm -rf fragments/commands/accept
rm -rf fragments/commands/agent-profiles
rm -rf fragments/commands/backlog-list
# ... continue for all directories
```

### 2. Remove YAML Loader Code
```php
// Remove or deprecate:
// - app/Models/CommandRegistry.php (old YAML model)
// - app/Services/Commands/DSL/CommandRunner.php
// - Any YAML parsing logic in CommandController
```

### 3. Update CommandController
```php
// app/Http/Controllers/CommandController.php
// Remove YAML fallback logic
public function handleWebCommand(Request $request) {
    $commandName = ltrim($request->input('command'), '/');
    
    // Only check PHP commands
    if (!CommandRegistry::isPhpCommand($commandName)) {
        return response()->json([
            'success' => false,
            'error' => "Unknown command: /{$commandName}"
        ], 404);
    }
    
    // Continue with PHP command execution...
}
```

---

## Migration Checklist

### Per Command Checklist
- [ ] Create PHP handler class (if needed)
- [ ] Add entry to `commands` table
- [ ] Verify/create frontend component
- [ ] Register component in COMPONENT_MAP
- [ ] Test command execution
- [ ] Clear CommandRegistry cache
- [ ] Update documentation
- [ ] Remove YAML files
- [ ] Verify no regression

### Testing Protocol
```bash
# After each migration:
php artisan cache:clear
php artisan tinker --execute="App\Services\CommandRegistry::clearCache();"

# Test the command
php artisan tinker --execute="
    \$cmd = new App\Commands\HelpCommand();
    dd(\$cmd->handle());
"

# Verify in browser
# Type: /help (should show modal)
```

---

## Component Creation Template

### For Missing Modals (e.g., HelpModal)
```typescript
// resources/js/components/system/HelpModal.tsx
import React from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

interface HelpModalProps {
  data: {
    commands: any[]
    categories: Record<string, any[]>
    markdown_help: string
  }
  onClose: () => void
}

export function HelpModal({ data, onClose }: HelpModalProps) {
  const { categories, markdown_help } = data
  
  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-bold">Available Commands</h2>
        <Button onClick={onClose} variant="ghost">✕</Button>
      </div>
      
      <div className="space-y-6">
        {Object.entries(categories).map(([category, commands]) => (
          <div key={category}>
            <h3 className="font-semibold text-lg mb-2">{category}</h3>
            <div className="space-y-1">
              {commands.map((cmd: any) => (
                <div key={cmd.slug} className="flex justify-between">
                  <code className="text-sm">{cmd.usage}</code>
                  <span className="text-sm text-gray-600">{cmd.description}</span>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}
```

### Register in CommandResultModal
```typescript
// Add to COMPONENT_MAP
import { HelpModal } from '@/components/system/HelpModal'

const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  // ... existing components
  'HelpModal': HelpModal,
}
```

---

## Risk Mitigation

### Rollback Plan
```sql
-- Keep backup of commands table before migration
CREATE TABLE commands_backup_20251011 AS SELECT * FROM commands;

-- If issues, can rollback individual commands
DELETE FROM commands WHERE command = '/help';
-- YAML will take over again
```

### Monitoring
```sql
-- Track command usage
UPDATE commands SET usage_count = usage_count + 1 
WHERE command = '/help';

-- Check for errors
SELECT command, error_count, last_error 
FROM command_metrics 
WHERE error_count > 0;
```

---

## Success Criteria

### Phase 1 Success (Day 1)
- `/help` shows categorized command list
- `/search` returns fragment results
- `/todo` shows todo management interface
- No regression in existing commands

### Phase 2 Success (Day 2)
- `/accept` and `/reject` process fragments
- `/channels` shows available channels
- `/inbox` displays pending items
- Workflow continuity maintained

### Phase 3 Success (Day 3-4)
- All 32 YAML commands migrated
- No "command not found" errors
- All frontend components working

### Phase 4 Success (Day 5)
- YAML system completely removed
- No performance degradation
- Clean codebase with single command system
- Documentation fully updated

---

## Post-Migration Tasks

1. **Documentation Update**
   - Remove all YAML command references
   - Update developer guide
   - Create command creation tutorial

2. **Performance Optimization**
   - Implement command result caching
   - Optimize CommandRegistry queries
   - Add database indexes if needed

3. **Developer Experience**
   - Create artisan command for adding new commands
   - Build command testing framework
   - Add command validation

```bash
# Future: php artisan make:command sprints/export
# Automatically creates handler, adds to DB, creates test
```