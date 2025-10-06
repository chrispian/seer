# SEEDER-001: YAML Configuration System — TODO

## Implementation Checklist

### Phase 1: Core Configuration Infrastructure ⏱️ 3-4h

#### Service Architecture Setup
- [ ] Create `app/Services/Demo/DemoScenarioConfigService.php`
  - [ ] `loadScenario(string $name): DemoScenarioConfig` method
  - [ ] `validateSchema(array $config): ValidationResult` method
  - [ ] `listAvailableScenarios(): Collection` method
  - [ ] `getDefaultScenario(): DemoScenarioConfig` method
  - [ ] Error handling for missing files and invalid YAML
  - [ ] Storage integration with Laravel filesystem

#### Value Objects Creation
- [ ] Create `app/Services/Demo/Config/DemoScenarioConfig.php`
  - [ ] `getScenarioInfo(): ScenarioInfo` method
  - [ ] `getTimeframe(): TimeframeConfig` method
  - [ ] `getVaults(): Collection<VaultConfig>` method
  - [ ] `getProjects(): Collection<ProjectConfig>` method
  - [ ] `getContentGeneration(): ContentGenerationConfig` method
  - [ ] `getFragmentRelationships(): RelationshipConfig` method
  - [ ] `getTaggingStrategy(): TaggingConfig` method
  - [ ] `getAiGenerationRules(): Collection<string>` method

- [ ] Create `app/Services/Demo/Config/ScenarioInfo.php`
  - [ ] Constructor with `name`, `description`, `persona` properties
  - [ ] Readonly properties for immutability

- [ ] Create `app/Services/Demo/Config/TimeframeConfig.php`
  - [ ] Constructor with `startDate`, `endDate`, `activityPattern` properties
  - [ ] `getStartDate(): Carbon` method (parse relative dates like "-90d")
  - [ ] `getEndDate(): Carbon` method (parse relative dates like "+30d")
  - [ ] `getActivityPattern(): ActivityPattern` enum conversion

- [ ] Create `app/Services/Demo/Config/VaultConfig.php`
  - [ ] Constructor with `name`, `slug`, `description`, `metadata` properties
  - [ ] Validation for slug format (alpha_dash)

- [ ] Create additional config value objects:
  - [ ] `ProjectConfig.php` - project definitions with status
  - [ ] `ContentGenerationConfig.php` - content generation settings
  - [ ] `RelationshipConfig.php` - fragment relationship configuration
  - [ ] `TaggingConfig.php` - tagging strategy configuration

#### Storage Integration
- [ ] Create storage directory structure
  - [ ] `mkdir -p storage/app/demo-scenarios`
  - [ ] `mkdir -p storage/app/demo-scenarios/schemas`

- [ ] Extend `config/fragments.php` with demo seeder configuration
  - [ ] Add `demo_seeder` configuration section
  - [ ] Storage path configuration
  - [ ] Default scenario setting
  - [ ] Cache TTL configuration
  - [ ] AI generation settings

### Phase 2: YAML Processing & Validation ⏱️ 3-4h

#### YAML Parser Integration
- [ ] Add Symfony YAML dependency (likely already available)
- [ ] Implement YAML file loading in `DemoScenarioConfigService`
  - [ ] File existence checking
  - [ ] YAML parsing with error handling
  - [ ] ParseException handling with context

#### Schema Validation System
- [ ] Create `app/Services/Demo/Validation/DemoScenarioValidator.php`
  - [ ] Define Laravel validation rules for all schema sections
  - [ ] Implement `validate(array $config): ValidationResult` method
  - [ ] Cross-reference validation (vault slugs in projects)
  - [ ] Date format validation (relative date expressions)
  - [ ] Enum validation for activity patterns, statuses

- [ ] Create `app/Services/Demo/Validation/ValidationResult.php`
  - [ ] Constructor with `passed`, `errors`, `warnings` properties
  - [ ] `getErrorSummary(): string` method
  - [ ] `getDetailedErrors(): array` method
  - [ ] Static factory methods `passed()` and `failed()`

