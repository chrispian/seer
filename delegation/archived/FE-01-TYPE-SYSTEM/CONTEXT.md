# Type System Implementation Context

## Current Fragment System

### Fragment Model Structure
- **Location**: `app/Models/Fragment.php`
- **Key Fields**: `type` (string), `state` (JSONB), `metadata` (JSONB)
- **Existing Types**: todo, contact, link, file, calendar_event, meeting
- **State Field**: Flexible JSONB for type-specific data
- **Relationships**: Type, Category, ObjectType with HasOne relationships

### Existing Type Infrastructure
- Type model with value field (string-based types)
- Fragment scopes for todo queries (`scopeTodos`, `scopeOpenTodos`, `scopeCompletedTodos`)
- JSON casting with custom JsonCastingBuilder
- Existing validation patterns in the codebase

### Database Schema
```sql
-- fragments table (existing)
type VARCHAR(255)              -- Current type field  
state JSONB                   -- Target for schema validation
metadata JSONB                -- For enrichment data
type_id BIGINT               -- Relationship to types table
```

## Current Validation Patterns

### JSON Schema Validator
- **Location**: `app/Services/AI/JsonSchemaValidator.php`
- **Usage**: AI response validation
- **Pattern**: Validates JSON against schema, returns detailed errors

### Fragment Creation
- Fragment model uses `creating` boot method for defaults
- State field currently unvalidated but flexible
- Type inference happens in some contexts

## Target Type Pack Structure

Based on Sprint 40 specifications:
```
fragments/types/todo/
  type.yaml              # Manifest with metadata
  state.schema.json      # JSON schema for state validation
  indexes.yaml           # Generated columns and indexes
  recall.md             # Optional recall prompts
  prompts/              # AI prompts for classification
  views/                # Blade templates (future)
```

## Integration Requirements

### Fragment Model Integration
- Validate state field against Type Pack schema on create/update
- Maintain backward compatibility with existing fragments
- Preserve existing type relationships and scopes

### Configuration Integration
- Extend `config/fragments.php` with type system settings
- File precedence: storage > fragments > modules
- Registry cache for performance

### Command Integration
- Follow existing command patterns in `app/Console/Commands/`
- Use existing Artisan conventions
- Integration with current AI provider system

## Performance Considerations

### Generated Columns
- Extract hot fields from JSONB state to typed columns
- Enable efficient indexing and querying
- Target fields: `status`, `due_at`, `priority` for todos

### Registry Cache
- Database table for fast Type Pack lookups
- File hash verification for cache invalidation
- Avoid filesystem reads on every Fragment operation

## Key Files to Modify/Create

### New Files
- `app/Models/FragmentTypeRegistry.php` - Registry model
- `app/Services/TypePackLoader.php` - File loading service
- `app/Services/TypePackValidator.php` - Schema validation
- `app/Console/Commands/FragType*.php` - Management commands
- `database/migrations/*_create_fragment_type_registry_table.php`
- `fragments/types/todo/` - Sample Type Pack

### Modified Files
- `app/Models/Fragment.php` - Add validation hooks
- `config/fragments.php` - Type system configuration
- Existing Fragment migration for generated columns

## Dependencies

### Existing Services
- `JsonSchemaValidator` for schema validation logic
- `AIProviderManager` for potential AI integration
- Existing Fragment model and relationships

### External Packages
- Laravel's built-in JSON validation
- Existing composer dependencies (no new packages needed)

## Testing Strategy

### Unit Tests
- Type Pack loading and validation
- Schema validation error handling
- Registry cache operations

### Feature Tests  
- Fragment creation with schema validation
- Generated column performance improvements
- Management command functionality

### Integration Points
- Existing Fragment workflows
- AI provider integration
- Database query performance

## Migration Strategy

### Phase 1: Foundation
- Create registry table and model
- Implement Type Pack loader service
- Add basic schema validation

### Phase 2: Integration
- Hook validation into Fragment model
- Create todo Type Pack sample
- Add management commands

### Phase 3: Performance
- Add generated columns migration
- Create partial indexes
- Performance testing and optimization