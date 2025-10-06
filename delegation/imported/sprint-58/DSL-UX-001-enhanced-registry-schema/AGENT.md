# DSL-UX-001: Enhanced Registry Schema & Metadata

## Agent Role
Database schema architect focused on extending the command registry to support rich metadata for enhanced UX. Transform the current minimal registry into a comprehensive command metadata store.

## Objective
Extend the `command_registry` table with human-friendly fields and update the `CommandPackLoader` to persist manifest help data, enabling dynamic help systems and rich autocomplete experiences.

## Core Task
Enhance the command registry infrastructure to capture and store comprehensive metadata from command manifests, replacing static hardcoded descriptions with dynamic, source-of-truth data.

## Key Deliverables

### 1. Enhanced Database Schema
- **File**: `database/migrations/YYYY_MM_DD_HHMMSS_enhance_command_registry_metadata.php`
- **Action**: Add columns for name, category, summary, usage, examples, aliases, keywords
- **Schema Design**:
  ```sql
  name VARCHAR(255) NULL,
  category VARCHAR(100) NULL,
  summary TEXT NULL,
  usage TEXT NULL, 
  examples JSON NULL,
  aliases JSON NULL,
  keywords JSON NULL
  ```

### 2. Manifest Help Block Validation
- **File**: `app/Services/Commands/CommandPackLoader.php`
- **Method**: `updateRegistryCache()` enhancement
- **Action**: Parse and validate `help` block from `command.yaml` manifests
- **Validation**: Ensure required help fields are present and properly formatted

### 3. Metadata Persistence Logic
- **File**: `app/Services/Commands/CommandPackLoader.php`
- **Action**: Extract help metadata and persist to enhanced registry fields
- **Data Flow**: `command.yaml` → help block parsing → registry persistence

### 4. Registry Model Enhancement
- **File**: `app/Models/CommandRegistry.php`
- **Action**: Add fillable fields and accessor methods for new metadata columns
- **Methods**: `getAliases()`, `getExamples()`, `getKeywords()` for clean data access

## Technical Requirements

### Schema Migration Details:
```php
Schema::table('command_registry', function (Blueprint $table) {
    $table->string('name')->nullable()->after('slug');
    $table->string('category')->nullable()->after('name');
    $table->text('summary')->nullable()->after('category');
    $table->text('usage')->nullable()->after('summary');
    $table->json('examples')->nullable()->after('usage');
    $table->json('aliases')->nullable()->after('examples');
    $table->json('keywords')->nullable()->after('aliases');
    
    // Add indexes for search performance
    $table->index('category');
    $table->index('name');
});
```

### Help Block Specification:
```yaml
help:
  name: "Search Command"
  category: "search"
  summary: "Search through fragments and content"
  usage: "/search <query> [options]"
  examples:
    - "/search typescript"
    - "/search project:work urgent"
    - "/search #meeting last week"
  keywords: ["find", "lookup", "query", "discover"]
```

### Validation Rules:
- `name`: Required string, max 255 chars
- `category`: Optional, from predefined list (search, fragment, session, system, etc.)
- `summary`: Required for non-reserved commands, max 500 chars
- `usage`: Optional usage pattern with argument placeholders
- `examples`: Array of example invocations
- `keywords`: Array of searchable terms for discovery

## Current State Analysis

### Existing Registry Schema:
```sql
id, slug, version, source_path, steps_hash, 
capabilities, requires_secrets, reserved, timestamps
```

### Missing Metadata Fields:
- Human-readable names
- Categorization for grouping
- Rich descriptions for autocomplete
- Usage patterns for help display
- Example invocations for onboarding
- Searchable keywords for discovery
- Alias mappings for resolution

## Implementation Strategy

### Phase 1: Schema Extension
1. Create migration with new metadata columns
2. Ensure backward compatibility with existing registry entries
3. Add appropriate indexes for query performance

### Phase 2: Loader Enhancement
1. Extend `updateRegistryCache()` to parse help blocks
2. Add validation for help block structure
3. Implement graceful fallbacks for missing help data

### Phase 3: Model Updates
1. Update `CommandRegistry` model with new fillable fields
2. Add accessor methods for JSON field handling
3. Create helper methods for metadata queries

### Phase 4: Validation & Testing
1. Unit tests for help block parsing
2. Migration tests for schema changes
3. Integration tests for loader enhancements

## Success Criteria

### Database:
- [ ] Migration runs successfully on fresh and existing databases
- [ ] All new columns are properly indexed and nullable
- [ ] No existing functionality is broken by schema changes

### Loader:
- [ ] Help blocks are parsed and validated during pack loading
- [ ] Metadata is properly persisted to registry table
- [ ] Graceful handling of packs without help blocks

### Model:
- [ ] New fields are accessible through Eloquent model
- [ ] JSON fields are properly cast and accessible
- [ ] Helper methods provide clean data access

## Error Handling

### Validation Failures:
- Log warnings for invalid help blocks
- Continue loading with minimal metadata
- Provide clear error messages for pack authors

### Schema Migration:
- Handle existing data preservation
- Rollback capability for failed migrations
- Performance considerations for large registries

## Testing Requirements

### Unit Tests:
- Help block parsing with various YAML structures
- Validation logic for required/optional fields
- JSON field casting and accessor methods

### Integration Tests:
- Full pack loading with help metadata
- Registry cache updates with new fields
- Backward compatibility with existing packs

### Data Migration Tests:
- Fresh database installation
- Existing database upgrade path
- Rollback scenarios

This task establishes the foundation for rich command metadata that enables enhanced autocomplete, dynamic help systems, and improved command discoverability throughout the application.
