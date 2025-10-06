# SEEDER-005: Export & Versioning System — TODO

## Implementation Checklist

### Phase 1: Core Export System ⏱️ 2-3h

#### Demo Data Collection
- [ ] Create `app/Services/Demo/Export/DemoDataCollector.php`
  - [ ] `collectDemoData(string $scenarioName): DemoDataCollection` method
  - [ ] `collectDemoFragments(): Collection` method with relations
  - [ ] `collectDemoTodos(): Collection` method
  - [ ] `collectDemoContacts(): Collection` method
  - [ ] `collectDemoChatSessions(): Collection` method
  - [ ] `collectDemoVaults(): Collection` method
  - [ ] `collectDemoProjects(): Collection` method
  - [ ] `collectExportMetadata(string $scenarioName): array` method
  - [ ] Proper ordering for consistent exports

#### Data Collection Model
- [ ] Create `app/Services/Demo/Export/Models/DemoDataCollection.php`
  - [ ] Constructor with data array
  - [ ] Getter methods for all data types (getFragments, getTodos, etc.)
  - [ ] `getMetadata(): array` method
  - [ ] `getTotalRecordCount(): int` method
  - [ ] `getStatistics(): array` method with counts per type
  - [ ] Data validation and consistency checks

#### SQL Export Generator
- [ ] Create `app/Services/Demo/Export/SqlExportGenerator.php`
  - [ ] `generateSqlExport(DemoDataCollection $data): string` method
  - [ ] `generateExportHeader(DemoDataCollection $data): string` method
  - [ ] `generateVaultExports(Collection $vaults): string` method
  - [ ] `generateProjectExports(Collection $projects): string` method
  - [ ] `generateFragmentExports(Collection $fragments): string` method
  - [ ] `generateTodoExports(Collection $todos): string` method
  - [ ] `generateContactExports(Collection $contacts): string` method
  - [ ] `generateChatSessionExports(Collection $chatSessions): string` method
  - [ ] `generateExportFooter(DemoDataCollection $data): string` method
  - [ ] `escapeString(?string $value): string` method
  - [ ] `escapeJson(array $data): string` method

#### SQL Generation Implementation
- [ ] Implement export header with metadata and cleanup statements
  - [ ] Include scenario name, version, timestamp
  - [ ] Include statistics (fragment count, total records)
  - [ ] Generate cleanup SQL for existing demo data
  - [ ] Set FOREIGN_KEY_CHECKS = 0 for import safety

- [ ] Implement table export generation in dependency order
  - [ ] Vaults first (no dependencies)
  - [ ] Projects second (depend on vaults)
  - [ ] Fragments third (depend on vaults, projects)
  - [ ] Todos fourth (depend on fragments)
  - [ ] Contacts fifth (independent)
  - [ ] Chat sessions sixth (independent)

- [ ] Implement individual table INSERT generation
  - [ ] Handle NULL values correctly
  - [ ] Escape strings and JSON data properly
  - [ ] Include all required columns
  - [ ] Maintain referential integrity

- [ ] Implement export footer with verification
  - [ ] Generate verification count queries
  - [ ] Re-enable FOREIGN_KEY_CHECKS
  - [ ] Include completion marker

### Phase 2: Export Management & Versioning ⏱️ 1-2h

#### Export Version Manager
- [ ] Create `app/Services/Demo/Export/ExportVersionManager.php`
  - [ ] Constructor with export base path (`database/demo-snapshots`)
  - [ ] `saveExport(string $scenarioName, string $sqlContent): string` method
  - [ ] `listExports(string $scenarioName = null): array` method
  - [ ] `getLatestExportPath(string $scenarioName): ?string` method
  - [ ] `generateVersion(string $scenarioName): string` method
  - [ ] `getExistingVersions(string $scenarioName): array` method
  - [ ] `getExportPath(string $scenarioName, string $version): string` method
  - [ ] `updateLatestSymlink(string $scenarioName, string $exportPath): void` method
  - [ ] `updateExportManifest(string $scenarioName, string $version, string $exportPath, string $sqlContent): void` method
  - [ ] `ensureDirectoryExists(string $path): void` method