#### Exception Classes
- [ ] Create `app/Services/Demo/Exceptions/ScenarioNotFoundException.php`
- [ ] Create `app/Services/Demo/Exceptions/ScenarioParseException.php`
- [ ] Create `app/Services/Demo/Exceptions/ScenarioValidationException.php`

#### Custom Validation Logic
- [ ] Implement cross-reference validation
  - [ ] Verify project vault references exist in vaults section
  - [ ] Check content generation vault references
- [ ] Implement date format validation
  - [ ] Parse relative date expressions (±Nd, ±Nw, ±Nm, ±Ny)
  - [ ] Validate start_date < end_date after parsing
- [ ] Implement content validation
  - [ ] Validate realistic examples format
  - [ ] Check contact structure consistency

### Phase 3: Example Scenarios Creation ⏱️ 2-3h

#### General Demo Scenario
- [ ] Create `storage/app/demo-scenarios/general.yaml`
  - [ ] Complete scenario metadata (name, description, persona)
  - [ ] Timeframe configuration (-90d to +30d, business_hours_weighted)
  - [ ] Vault definitions (Personal, Work)
  - [ ] Project definitions (Home Lab Setup, Moving, POS Upgrade, Salesforce)
  - [ ] Content generation configuration:
    - [ ] 50 realistic todo examples (personal and work)
    - [ ] 25 chat sessions with 5 messages each
    - [ ] 25 contacts with meeting notes
    - [ ] 12 reminders of various types
  - [ ] Fragment relationship configuration (25% linking)
  - [ ] Tagging strategy (40% contact tags, auto tags enabled)
  - [ ] AI generation rules (6 specific guidelines)

#### Specialized Scenarios
- [ ] Create `storage/app/demo-scenarios/writer.yaml`
  - [ ] Content creator persona and projects
  - [ ] Writing-focused todos (deadlines, research, client work)
  - [ ] Editor and client contacts
  - [ ] Publishing and content calendar focused

- [ ] Create `storage/app/demo-scenarios/developer.yaml`
  - [ ] Full-stack developer persona
  - [ ] Programming projects (refactor, documentation, optimization)
  - [ ] Technical todos (bug reports, code reviews, architecture)
  - [ ] Development team contacts

- [ ] Create `storage/app/demo-scenarios/productivity.yaml`
  - [ ] Task management focused persona
  - [ ] Organization and productivity projects
  - [ ] GTD-style todos and workflows
  - [ ] Personal and professional productivity contacts

#### Schema Documentation
- [ ] Create `storage/app/demo-scenarios/schemas/demo-scenario-schema.json`
  - [ ] JSON Schema definition for validation
  - [ ] Documentation of all required fields
  - [ ] Examples for each section

### Phase 4: Laravel Integration ⏱️ 1-2h

#### Service Provider Registration
- [ ] Create `app/Providers/DemoSeederServiceProvider.php`
  - [ ] Register `DemoScenarioConfigService` as singleton
  - [ ] Bind service to container alias `demo.scenario.config`
  - [ ] Publish demo scenarios in local/testing environments
  - [ ] Register service provider in `config/app.php`

#### Console Commands
- [ ] Create `app/Console/Commands/Demo/ValidateScenarioCommand.php`
  - [ ] Command signature: `demo:validate-scenario {scenario}`
  - [ ] Load and validate specified scenario
  - [ ] Display validation results with colors
  - [ ] Exit codes for success/failure

- [ ] Create `app/Console/Commands/Demo/ListScenariosCommand.php`
  - [ ] Command signature: `demo:list-scenarios`
  - [ ] List all available scenarios
  - [ ] Show scenario descriptions
  - [ ] Indicate default scenario

- [ ] Create `app/Console/Commands/Demo/CreateScenarioCommand.php`
  - [ ] Command signature: `demo:create-scenario {name} {--template=general}`
  - [ ] Copy template scenario to new file
  - [ ] Open editor for customization
  - [ ] Validate new scenario after creation

