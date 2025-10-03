# FE-01 Type System Agent Profile

## Mission
Implement file-based Type Packs with DB registry cache, JSON schema validation, and generated columns for Fragment performance optimization per Sprint 40 specifications.

## Workflow
- Start session with CLI commands:
  1. `git fetch origin` and `git pull --rebase origin main`
  2. `git checkout -b feature/fe-01-type-system`
- Use unified diffs for edits where possible
- Ask for direction if not sure about implementation details
- Do not commit until user approves that the feature is working
- Use sub-agents when possible for complex domain work:
  - Schema validation logic
  - Migration generation for indexes
  - Type Pack template scaffolding

## Quality Standards
- Type Packs cleanly integrate with existing Fragment model
- Schema validation is performant and provides clear error messages
- Generated columns and indexes significantly improve query performance
- Registry cache provides fast lookups while maintaining file-first authority
- Management commands follow Laravel conventions and existing patterns
- All code follows PSR-12 and uses constructor property promotion

## Deliverables
- `fragment_type_registry` migration and model
- Type Pack file structure: `fragments/types/{slug}/`
- Schema validation service integrated with Fragment model
- Generated columns migration for todo type (status, due_at)
- Artisan commands: `frag:type:make`, `frag:type:cache`, `frag:type:validate`
- Sample todo Type Pack with complete structure

## Technical Focus
- JSON Schema validation using existing JsonSchemaValidator service
- Generated columns for hot fields with proper types
- Partial indexes scoped by type for query optimization
- File precedence: storage/fragments/types > fragments/types > modules
- Integration with existing Fragment state field and type system

## Communication
- Provide concise updates on schema validation integration progress
- Report any conflicts with existing Fragment model behavior
- Include test results for performance improvements from generated columns
- Document Type Pack structure and usage patterns

## Safety Notes
- Preserve existing Fragment functionality and backward compatibility
- Do not modify existing Fragment state data during migration
- Ensure schema validation is opt-in and doesn't break existing workflows
- Test generated columns thoroughly before applying to production data
- Coordinate with existing type inference and classification systems

## Integration Points
- Fragment model state field and existing type relationships
- AIProviderManager for any AI-based type inference
- Existing JsonSchemaValidator service in app/Services/AI/
- Current fragment migration patterns and database structure
- Existing console command patterns in app/Console/Commands/

## Sub-Agent Specializations
- **Schema Validator Agent**: JSON schema validation integration and error handling
- **Migration Generator Agent**: Generated columns and partial index creation
- **Template Scaffolder Agent**: Type Pack file structure and template generation