#### Version Generation Logic
- [ ] Implement automatic version numbering
  - [ ] Scan existing versions for scenario
  - [ ] Generate next version number (v1, v2, v3, etc.)
  - [ ] Include date in version string (v1_2025-01-15)
  - [ ] Ensure version uniqueness

#### Directory Structure Management
- [ ] Create directory structure automatically:
  - [ ] `database/demo-snapshots/{scenario}/` for each scenario
  - [ ] `database/demo-snapshots/metadata/` for manifest files
  - [ ] Proper permissions for created directories

#### Symlink Management
- [ ] Implement latest export symlink creation
  - [ ] Remove existing `latest.sql` symlink
  - [ ] Create new symlink pointing to latest export
  - [ ] Handle symlink failures gracefully
  - [ ] Verify symlink functionality across platforms

#### Export Manifest Management
- [ ] Implement export manifest tracking
  - [ ] JSON format for easy parsing
  - [ ] Track all versions per scenario
  - [ ] Include metadata (size, timestamp, statistics)
  - [ ] Maintain latest version pointer
  - [ ] Handle manifest corruption gracefully

#### Main Export Service
- [ ] Create `app/Services/Demo/Export/DemoDataExportService.php`
  - [ ] Constructor with injected dependencies (collector, generator, version manager)
  - [ ] `exportScenario(string $scenarioName): ExportResult` method
  - [ ] `listExports(string $scenarioName = null): array` method
  - [ ] `getLatestExportPath(string $scenarioName): ?string` method
  - [ ] Error handling for empty datasets
  - [ ] Comprehensive logging

### Phase 3: Import System ⏱️ 1-2h

#### Import Service
- [ ] Create `app/Services/Demo/Export/DemoDataImportService.php`
  - [ ] `importFromFile(string $exportFile): ImportResult` method
  - [ ] `importLatestScenario(string $scenarioName): ImportResult` method
  - [ ] `importScenarioVersion(string $scenarioName, string $version): ImportResult` method
  - [ ] `validateExportFile(string $exportFile): void` method
  - [ ] `executeSqlImport(string $exportFile): void` method
  - [ ] `getImportStatistics(): array` method

#### Import Validation
- [ ] Implement export file validation
  - [ ] Check file existence and readability
  - [ ] Validate file format (check for export header)
  - [ ] Verify SQL syntax basic structure
  - [ ] Check for required sections

#### SQL Import Execution
- [ ] Implement safe SQL import execution
  - [ ] Parse SQL into individual statements
  - [ ] Filter out comments and empty statements
  - [ ] Execute statements in transaction
  - [ ] Handle SQL execution errors
  - [ ] Rollback on failure

#### Import Statistics Collection
- [ ] Implement post-import statistics collection
  - [ ] Count imported fragments by type
  - [ ] Count related model records
  - [ ] Verify expected record counts
  - [ ] Report import success metrics

#### Result Models
- [ ] Create `app/Services/Demo/Export/Models/ExportResult.php`
  - [ ] Constructor with export path and statistics
  - [ ] `getExportPath(): string` method
  - [ ] `getStatistics(): array` method
  - [ ] `getFileSize(): int` method

- [ ] Create `app/Services/Demo/Export/Models/ImportResult.php`
  - [ ] Constructor with statistics
  - [ ] `getStatistics(): array` method
  - [ ] `getTotalRecordsImported(): int` method

#### Exception Classes
- [ ] Create exception classes in `app/Services/Demo/Export/Exceptions/`
  - [ ] `ExportException.php` for export failures
  - [ ] `ImportException.php` for import failures
  - [ ] `ExportNotFoundException.php` for missing exports
  - [ ] Clear error messages and context

### Phase 4: Console Commands & Integration ⏱️ 30min-1h

#### Export Command
- [ ] Create `app/Console/Commands/Demo/ExportScenarioCommand.php`
  - [ ] Command signature: `demo:export {scenario} {--auto-version}`
  - [ ] Integration with `DemoDataExportService`
  - [ ] Progress reporting during export
  - [ ] Display export statistics table
  - [ ] File size formatting
  - [ ] Error handling and user-friendly messages

