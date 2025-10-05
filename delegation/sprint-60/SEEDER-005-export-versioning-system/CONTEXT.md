# SEEDER-005: Export & Versioning System — Context

## Current Demo Data Lifecycle

### Current State: No Export Capability
The existing demo seeder system has no export or versioning capability:

```php
// Current workflow - regenerate every time
php artisan migrate:fresh --seed

// Problems:
// 1. Must regenerate all data for every demo
// 2. Inconsistent data between demo sessions
// 3. No way to preserve good demo datasets
// 4. AI generation cost and time for every setup
// 5. No version control of demo data
```

### Current Demo Data Structure
The demo data spans multiple related tables:

```php
// Current demo data distribution
fragments: ~175 records (todos, contacts, chat_messages)
├── todos: 100 records (linked via fragment_id)
├── contacts: 25 records 
├── chat_sessions: 10 records (with embedded + fragment messages)
vaults: 6 records (routing + demo vaults)
projects: 8 records (4 demo projects)
```

## Target Export System

### Export File Structure
Create versioned SQL exports that can be easily imported:

```
database/demo-snapshots/
├── general/
│   ├── general_v1_2025-01-15.sql    # First version
│   ├── general_v2_2025-01-20.sql    # Updated version
│   └── latest.sql → general_v2_2025-01-20.sql (symlink)
├── writer/
│   ├── writer_v1_2025-01-15.sql
│   └── latest.sql → writer_v1_2025-01-15.sql
├── developer/
│   ├── developer_v1_2025-01-15.sql
│   └── latest.sql → developer_v1_2025-01-15.sql
└── metadata/
    ├── export_manifest.json
    └── schema_versions.json
```

### Export Manifest Example
Track all exports with metadata:

```json
// database/demo-snapshots/metadata/export_manifest.json
{
  "scenarios": {
    "general": {
      "latest": "v2_2025-01-20",
      "versions": [
        {
          "version": "v1_2025-01-15",
          "file": "general/general_v1_2025-01-15.sql",
          "created_at": "2025-01-15T10:30:00Z",
          "size": 145823,
          "fragments": 175,
          "todos": 100,
          "contacts": 25,
          "chat_sessions": 10
        },
        {
          "version": "v2_2025-01-20", 
          "file": "general/general_v2_2025-01-20.sql",
          "created_at": "2025-01-20T14:15:00Z",
          "size": 152451,
          "fragments": 180,
          "todos": 105,
          "contacts": 25,
          "chat_sessions": 12
        }
      ]
    },
    "writer": {
      "latest": "v1_2025-01-15",
      "versions": [...]
    }
  },
  "last_updated": "2025-01-20T14:15:00Z"
}
```

## Demo Data Identification Strategy

### Existing Demo Seed Markers
The current system already marks demo data consistently:

```php
// All demo data has this metadata flag
'metadata' => [
    'demo_seed' => true,
    'demo_category' => 'todo',  // or 'contact', 'chat_message'
    'scenario' => 'general',    // Added by enhanced seeders
]
```

### Demo Data Collection Query Patterns
Collect all demo data using existing markers:

```php
// Fragment collection (primary demo data)
$fragments = Fragment::where('metadata->demo_seed', true)
    ->with(['todo', 'contact'])  // Include related models
    ->orderBy('created_at')
    ->get();

// Related model collection  
$todos = Todo::whereHas('fragment', function ($query) {
    $query->where('metadata->demo_seed', true);
})->get();

$contacts = Contact::where('metadata->demo_seed', true)->get();

$chatSessions = ChatSession::where('metadata->demo_seed', true)->get();

$vaults = Vault::where('metadata->demo_seed', true)->get();

$projects = Project::where('metadata->demo_seed', true)->get();
```

## SQL Export Generation Strategy

### Table Dependency Order
Export tables in correct dependency order to avoid foreign key conflicts:

```
1. vaults (no dependencies)
2. projects (depends on vaults)
3. fragments (depends on vaults, projects)
4. todos (depends on fragments)
5. contacts (independent, but fragments may reference)
6. chat_sessions (independent)
```

