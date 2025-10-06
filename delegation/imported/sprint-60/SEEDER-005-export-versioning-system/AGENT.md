# SEEDER-005: Export & Versioning System Agent

## Agent Mission

You are a database export specialist and Laravel backend developer focused on creating efficient data export and versioning systems. Your mission is to build a SQL export system for versioned demo data that enables consistent demo environments and quick deployment of realistic datasets without requiring expensive AI generation for every demo setup.

## Core Objectives

### Primary Goal
Create a demo data export and versioning system that:
- Generates SQL snapshots of complete demo scenarios
- Enables versioned demo data for consistent demo environments
- Provides hot-swap capability for demo setups
- Stores exports in `database/demo-snapshots/` for git version control
- Supports incremental updates and delta exports

### Success Metrics
- [ ] Generate complete SQL exports for any demo scenario
- [ ] Version exports with scenario name and timestamp
- [ ] Enable quick demo environment setup without AI generation
- [ ] Support multiple scenarios and comparison between versions
- [ ] Maintain export integrity and data consistency

## Technical Specifications

### Export System Architecture
```php
// Core export system pattern
class DemoDataExportService
{
    public function exportScenario(string $scenarioName): ExportResult
    {
        $exportData = $this->collectDemoData($scenarioName);
        $sqlExport = $this->generateSqlExport($exportData);
        $exportPath = $this->saveExport($scenarioName, $sqlExport);
        
        return new ExportResult($exportPath, $exportData->getStatistics());
    }
    
    public function importScenario(string $exportFile): ImportResult
    {
        $this->validateExportFile($exportFile);
        $this->clearExistingDemoData();
        $this->executeSqlImport($exportFile);
        
        return new ImportResult($this->getImportStatistics());
    }
}
```

### Export Storage Structure
```
database/demo-snapshots/
├── general/
│   ├── general_v1_2025-01-15.sql
│   ├── general_v2_2025-01-20.sql
│   └── latest.sql (symlink)
├── writer/
│   ├── writer_v1_2025-01-15.sql
│   └── latest.sql (symlink)
├── developer/
│   ├── developer_v1_2025-01-15.sql
│   └── latest.sql (symlink)
└── metadata/
    ├── export_manifest.json
    └── schema_versions.json
```

### Export File Format
Each export includes:
- Complete demo dataset (all tables with demo_seed = true)
- Metadata about scenario configuration
- Export timestamps and versioning information
- Schema compatibility information

## Implementation Approach

### 1. Demo Data Collection System
```php
// app/Services/Demo/Export/DemoDataCollector.php
class DemoDataCollector
{
    public function collectDemoData(string $scenarioName): DemoDataCollection
    {
        return new DemoDataCollection([
            'scenario' => $scenarioName,
            'fragments' => $this->collectDemoFragments(),
            'todos' => $this->collectDemoTodos(),
            'contacts' => $this->collectDemoContacts(),
            'chat_sessions' => $this->collectDemoChatSessions(),
            'vaults' => $this->collectDemoVaults(),
            'projects' => $this->collectDemoProjects(),
            'metadata' => $this->collectExportMetadata($scenarioName),
        ]);
    }
    
    private function collectDemoFragments(): Collection
    {
        return Fragment::where('metadata->demo_seed', true)
            ->with(['todo', 'contact'])
            ->orderBy('created_at')
            ->get();
    }
    
    private function collectDemoTodos(): Collection
    {
        return Todo::whereHas('fragment', function ($query) {
            $query->where('metadata->demo_seed', true);
        })->get();
    }
    
    private function collectDemoContacts(): Collection
    {
        return Contact::where('metadata->demo_seed', true)
            ->orderBy('created_at')
            ->get();
    }
    
    private function collectDemoChatSessions(): Collection
    {
        return ChatSession::where('metadata->demo_seed', true)
            ->orderBy('created_at')
            ->get();
    }
    
    private function collectDemoVaults(): Collection
    {
        return Vault::where('metadata->demo_seed', true)
            ->orderBy('created_at')
            ->get();
    }
    
    private function collectDemoProjects(): Collection
    {
        return Project::where('metadata->demo_seed', true)
            ->orderBy('created_at')
            ->get();
    }
}
```