#### Import Command
- [ ] Create `app/Console/Commands/Demo/ImportScenarioCommand.php`
  - [ ] Command signature: `demo:import {scenario} {--version=} {--latest} {--file=}`
  - [ ] Support for latest version import (default)
  - [ ] Support for specific version import
  - [ ] Support for direct file import
  - [ ] Progress reporting during import
  - [ ] Display import statistics table
  - [ ] Confirmation prompts for destructive operations

#### List Exports Command
- [ ] Create `app/Console/Commands/Demo/ListExportsCommand.php`
  - [ ] Command signature: `demo:list-exports {scenario?}`
  - [ ] List all scenarios if no scenario specified
  - [ ] List versions for specific scenario
  - [ ] Display export statistics (size, records, date)
  - [ ] Highlight latest version
  - [ ] Table formatting for readability

#### Additional Management Commands
- [ ] Create `app/Console/Commands/Demo/CleanupExportsCommand.php`
  - [ ] Command signature: `demo:cleanup-exports {scenario?} {--keep=5}`
  - [ ] Remove old export versions
  - [ ] Keep specified number of recent versions
  - [ ] Update manifest after cleanup
  - [ ] Confirmation prompts

#### Service Provider Registration
- [ ] Register export services in `app/Providers/DemoExportServiceProvider.php`
  - [ ] Bind export service interfaces
  - [ ] Register console commands
  - [ ] Set up configuration defaults

### Testing & Documentation ⏱️ 30min

#### Unit Tests
- [ ] Create `tests/Unit/Services/Demo/Export/DemoDataCollectorTest.php`
  - [ ] Test demo data collection from database
  - [ ] Test filtering by demo_seed metadata
  - [ ] Test inclusion of related models
  - [ ] Test metadata generation

- [ ] Create `tests/Unit/Services/Demo/Export/SqlExportGeneratorTest.php`
  - [ ] Test SQL export generation
  - [ ] Test string escaping functions
  - [ ] Test JSON escaping functions
  - [ ] Test empty data handling

- [ ] Create `tests/Unit/Services/Demo/Export/ExportVersionManagerTest.php`
  - [ ] Test version generation
  - [ ] Test directory creation
  - [ ] Test symlink management
  - [ ] Test manifest updates

#### Integration Tests
- [ ] Create `tests/Feature/Demo/Export/ExportImportIntegrationTest.php`
  - [ ] Test complete export/import cycle
  - [ ] Test data integrity preservation
  - [ ] Test version management
  - [ ] Test console command integration

#### Console Command Tests
- [ ] Create `tests/Feature/Demo/Export/ExportConsoleCommandsTest.php`
  - [ ] Test export command functionality
  - [ ] Test import command with different options
  - [ ] Test list exports command
  - [ ] Test command error handling

#### Documentation
- [ ] Create export system documentation:
  - [ ] Overview of export/import workflow
  - [ ] Console command usage guide
  - [ ] File format specifications
  - [ ] Troubleshooting guide

## Acceptance Criteria

### Functional Requirements
- [ ] Generate complete SQL exports for any demo scenario
- [ ] Version exports with scenario name and timestamp
- [ ] Enable quick demo environment setup without AI generation
- [ ] Support multiple scenarios and version management
- [ ] Maintain export integrity and data consistency

### Export Quality Requirements
- [ ] All demo data captured without loss
- [ ] Referential integrity maintained across tables
- [ ] SQL syntax valid and cross-platform compatible
- [ ] File sizes optimized but human-readable
- [ ] Exports work with both SQLite and PostgreSQL

### Import Quality Requirements
- [ ] Imports execute without errors
- [ ] Data integrity preserved through export/import cycle
- [ ] Transaction safety with rollback on failures
- [ ] Performance acceptable for typical demo datasets
- [ ] Clear error messages for import failures