### Sample Export SQL Structure
```sql
-- Demo Data Export  
-- Scenario: general
-- Generated: 2025-01-15 10:30:00
-- Version: v1_2025-01-15
-- Total Fragments: 175

-- Cleanup existing demo data
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM todos WHERE fragment_id IN (
    SELECT id FROM fragments WHERE metadata->>'demo_seed' = 'true'
);
DELETE FROM contacts WHERE metadata->>'demo_seed' = 'true';
DELETE FROM chat_sessions WHERE metadata->>'demo_seed' = 'true';
DELETE FROM fragments WHERE metadata->>'demo_seed' = 'true';
DELETE FROM projects WHERE metadata->>'demo_seed' = 'true';
DELETE FROM vaults WHERE metadata->>'demo_seed' = 'true';

-- Vault exports
INSERT INTO vaults (id, name, slug, description, metadata, created_at, updated_at) VALUES
(1, 'Personal', 'personal', 'Personal life organization', '{"demo_seed": true, "scenario": "general"}', '2025-01-15 10:00:00', '2025-01-15 10:00:00'),
(2, 'Work', 'work', 'Professional work projects', '{"demo_seed": true, "scenario": "general"}', '2025-01-15 10:00:00', '2025-01-15 10:00:00');

-- Project exports  
INSERT INTO projects (id, name, description, status, vault_id, metadata, created_at, updated_at) VALUES
(1, 'Home Lab Setup', 'Setting up home server and network', 'active', 1, '{"demo_seed": true, "scenario": "general"}', '2025-01-15 10:00:00', '2025-01-15 10:00:00'),
(2, 'POS System Upgrade', 'Retail point-of-sale system modernization', 'active', 2, '{"demo_seed": true, "scenario": "general"}', '2025-01-15 10:00:00', '2025-01-15 10:00:00');

-- Fragment exports (large section with all 175 fragments)
INSERT INTO fragments (id, type, title, message, tags, relationships, metadata, state, vault, project_id, inbox_status, inbox_at, created_at, updated_at) VALUES
(1, 'todo', 'Pick up dry cleaning from Main Street cleaners', 'TODO: Pick up dry cleaning from Main Street cleaners by 6pm', '["demo", "personal", "errands"]', '[]', '{"demo_seed": true, "demo_category": "todo", "scenario": "general"}', '{"status": "open", "priority": "medium"}', 'personal', 1, 'accepted', '2025-01-15 08:30:00', '2025-01-15 08:30:00', '2025-01-15 08:30:00'),
-- ... (continues for all fragments)

-- Todo model exports
INSERT INTO todos (id, fragment_id, title, state) VALUES
(1, 1, 'Pick up dry cleaning from Main Street cleaners', '{"status": "open", "priority": "medium"}'),
-- ... (continues for all todos)

-- Contact exports
INSERT INTO contacts (id, name, role, company, email, phone, metadata, created_at, updated_at) VALUES
(1, 'Sarah Chen', 'DevOps Lead', 'TechCorp', 'sarah.chen@techcorp.com', NULL, '{"demo_seed": true, "scenario": "general"}', '2025-01-15 09:00:00', '2025-01-15 09:00:00'),
-- ... (continues for all contacts)

-- Chat session exports
INSERT INTO chat_sessions (id, title, messages, message_count, last_activity_at, metadata, created_at, updated_at) VALUES
(1, 'Planning weekend home lab work', '[{"content": "Need to configure the router this weekend", "role": "user", "timestamp": "2 hours ago"}]', 5, '2025-01-15 10:00:00', '{"demo_seed": true, "scenario": "general"}', '2025-01-15 10:00:00', '2025-01-15 10:00:00'),
-- ... (continues for all chat sessions)

SET FOREIGN_KEY_CHECKS = 1;

-- Verification queries
SELECT 'Fragments' as type, COUNT(*) as count FROM fragments WHERE metadata->>'demo_seed' = 'true'
UNION ALL
SELECT 'Todos', COUNT(*) FROM todos WHERE fragment_id IN (SELECT id FROM fragments WHERE metadata->>'demo_seed' = 'true')
UNION ALL  
SELECT 'Contacts', COUNT(*) FROM contacts WHERE metadata->>'demo_seed' = 'true'
UNION ALL
SELECT 'Chat Sessions', COUNT(*) FROM chat_sessions WHERE metadata->>'demo_seed' = 'true'
UNION ALL
SELECT 'Vaults', COUNT(*) FROM vaults WHERE metadata->>'demo_seed' = 'true'
UNION ALL
SELECT 'Projects', COUNT(*) FROM projects WHERE metadata->>'demo_seed' = 'true';
```

## Import Strategy

### Transaction-Based Import
Ensure atomicity of imports:

