# SEEDER-005: Export & Versioning System — Implementation Plan

## Executive Summary

Build a SQL export and versioning system for demo data that enables consistent demo environments and quick deployment of realistic datasets without requiring expensive AI generation for every demo setup.

**Estimated Effort**: 3-6 hours  
**Priority**: Medium (Operational efficiency)  
**Dependencies**: SEEDER-003 (Enhanced Seeder Components)

## Implementation Phases

### Phase 1: Core Export System (2-3h)

#### 1.1 Demo Data Collection
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
        })->orderBy('id')->get();
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
            ->orderBy('id')
            ->get();
    }
    
    private function collectDemoProjects(): Collection
    {
        return Project::where('metadata->demo_seed', true)
            ->orderBy('id')
            ->get();
    }
    
    private function collectExportMetadata(string $scenarioName): array
    {
        return [
            'scenario' => $scenarioName,
            'exported_at' => now()->toISOString(),
            'version' => $this->generateVersion($scenarioName),
            'database_version' => $this->getDatabaseSchemaVersion(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }
}
```

#### 1.2 Data Collection Model
```php
// app/Services/Demo/Export/Models/DemoDataCollection.php
class DemoDataCollection
{
    public function __construct(
        private array $data
    ) {}
    
    public function getFragments(): Collection
    {
        return collect($this->data['fragments']);
    }
    
    public function getTodos(): Collection
    {
        return collect($this->data['todos']);
    }
    
    public function getContacts(): Collection
    {
        return collect($this->data['contacts']);
    }
    
    public function getChatSessions(): Collection
    {
        return collect($this->data['chat_sessions']);
    }
    
    public function getVaults(): Collection
    {
        return collect($this->data['vaults']);
    }
    
    public function getProjects(): Collection
    {
        return collect($this->data['projects']);
    }
    
    public function getMetadata(): array
    {
        return $this->data['metadata'];
    }
    
    public function getTotalRecordCount(): int
    {
        return $this->getFragments()->count() +
               $this->getTodos()->count() +
               $this->getContacts()->count() +
               $this->getChatSessions()->count() +
               $this->getVaults()->count() +
               $this->getProjects()->count();
    }
    
    public function getStatistics(): array
    {
        return [
            'fragments' => $this->getFragments()->count(),
            'todos' => $this->getTodos()->count(),
            'contacts' => $this->getContacts()->count(),
            'chat_sessions' => $this->getChatSessions()->count(),
            'vaults' => $this->getVaults()->count(),
            'projects' => $this->getProjects()->count(),
            'total_records' => $this->getTotalRecordCount(),
        ];
    }
}
```

#### 1.3 SQL Export Generator
```php
// app/Services/Demo/Export/SqlExportGenerator.php
class SqlExportGenerator
{
    public function generateSqlExport(DemoDataCollection $data): string
    {
        $sql = collect();
        
        // Export header with metadata and cleanup
        $sql->push($this->generateExportHeader($data));
        
        // Disable foreign key checks
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
        
        // Footer with verification
        $sql->push($this->generateExportFooter($data));
        
        return $sql->filter()->implode("\n\n");
    }
    
    private function generateExportHeader(DemoDataCollection $data): string
    {
        $metadata = $data->getMetadata();
        $stats = $data->getStatistics();
        
        return "-- Demo Data Export
-- Scenario: {$metadata['scenario']}
-- Generated: {$metadata['exported_at']}
-- Version: {$metadata['version']}
-- Total Fragments: {$stats['fragments']}
-- Total Records: {$stats['total_records']}

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
        
        $sql = ["-- Fragment exports ({$fragments->count()} records)"];
        
        foreach ($fragments as $fragment) {
            $sql[] = $this->generateFragmentInsert($fragment);
        }
        
        return implode("\n", $sql);
    }
    
    private function generateFragmentInsert(Fragment $fragment): string
    {
        $values = [
            'id' => $fragment->id,
            'type' => $this->escapeString($fragment->type),
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
    
    private function generateExportFooter(DemoDataCollection $data): string
    {
        return "-- Verification queries
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

-- Export completed successfully";
    }
    
    private function escapeString(?string $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        
        return "'" . str_replace("'", "''", $value) . "'";
    }
    
    private function escapeJson(array $data): string
    {
        return "'" . str_replace("'", "''", json_encode($data)) . "'";
    }
}
```

### Phase 2: Export Management & Versioning (1-2h)

#### 2.1 Export Version Manager
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
        $this->updateExportManifest($scenarioName, $version, $exportPath, $sqlContent);
        
        return $exportPath;
    }
    
    public function listExports(string $scenarioName = null): array
    {
        $manifestPath = "{$this->exportBasePath}/metadata/export_manifest.json";
        
        if (!file_exists($manifestPath)) {
            return [];
        }
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if ($scenarioName) {
            return $manifest['scenarios'][$scenarioName] ?? [];
        }
        
        return $manifest['scenarios'] ?? [];
    }
    
    public function getLatestExportPath(string $scenarioName): ?string
    {
        $latestPath = "{$this->exportBasePath}/{$scenarioName}/latest.sql";
        
        if (file_exists($latestPath) && is_link($latestPath)) {
            $realPath = readlink($latestPath);
            return dirname($latestPath) . '/' . $realPath;
        }
        
        return null;
    }
    
    private function generateVersion(string $scenarioName): string
    {
        $existingVersions = $this->getExistingVersions($scenarioName);
        $nextVersionNumber = count($existingVersions) + 1;
        $date = now()->format('Y-m-d');
        
        return "v{$nextVersionNumber}_{$date}";
    }
    
    private function getExistingVersions(string $scenarioName): array
    {
        $scenarioDir = "{$this->exportBasePath}/{$scenarioName}";
        
        if (!is_dir($scenarioDir)) {
            return [];
        }
        
        $files = glob("{$scenarioDir}/{$scenarioName}_v*.sql");
        
        return array_map(function ($file) use ($scenarioName) {
            $basename = basename($file, '.sql');
            return str_replace("{$scenarioName}_", '', $basename);
        }, $files);
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
    
    private function updateExportManifest(string $scenarioName, string $version, string $exportPath, string $sqlContent): void
    {
        $manifestPath = "{$this->exportBasePath}/metadata/export_manifest.json";
        
        $manifest = file_exists($manifestPath) 
            ? json_decode(file_get_contents($manifestPath), true) 
            : ['scenarios' => []];
        
        // Parse statistics from SQL content
        $stats = $this->parseExportStatistics($sqlContent);
        
        $manifest['scenarios'][$scenarioName]['versions'][] = [
            'version' => $version,
            'file' => $exportPath,
            'created_at' => now()->toISOString(),
            'size' => filesize($exportPath),
            'statistics' => $stats,
        ];
        
        $manifest['scenarios'][$scenarioName]['latest'] = $version;
        $manifest['last_updated'] = now()->toISOString();
        
        $this->ensureDirectoryExists(dirname($manifestPath));
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }
}
```

#### 2.2 Main Export Service
```php
// app/Services/Demo/Export/DemoDataExportService.php
class DemoDataExportService
{
    public function __construct(
        private DemoDataCollector $collector,
        private SqlExportGenerator $generator,
        private ExportVersionManager $versionManager
    ) {}
    
    public function exportScenario(string $scenarioName): ExportResult
    {
        // Collect demo data
        $demoData = $this->collector->collectDemoData($scenarioName);
        
        if ($demoData->getTotalRecordCount() === 0) {
            throw new ExportException("No demo data found for scenario: {$scenarioName}");
        }
        
        // Generate SQL export
        $sqlExport = $this->generator->generateSqlExport($demoData);
        
        // Save export with versioning
        $exportPath = $this->versionManager->saveExport($scenarioName, $sqlExport);
        
        return new ExportResult($exportPath, $demoData->getStatistics());
    }
    
    public function listExports(string $scenarioName = null): array
    {
        return $this->versionManager->listExports($scenarioName);
    }
    
    public function getLatestExportPath(string $scenarioName): ?string
    {
        return $this->versionManager->getLatestExportPath($scenarioName);
    }
}
```

### Phase 3: Import System (1-2h)

#### 3.1 Import Service
```php
// app/Services/Demo/Export/DemoDataImportService.php
class DemoDataImportService
{
    public function importFromFile(string $exportFile): ImportResult
    {
        $this->validateExportFile($exportFile);
        
        $statistics = DB::transaction(function () use ($exportFile) {
            $this->executeSqlImport($exportFile);
            return $this->getImportStatistics();
        });
        
        return new ImportResult($statistics);
    }
    
    public function importLatestScenario(string $scenarioName): ImportResult
    {
        $latestFile = app(ExportVersionManager::class)->getLatestExportPath($scenarioName);
        
        if (!$latestFile) {
            throw new ExportNotFoundException("No exports found for scenario: {$scenarioName}");
        }
        
        return $this->importFromFile($latestFile);
    }
    
    public function importScenarioVersion(string $scenarioName, string $version): ImportResult
    {
        $exportPath = database_path("demo-snapshots/{$scenarioName}/{$scenarioName}_{$version}.sql");
        
        if (!file_exists($exportPath)) {
            throw new ExportNotFoundException("Export not found: {$exportPath}");
        }
        
        return $this->importFromFile($exportPath);
    }
    
    private function validateExportFile(string $exportFile): void
    {
        if (!file_exists($exportFile)) {
            throw new ExportNotFoundException("Export file not found: {$exportFile}");
        }
        
        if (!is_readable($exportFile)) {
            throw new ImportException("Export file is not readable: {$exportFile}");
        }
        
        // Basic content validation
        $content = file_get_contents($exportFile);
        if (!str_contains($content, '-- Demo Data Export')) {
            throw new ImportException("Invalid export file format: {$exportFile}");
        }
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
            try {
                DB::unprepared(trim($statement) . ';');
            } catch (Exception $e) {
                throw new ImportException("Failed to execute SQL statement: {$e->getMessage()}");
            }
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

#### 3.2 Result Models
```php
// app/Services/Demo/Export/Models/ExportResult.php
class ExportResult
{
    public function __construct(
        private string $exportPath,
        private array $statistics
    ) {}
    
    public function getExportPath(): string
    {
        return $this->exportPath;
    }
    
    public function getStatistics(): array
    {
        return $this->statistics;
    }
    
    public function getFileSize(): int
    {
        return filesize($this->exportPath);
    }
}

// app/Services/Demo/Export/Models/ImportResult.php
class ImportResult
{
    public function __construct(
        private array $statistics
    ) {}
    
    public function getStatistics(): array
    {
        return $this->statistics;
    }
    
    public function getTotalRecordsImported(): int
    {
        return array_sum($this->statistics);
    }
}
```

### Phase 4: Console Commands & Integration (30min-1h)

#### 4.1 Export Command
```php
// app/Console/Commands/Demo/ExportScenarioCommand.php
class ExportScenarioCommand extends Command
{
    protected $signature = 'demo:export {scenario} {--auto-version}';
    
    protected $description = 'Export demo data for a scenario to SQL file';
    
    public function handle(DemoDataExportService $exportService): int
    {
        $scenario = $this->argument('scenario');
        
        try {
            $this->info("Exporting demo data for scenario: {$scenario}");
            
            $result = $exportService->exportScenario($scenario);
            
            $this->info("Export completed successfully!");
            $this->line("File: {$result->getExportPath()}");
            $this->line("Size: " . $this->formatFileSize($result->getFileSize()));
            
            $this->table(
                ['Type', 'Count'],
                collect($result->getStatistics())->map(fn($count, $type) => [$type, $count])
            );
            
            return 0;
        } catch (Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            return 1;
        }
    }
    
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }
}
```

#### 4.2 Import Command
```php
// app/Console/Commands/Demo/ImportScenarioCommand.php
class ImportScenarioCommand extends Command
{
    protected $signature = 'demo:import {scenario} {--version=} {--latest} {--file=}';
    
    protected $description = 'Import demo data from exported SQL file';
    
    public function handle(DemoDataImportService $importService): int
    {
        $scenario = $this->argument('scenario');
        $version = $this->option('version');
        $latest = $this->option('latest');
        $file = $this->option('file');
        
        try {
            if ($file) {
                $result = $importService->importFromFile($file);
            } elseif ($latest || !$version) {
                $this->info("Importing latest version of scenario: {$scenario}");
                $result = $importService->importLatestScenario($scenario);
            } else {
                $this->info("Importing version {$version} of scenario: {$scenario}");
                $result = $importService->importScenarioVersion($scenario, $version);
            }
            
            $this->info("Import completed successfully!");
            $this->line("Total records imported: {$result->getTotalRecordsImported()}");
            
            $this->table(
                ['Type', 'Count'],
                collect($result->getStatistics())->map(fn($count, $type) => [$type, $count])
            );
            
            return 0;
        } catch (Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return 1;
        }
    }
}
```

#### 4.3 List Exports Command
```php
// app/Console/Commands/Demo/ListExportsCommand.php
class ListExportsCommand extends Command
{
    protected $signature = 'demo:list-exports {scenario?}';
    
    protected $description = 'List available demo data exports';
    
    public function handle(DemoDataExportService $exportService): int
    {
        $scenario = $this->argument('scenario');
        $exports = $exportService->listExports($scenario);
        
        if (empty($exports)) {
            $message = $scenario ? "No exports found for scenario: {$scenario}" : "No exports found";
            $this->warn($message);
            return 0;
        }
        
        if ($scenario) {
            $this->displayScenarioExports($scenario, $exports);
        } else {
            $this->displayAllExports($exports);
        }
        
        return 0;
    }
    
    private function displayScenarioExports(string $scenario, array $exports): void
    {
        $this->info("Exports for scenario: {$scenario}");
        $this->line("Latest version: {$exports['latest']}");
        
        $rows = collect($exports['versions'])->map(function ($version) {
            return [
                $version['version'],
                date('Y-m-d H:i', strtotime($version['created_at'])),
                $this->formatFileSize($version['size']),
                $version['statistics']['fragments'] ?? 0,
                $version['statistics']['total_records'] ?? 0,
            ];
        });
        
        $this->table(
            ['Version', 'Created', 'Size', 'Fragments', 'Total Records'],
            $rows
        );
    }
}
```

## Testing Strategy

### Unit Tests
```php
// tests/Unit/Services/Demo/Export/DemoDataCollectorTest.php
test('collects all demo data types correctly')
test('filters demo data by metadata flag')
test('includes related models in collection')

// tests/Unit/Services/Demo/Export/SqlExportGeneratorTest.php
test('generates valid SQL export')
test('includes cleanup statements')
test('handles empty data collections')
test('escapes SQL strings correctly')
```

### Integration Tests
```php
// tests/Feature/Demo/Export/ExportImportIntegrationTest.php
test('exports and imports scenario successfully')
test('maintains data integrity through export/import cycle')
test('handles large datasets efficiently')
```

## Quality Assurance

### Code Quality
- [ ] PSR-12 compliance with Pint
- [ ] Type declarations for all methods
- [ ] Comprehensive error handling
- [ ] Transaction safety for imports

### Export Quality
- [ ] Complete data capture without loss
- [ ] Referential integrity maintained
- [ ] SQL syntax validity
- [ ] Cross-platform compatibility

### Performance Requirements
- [ ] Export generation in <60 seconds
- [ ] Import execution in <30 seconds
- [ ] Efficient memory usage for large datasets
- [ ] File size optimization

## Delivery Checklist

### Core Export System
- [ ] `DemoDataCollector` with comprehensive data collection
- [ ] `SqlExportGenerator` with optimized SQL generation
- [ ] `DemoDataCollection` model for data organization
- [ ] Export validation and error handling

### Versioning & Management
- [ ] `ExportVersionManager` with automatic versioning
- [ ] Export manifest tracking and metadata
- [ ] Latest export symlink management
- [ ] Directory structure management

### Import System
- [ ] `DemoDataImportService` with transaction safety
- [ ] SQL parsing and execution
- [ ] Import validation and verification
- [ ] Error recovery and rollback

### Console Interface
- [ ] Export generation command (`demo:export`)
- [ ] Import command with version options (`demo:import`)
- [ ] Export listing command (`demo:list-exports`)
- [ ] Service provider registration

## Success Validation

### Functional Testing
```bash
# Test export generation
php artisan demo:export general
php artisan demo:export writer

# Test import functionality  
php artisan demo:import general --latest
php artisan demo:import writer --version=v1_2025-01-15

# Test version management
php artisan demo:list-exports
php artisan demo:list-exports general

# Test export/import cycle
php artisan demo:seed-enhanced --scenario=general
php artisan demo:export general
php artisan migrate:fresh
php artisan demo:import general --latest
```

### Quality Gates
- [ ] Exported SQL imports without errors
- [ ] All demo data preserved accurately  
- [ ] Referential integrity maintained
- [ ] Version tracking works correctly
- [ ] File sizes reasonable (<5MB for typical scenarios)

This export and versioning system provides the crucial capability to preserve and share high-quality AI-generated demo data, eliminating the need for expensive regeneration while ensuring consistent demo experiences across environments and team members.