### Version Management Requirements
- [ ] Automatic version numbering works correctly
- [ ] Latest symlinks update properly
- [ ] Export manifest tracks all versions accurately
- [ ] Directory structure created automatically
- [ ] Old version cleanup functionality

## Success Validation Commands

```bash
# Test export generation for different scenarios
php artisan demo:export general
php artisan demo:export writer
php artisan demo:export developer

# Test import functionality with different options
php artisan demo:import general --latest
php artisan demo:import writer --version=v1_2025-01-15
php artisan demo:import developer --file=database/demo-snapshots/developer/developer_v1_2025-01-15.sql

# Test version management and listing
php artisan demo:list-exports
php artisan demo:list-exports general
php artisan demo:cleanup-exports general --keep=3

# Test complete export/import cycle
php artisan demo:seed-enhanced --scenario=general
php artisan demo:export general
php artisan migrate:fresh
php artisan demo:import general --latest

# Verify import integrity
php artisan tinker --execute="
\$fragments = App\Models\Fragment::where('metadata->demo_seed', true)->count();
\$todos = App\Models\Todo::whereHas('fragment', fn(\$q) => \$q->where('metadata->demo_seed', true))->count();
echo 'Imported - Fragments: ' . \$fragments . ', Todos: ' . \$todos;
"
```

## File Structure Validation

### Directory Structure
```
database/demo-snapshots/
├── general/
│   ├── general_v1_2025-01-15.sql
│   ├── general_v2_2025-01-20.sql
│   └── latest.sql → general_v2_2025-01-20.sql
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

### Export File Quality
**Valid SQL Export Structure:**
```sql
-- Demo Data Export
-- Scenario: general
-- Generated: 2025-01-15T10:30:00Z
-- Version: v1_2025-01-15
-- Total Fragments: 175
-- Total Records: 218

-- Cleanup existing demo data
DELETE FROM todos WHERE fragment_id IN (SELECT id FROM fragments WHERE metadata->>'demo_seed' = 'true');
-- ... (cleanup statements)

SET FOREIGN_KEY_CHECKS = 0;

-- Vault exports (2 records)
INSERT INTO vaults (id, name, slug, description, metadata, created_at, updated_at) VALUES
(1, 'Personal', 'personal', 'Personal life organization', '{"demo_seed": true, "scenario": "general"}', '2025-01-15 10:00:00', '2025-01-15 10:00:00');

-- Fragment exports (175 records)
INSERT INTO fragments (id, type, title, message, tags, relationships, metadata, state, vault, project_id, inbox_status, inbox_at, created_at, updated_at) VALUES
(1, 'todo', 'Pick up dry cleaning from Main Street cleaners', 'TODO: Pick up dry cleaning...', '["demo", "personal"]', '[]', '{"demo_seed": true, "demo_category": "todo"}', '{"status": "open"}', 'personal', 1, 'accepted', '2025-01-15 08:30:00', '2025-01-15 08:30:00', '2025-01-15 08:30:00');

SET FOREIGN_KEY_CHECKS = 1;

-- Verification queries
SELECT 'Fragments' as type, COUNT(*) as count FROM fragments WHERE metadata->>'demo_seed' = 'true';
-- ... (verification statements)
```

## Notes & Considerations

### Performance Optimization
- Use streaming for large exports to avoid memory issues
- Implement batch processing for large imports
- Consider compression for very large exports
- Optimize SQL generation for readability vs size

### Cross-Platform Compatibility
- Test symlinks on Windows, macOS, Linux
- Ensure SQL syntax works with both SQLite and PostgreSQL
- Handle file path differences across platforms
- Test console commands on different shells

### Error Recovery
- Implement comprehensive error handling for all failure modes
- Provide clear error messages with actionable guidance
- Support rollback and recovery for failed imports
- Validate data integrity after imports

### Future Extensibility
- Design for additional export formats (JSON, YAML)
- Support for incremental/delta exports
- Integration with backup and restore systems
- Support for remote export storage (S3, etc.)

This export and versioning system provides the crucial final piece of the AI-powered demo seeder system, enabling teams to preserve and share high-quality AI-generated demo data efficiently while maintaining consistency across environments.