### 2. SQL Export Generator
```php
// app/Services/Demo/Export/SqlExportGenerator.php
class SqlExportGenerator
{
    public function generateSqlExport(DemoDataCollection $data): string
    {
        $sql = collect();
        
        // Header with metadata
        $sql->push($this->generateExportHeader($data));
        
        // Disable foreign key checks for import
        $sql->push("SET FOREIGN_KEY_CHECKS = 0;");
        
        // Generate table exports in dependency order
        $sql->push($this->generateVaultExports($data->getVaults()));
        $sql->push($this->generateProjectExports($data->getProjects()));
        $sql->push($this->generateFragmentExports($data->getFragments()));
        $sql->push($this->generateTodoExports($data->getTodos()));
        $sql->push($this->generateContactExports($data->getContacts()));
        $sql->push($this->generateChatSessionExports($data->getChatSessions()));
        
        // Re-enable foreign key checks
        $sql->push("SET FOREIGN_KEY_CHECKS = 1;");
        
        // Footer with verification queries
        $sql->push($this->generateExportFooter($data));
        
        return $sql->filter()->implode("\n\n");
    }
    
    private function generateExportHeader(DemoDataCollection $data): string
    {
        $metadata = $data->getMetadata();
        
        return "-- Demo Data Export
-- Scenario: {$metadata['scenario']}
-- Generated: {$metadata['exported_at']}
-- Version: {$metadata['version']}
-- Total Fragments: {$data->getFragments()->count()}
-- Total Records: {$data->getTotalRecordCount()}

-- Cleanup existing demo data
DELETE FROM todos WHERE fragment_id IN (SELECT id FROM fragments WHERE metadata->>'demo_seed' = 'true');
DELETE FROM contacts WHERE metadata->>'demo_seed' = 'true';
DELETE FROM chat_sessions WHERE metadata->>'demo_seed' = 'true';
DELETE FROM fragments WHERE metadata->>'demo_seed' = 'true';
DELETE FROM projects WHERE metadata->>'demo_seed' = 'true';
DELETE FROM vaults WHERE metadata->>'demo_seed' = 'true';";
    }
    
    private function generateFragmentExports(Collection $fragments): string
    {
        if ($fragments->isEmpty()) {
            return "-- No fragments to export";
        }
        
        $sql = ["-- Fragment exports"];
        
        foreach ($fragments as $fragment) {
            $sql[] = $this->generateFragmentInsert($fragment);
        }
        
        return implode("\n", $sql);
    }
    
    private function generateFragmentInsert(Fragment $fragment): string
    {
        $values = [
            'id' => $fragment->id,
            'type' => $fragment->type,
            'title' => $this->escapeString($fragment->title),
            'message' => $this->escapeString($fragment->message),
            'tags' => $fragment->tags ? $this->escapeJson($fragment->tags) : 'NULL',
            'relationships' => $fragment->relationships ? $this->escapeJson($fragment->relationships) : 'NULL',
            'metadata' => $this->escapeJson($fragment->metadata),
            'state' => $fragment->state ? $this->escapeJson($fragment->state) : 'NULL',
            'vault' => $fragment->vault ? $this->escapeString($fragment->vault) : 'NULL',
            'project_id' => $fragment->project_id ?? 'NULL',
            'inbox_status' => $this->escapeString($fragment->inbox_status),
            'inbox_at' => $fragment->inbox_at ? "'{$fragment->inbox_at->toDateTimeString()}'" : 'NULL',
            'created_at' => "'{$fragment->created_at->toDateTimeString()}'",
            'updated_at' => "'{$fragment->updated_at->toDateTimeString()}'",
        ];
        
        $columns = implode(', ', array_keys($values));
        $valuesList = implode(', ', array_values($values));
        
        return "INSERT INTO fragments ({$columns}) VALUES ({$valuesList});";
    }
}
```

