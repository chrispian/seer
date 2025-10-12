# /search Command Reference

## Overview
The `/search` command searches through all fragments and content, returning matching results. This is an example of a **utility command** that works across all data types rather than being tied to a specific type.

## Database Configuration

### commands table entry
```json
{
  "id": [auto-generated],
  "command": "/search",
  "name": "Search",
  "description": "Search through all fragments and content",
  "category": "Navigation",
  "type_slug": null,  // Not tied to specific type
  "handler_class": "App\\Commands\\SearchCommand",
  "available_in_slash": true,
  "available_in_cli": false,
  "available_in_mcp": true,
  "ui_modal_container": "FragmentListModal",
  "ui_layout_mode": "list",
  "ui_base_renderer": null,
  "navigation_config": null,  // No drill-down navigation
  "default_sort": null,
  "pagination_default": 200
}
```

### Type Association
**NONE** - This is a cross-cutting utility command that searches all fragment types.

## Dependencies

### Backend
- **Model**: `App\Models\Fragment` (searches all fragments)
- **Handler**: `App\Commands\SearchCommand`
- **Relations**: Fragment belongsTo category

### Frontend
- **Component**: `FragmentListModal` (resources/js/components/fragments/FragmentListModal.tsx)
- **Registered In**: `CommandResultModal.tsx` COMPONENT_MAP

## Execution Flow

### Step 1: Command Entry
User types `/search [query]` in chat interface
- Example: `/search authentication`
- Example: `/search` (shows recent fragments)

### Step 2: Backend Processing
```
CommandController::handleWebCommand()
  ↓
Extract argument: 'authentication'
  ↓
CommandRegistry::getPhpCommand('search')
  → Returns: App\Commands\SearchCommand
  ↓
new SearchCommand('authentication')->handle()
  ↓
Fragment::query()
  ->with('category')
  ->where(function($q) {
    $q->where('message', 'like', '%authentication%')
      ->orWhere('title', 'like', '%authentication%')
  })
  ->latest()
  ->limit(200)
  ->get()
  ↓
Transform and sanitize UTF-8
  ↓
Return fragment array
```

### Step 3: Response Structure
```json
{
  "success": true,
  "type": "fragment",
  "component": "FragmentListModal",
  "data": [
    {
      "id": 123,
      "title": "Authentication Implementation",
      "message": "Implement JWT-based authentication system...",
      "type": "task",
      "category": "Development",
      "metadata": {
        "author": "user",
        "tags": ["security", "backend"]
      },
      "created_at": "2025-10-01T10:00:00Z",
      "updated_at": "2025-10-11T15:30:00Z",
      "created_human": "11 days ago",
      "preview": "Implement JWT-based authentication system with refresh tokens..."
    }
  ]
}
```

### Step 4: Frontend Rendering
```typescript
// CommandResultModal.tsx
1. Receives response
2. Looks up 'FragmentListModal' in COMPONENT_MAP
3. Calls buildComponentProps():
   - Sets data array directly (no navigation needed)
   - No click handlers (fragments are read-only in this view)
4. Renders <FragmentListModal {...props} />
```

### Step 5: Display Features
```typescript
// FragmentListModal displays:
- Fragment title and preview
- Type badge
- Category
- Creation time (human-readable)
- Metadata tags
- Full message on expand
```

## Key Differences from Type Commands

### 1. No Type Association
- `type_slug` is null
- Works across all fragment types
- Not limited to specific data model

### 2. Simple Data Structure
- Returns flat array, not nested object
- No `data_prop` configuration needed
- Direct data mapping

### 3. No Navigation
- No `navigation_config`
- No detail command
- Fragments viewed in-place (expandable rows)

### 4. Constructor Arguments
```php
// Type commands (sprints, tasks)
new ListCommand()  // No arguments

// Search command
new SearchCommand('query string')  // Takes search argument
```

### 5. Cross-Cutting Concerns
- Searches all data types
- UTF-8 sanitization for mixed content
- Category-based filtering

## Implementation Details

### UTF-8 Sanitization
```php
// Ensures all strings are valid UTF-8
$message = mb_convert_encoding($fragment->message ?? '', 'UTF-8', 'UTF-8');
$title = mb_convert_encoding($fragment->title ?? '', 'UTF-8', 'UTF-8');

// Recursive sanitization for metadata
array_walk_recursive($metadata, function (&$value) {
    if (is_string($value)) {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
});
```

### Search Query Handling
```php
if (!empty($this->query)) {
    $fragmentQuery->where(function ($q) {
        $q->where('message', 'like', '%'.$this->query.'%')
          ->orWhere('title', 'like', '%'.$this->query.'%');
    });
}
```

## Testing Commands
```bash
# Test without query (shows recent)
php artisan tinker --execute="
\$cmd = new \App\Commands\SearchCommand();
dd(\$cmd->handle());
"

# Test with search query
php artisan tinker --execute="
\$cmd = new \App\Commands\SearchCommand('test');
\$result = \$cmd->handle();
echo 'Found ' . count(\$result['data']) . ' results';
"
```

## Common Patterns for Utility Commands

### 1. Argument Processing
```php
public function __construct(?string $argument = null) {
    $this->query = $argument;
}
```

### 2. Flexible Response
```php
return [
    'type' => 'fragment',  // Generic type
    'component' => 'FragmentListModal',
    'data' => $results  // Simple array
];
```

### 3. No Config Needed
- No navigation_config
- No type_slug
- No complex UI configuration

## Other Utility Command Examples
- `/help` - Shows available commands
- `/clear` - Clears chat interface
- `/ping` - Tests system responsiveness
- `/stats` - Shows system statistics

## Creating New Utility Commands

### Template for utility command:
```php
namespace App\Commands;

class UtilityCommand extends BaseCommand {
    protected ?string $argument = null;
    
    public function __construct(?string $argument = null) {
        $this->argument = $argument;
    }
    
    public function handle(): array {
        // Process across multiple types/models
        $results = $this->processUtilityFunction();
        
        return [
            'type' => 'utility',
            'component' => 'UtilityModal',  // or reuse existing
            'data' => $results
        ];
    }
}
```

### Database entry for utility command:
```sql
INSERT INTO commands (
    command, name, description, category, 
    type_slug,  -- NULL for utilities
    handler_class, 
    ui_modal_container,
    available_in_slash, available_in_mcp
) VALUES (
    '/utility', 'Utility Name', 'Description',
    'System',  -- or 'Navigation', 'Tools'
    NULL,      -- No type association
    'App\\Commands\\UtilityCommand',
    'ExistingModal',  -- Reuse existing modal
    true, true
);
```