```php
// Import with transaction safety
DB::transaction(function () use ($exportFile) {
    // 1. Cleanup existing demo data (handled by export SQL)
    // 2. Import new data from SQL file
    $this->executeSqlImport($exportFile);
    
    // 3. Verify import success
    $this->verifyImportIntegrity();
});
```

### SQL Statement Execution
Parse and execute SQL statements safely:

```php
// Safe SQL execution for imports
private function executeSqlImport(string $exportFile): void
{
    $sql = file_get_contents($exportFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        preg_split('/;\s*$/m', $sql),
        fn($stmt) => !empty(trim($stmt)) && !str_starts_with(trim($stmt), '--')
    );
    
    foreach ($statements as $statement) {
        try {
            DB::unprepared(trim($statement) . ';');
        } catch (Exception $e) {
            throw new ImportException("Failed to execute SQL statement: {$e->getMessage()}");
        }
    }
}
```

## Integration with Enhanced Seeder System

### Export Trigger Points
Export can be triggered after successful seeding:

```php
// Enhanced seeder integration
class EnhancedDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ... existing seeding logic ...
        
        // Optional: Auto-export after successful seeding
        if (config('fragments.demo_seeder.auto_export', false)) {
            $this->exportGeneratedData($scenarioName);
        }
    }
    
    private function exportGeneratedData(string $scenarioName): void
    {
        $exportService = app(DemoDataExportService::class);
        $result = $exportService->exportScenario($scenarioName);
        
        $this->command?->info("Demo data exported: {$result->getExportPath()}");
    }
}
```

### Console Command Integration
Provide console commands for manual export/import:

```bash
# Export current demo data
php artisan demo:export general

# Import specific version
php artisan demo:import general --version=v1_2025-01-15

# Import latest version
php artisan demo:import general --latest

# List available exports
php artisan demo:list-exports

# Compare export versions
php artisan demo:compare-exports general v1 v2
```

## Use Cases and Workflows

### Development Workflow
```bash
# 1. Generate new demo data with AI
php artisan demo:seed-enhanced --scenario=general

# 2. Verify data quality and relationships
php artisan demo:validate-enhanced --scenario=general

# 3. Export for preservation
php artisan demo:export general

# 4. Commit export to git for team sharing
git add database/demo-snapshots/general/
git commit -m "Add general demo scenario v2"
```

### Demo Setup Workflow
```bash
# Quick demo environment setup (no AI generation needed)
php artisan migrate:fresh
php artisan demo:import general --latest

# Verify import success
php artisan demo:validate-enhanced --scenario=general --imported
```

### Team Collaboration Workflow
```bash
# Developer A creates and exports new scenario
php artisan demo:seed-enhanced --scenario=writer
php artisan demo:export writer
git push

# Developer B imports the scenario
git pull
php artisan demo:import writer --latest
```

## Performance Considerations

### Export Optimization
- **Streaming**: Stream large exports to avoid memory issues
- **Compression**: Consider gzip compression for large exports
- **Parallel Processing**: Export large tables in parallel where possible
- **Incremental Exports**: Support delta exports for minimal changes

### Import Optimization
- **Batch Inserts**: Use batch inserts for large datasets
- **Index Management**: Temporarily disable indexes during import
- **Foreign Key Checks**: Disable during import, re-enable after
- **Memory Management**: Process large imports in chunks

### File Management
- **Storage Efficiency**: Balance readability with file size
- **Git Integration**: Ensure exports work well with version control
- **Cleanup**: Automatic cleanup of old export versions
- **Metadata Indexing**: Fast lookup of export information

## Quality Assurance

### Export Integrity
- **Complete Data**: Ensure all demo data is captured
- **Referential Integrity**: Maintain all relationships
- **Temporal Consistency**: Preserve creation timestamps
- **Metadata Preservation**: Keep all demo seed markers

### Import Verification
- **Row Counts**: Verify expected number of records imported
- **Relationship Validation**: Check foreign key integrity
- **Content Sampling**: Spot check imported content quality
- **Performance Impact**: Monitor import speed and resource usage

### Version Management
- **Unique Versioning**: Ensure version numbers are unique
- **Manifest Accuracy**: Keep manifest in sync with actual files
- **Symlink Management**: Maintain latest symlinks correctly
- **Storage Cleanup**: Manage disk space with old versions

This export and versioning system provides the crucial capability to preserve and share high-quality AI-generated demo data, eliminating the need for expensive regeneration while ensuring consistent demo experiences across environments and team members.