### 3. Export Versioning System
```php
// app/Services/Demo/Export/ExportVersionManager.php
class ExportVersionManager
{
    private string $exportBasePath;
    
    public function __construct()
    {
        $this->exportBasePath = database_path('demo-snapshots');
    }
    
    public function saveExport(string $scenarioName, string $sqlContent): string
    {
        $version = $this->generateVersion($scenarioName);
        $exportPath = $this->getExportPath($scenarioName, $version);
        
        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($exportPath));
        
        // Save the export
        file_put_contents($exportPath, $sqlContent);
        
        // Update latest symlink
        $this->updateLatestSymlink($scenarioName, $exportPath);
        
        // Update manifest
        $this->updateExportManifest($scenarioName, $version, $exportPath);
        
        return $exportPath;
    }
    
    private function generateVersion(string $scenarioName): string
    {
        $existingVersions = $this->getExistingVersions($scenarioName);
        $nextVersionNumber = count($existingVersions) + 1;
        $date = now()->format('Y-m-d');
        
        return "v{$nextVersionNumber}_{$date}";
    }
    
    private function getExportPath(string $scenarioName, string $version): string
    {
        return "{$this->exportBasePath}/{$scenarioName}/{$scenarioName}_{$version}.sql";
    }
    
    private function updateLatestSymlink(string $scenarioName, string $exportPath): void
    {
        $latestPath = "{$this->exportBasePath}/{$scenarioName}/latest.sql";
        
        // Remove existing symlink
        if (file_exists($latestPath)) {
            unlink($latestPath);
        }
        
        // Create new symlink
        symlink(basename($exportPath), $latestPath);
    }
    
    private function updateExportManifest(string $scenarioName, string $version, string $exportPath): void
    {
        $manifestPath = "{$this->exportBasePath}/metadata/export_manifest.json";
        
        $manifest = file_exists($manifestPath) 
            ? json_decode(file_get_contents($manifestPath), true) 
            : [];
        
        $manifest['scenarios'][$scenarioName]['versions'][] = [
            'version' => $version,
            'file' => $exportPath,
            'created_at' => now()->toISOString(),
            'size' => filesize($exportPath),
        ];
        
        $manifest['scenarios'][$scenarioName]['latest'] = $version;
        $manifest['last_updated'] = now()->toISOString();
        
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }
}
```

### 4. Import System
```php
// app/Services/Demo/Export/DemoDataImportService.php
class DemoDataImportService
{
    public function importFromFile(string $exportFile): ImportResult
    {
        $this->validateExportFile($exportFile);
        
        DB::transaction(function () use ($exportFile) {
            $this->executeSqlImport($exportFile);
        });
        
        return new ImportResult($this->getImportStatistics());
    }
    
    public function importLatestScenario(string $scenarioName): ImportResult
    {
        $latestFile = $this->getLatestExportFile($scenarioName);
        
        if (!$latestFile) {
            throw new ExportNotFoundException("No exports found for scenario: {$scenarioName}");
        }
        
        return $this->importFromFile($latestFile);
    }
    
    private function executeSqlImport(string $exportFile): void
    {
        $sql = file_get_contents($exportFile);
        
        // Split SQL into individual statements
        $statements = array_filter(
            preg_split('/;\s*$/m', $sql),
            fn($stmt) => !empty(trim($stmt)) && !str_starts_with(trim($stmt), '--')
        );
        
        foreach ($statements as $statement) {
            DB::unprepared(trim($statement) . ';');
        }
    }
    
    private function getImportStatistics(): array
    {
        return [
            'fragments' => Fragment::where('metadata->demo_seed', true)->count(),
            'todos' => Todo::whereHas('fragment', fn($q) => $q->where('metadata->demo_seed', true))->count(),
            'contacts' => Contact::where('metadata->demo_seed', true)->count(),
            'chat_sessions' => ChatSession::where('metadata->demo_seed', true)->count(),
            'vaults' => Vault::where('metadata->demo_seed', true)->count(),
            'projects' => Project::where('metadata->demo_seed', true)->count(),
        ];
    }
}
```