#### Configuration Caching
- [ ] Implement configuration caching in `DemoScenarioConfigService`
- [ ] Cache key generation based on file modification time
- [ ] Cache invalidation on scenario file changes
- [ ] Integration with Laravel's cache system

### Testing & Quality Assurance ⏱️ 1-2h

#### Unit Tests
- [ ] Create `tests/Unit/Services/Demo/DemoScenarioConfigServiceTest.php`
  - [ ] Test scenario loading success cases
  - [ ] Test exception handling for missing scenarios
  - [ ] Test YAML parsing error handling
  - [ ] Test validation integration

- [ ] Create `tests/Unit/Services/Demo/Config/DemoScenarioConfigTest.php`
  - [ ] Test typed access methods
  - [ ] Test configuration section retrieval
  - [ ] Test value object creation

- [ ] Create `tests/Unit/Services/Demo/Validation/DemoScenarioValidatorTest.php`
  - [ ] Test schema validation rules
  - [ ] Test cross-reference validation
  - [ ] Test date format validation
  - [ ] Test error message generation

#### Integration Tests
- [ ] Create `tests/Feature/Demo/ScenarioLoadingTest.php`
  - [ ] Test complete scenario loading flow
  - [ ] Test Laravel storage integration
  - [ ] Test service container resolution

#### Example Validation Tests
- [ ] Test all shipped scenarios validate successfully
- [ ] Test scenario consistency (vault/project references)
- [ ] Test realistic examples format
- [ ] Test configuration completeness

#### Documentation
- [ ] Create schema documentation with examples
- [ ] Document scenario creation process
- [ ] Create troubleshooting guide for common errors
- [ ] Document integration points for other task packs

## Acceptance Criteria

### Functional Requirements
- [ ] Load YAML scenarios from storage without errors
- [ ] Validate schema with clear error messages
- [ ] Support multiple concurrent scenarios
- [ ] Provide typed access to all configuration sections
- [ ] Handle missing files and invalid YAML gracefully

### Integration Requirements
- [ ] Clean integration with Laravel storage system
- [ ] Service container registration works correctly
- [ ] Configuration extends `fragments.php` appropriately
- [ ] Console commands function properly

### Quality Requirements
- [ ] Comprehensive test coverage (>90%)
- [ ] PSR-12 code compliance
- [ ] Clear documentation and examples
- [ ] Performance acceptable for demo data volumes

### Example Scenarios
- [ ] General scenario covers mixed personal/professional use
- [ ] Writer scenario focuses on content creation workflows
- [ ] Developer scenario emphasizes technical project management
- [ ] Productivity scenario showcases task management features

## Success Validation Commands

```bash
# Test scenario loading
php artisan demo:list-scenarios
php artisan demo:validate-scenario general
php artisan demo:validate-scenario writer

# Test configuration access
php artisan tinker --execute="
\$service = app('App\Services\Demo\DemoScenarioConfigService');
\$config = \$service->loadScenario('general');
echo 'Vaults: ' . \$config->getVaults()->count();
echo 'Projects: ' . \$config->getProjects()->count();
echo 'Todo examples: ' . \$config->getContentGeneration()->getTodos()->getRealisticExamples()->count();
"

# Validate all scenarios
php artisan demo:validate-scenario general
php artisan demo:validate-scenario writer  
php artisan demo:validate-scenario developer
php artisan demo:validate-scenario productivity
```

## Notes & Considerations

### Performance Optimization
- Configuration caching will be essential for production use
- Lazy loading of configuration sections reduces memory usage
- YAML parsing should be optimized for large scenario files

### Future Extensibility
- Schema should support future content types (notes, documents, etc.)
- Validation system should be extensible for custom rules
- Configuration format should allow for backward compatibility

### Error Handling Strategy
- Clear, actionable error messages for schema violations
- Helpful context for YAML parsing errors
- Graceful degradation for missing or invalid scenarios

This comprehensive TODO provides a clear roadmap for implementing the YAML configuration system foundation that will enable sophisticated AI-powered demo data generation in subsequent task packs.