## Technical Constraints

### Export Requirements
- **Complete Data**: Export all demo-related data across all tables
- **Referential Integrity**: Maintain foreign key relationships
- **Dependency Order**: Export tables in correct dependency order
- **Cleanup Integration**: Include cleanup of existing demo data

### File Management
- **Git-Friendly**: Store exports in version control
- **Naming Convention**: Consistent file naming with versions and dates
- **Storage Efficiency**: Optimize file sizes while maintaining readability
- **Symlink Management**: Latest exports easily accessible

### Performance Considerations
- **Large Dataset Handling**: Efficient export of large demo datasets
- **Memory Usage**: Stream large exports to avoid memory issues
- **Import Speed**: Fast import for quick demo environment setup
- **Parallel Processing**: Support for concurrent export/import operations

## Development Guidelines

### Code Organization
```
app/Services/Demo/Export/
├── DemoDataExportService.php
├── DemoDataImportService.php
├── DemoDataCollector.php
├── SqlExportGenerator.php
├── ExportVersionManager.php
├── Models/
│   ├── DemoDataCollection.php
│   ├── ExportResult.php
│   └── ImportResult.php
└── Exceptions/
    ├── ExportException.php
    ├── ImportException.php
    └── ExportNotFoundException.php
```

### Console Commands Integration
```php
// Console commands for export/import operations
php artisan demo:export general
php artisan demo:import general --latest
php artisan demo:list-exports
php artisan demo:compare-exports general v1 v2
```

## Key Deliverables

### 1. Export System
- `DemoDataExportService` with complete scenario export capability
- `SqlExportGenerator` with optimized SQL generation
- `DemoDataCollector` with comprehensive data collection
- Export versioning and file management

### 2. Import System
- `DemoDataImportService` with SQL import and transaction handling
- Import validation and error recovery
- Statistics and verification reporting
- Hot-swap capability for demo environments

### 3. Version Management
- `ExportVersionManager` with automatic versioning
- Export manifest tracking and metadata
- Latest export symlink management
- Export comparison and diff capabilities

### 4. Console Interface
- Export generation commands
- Import and restore commands
- Export listing and management commands
- Version comparison utilities

## Implementation Priority

### Phase 1: Core Export System (High Priority)
1. Create demo data collection system
2. Implement SQL export generation
3. Add export file management
4. Create basic console commands

### Phase 2: Import System (High Priority)
1. Implement SQL import system
2. Add import validation and error handling
3. Create import statistics and reporting
4. Add hot-swap capability

### Phase 3: Versioning & Management (Medium Priority)
1. Add export versioning system
2. Implement export manifest tracking
3. Create comparison utilities
4. Add advanced management features

## Success Validation

### Functional Testing
```bash
# Test export generation
php artisan demo:export general
php artisan demo:export writer
php artisan demo:export developer

# Test import functionality
php artisan demo:import general --latest
php artisan demo:import writer --version=v1

# Test version management
php artisan demo:list-exports
php artisan demo:compare-exports general v1 v2
```

### Quality Assurance
- Exported SQL imports without errors
- All demo data preserved accurately
- Referential integrity maintained
- Version tracking works correctly
- File sizes optimized appropriately

This export and versioning system will provide the final piece of the AI-powered demo seeder system, enabling consistent demo environments and quick deployment of realistic datasets without requiring expensive AI generation for every demo setup.