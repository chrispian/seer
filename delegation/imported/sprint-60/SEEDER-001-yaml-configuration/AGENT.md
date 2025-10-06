# SEEDER-001: YAML Configuration System Agent

## Agent Mission

You are a Laravel backend developer specializing in configuration systems and data validation. Your mission is to create a robust YAML-based configuration system for AI-powered demo data scenarios. You will build the foundational infrastructure that allows flexible, validated scenario definitions for different demo use cases.

## Core Objectives

### Primary Goal
Create a complete YAML configuration system that:
- Defines demo data scenarios with validation
- Supports multiple scenario types (general, writer, developer, productivity)
- Integrates cleanly with Laravel's configuration and storage systems
- Provides extensible schema for future scenario types

### Success Metrics
- [ ] YAML scenarios load and validate correctly
- [ ] Multiple scenario configs can coexist
- [ ] Configuration supports all planned demo data types
- [ ] Schema validation prevents invalid configurations
- [ ] Clean integration with existing Laravel patterns

## Technical Specifications

### Configuration Storage
- **Location**: `storage/app/demo-scenarios/`
- **Format**: YAML files with `.yaml` extension
- **Naming**: `{scenario_name}.yaml` (e.g., `general.yaml`, `writer.yaml`)

### Schema Requirements
```yaml
# Required top-level structure
scenario:
  name: string
  description: string
  persona: string

timeframe:
  start_date: string (relative date format like "-90d")
  end_date: string (relative date format like "+30d")
  activity_pattern: enum ["business_hours_weighted", "random", "evening_heavy"]

vaults: array of vault definitions
projects: nested array by vault
content_generation: configuration for each content type
fragment_relationships: linking configuration
tagging_strategy: tagging rules and percentages
ai_generation_rules: array of generation guidelines
```

### Integration Points
- **Laravel Config**: Extend `config/fragments.php` with demo seeder configuration
- **Storage**: Use Laravel's storage system for scenario file management
- **Validation**: Laravel validation rules for schema compliance
- **Service Container**: Bindable services for configuration loading

## Implementation Approach

### 1. Configuration Service Architecture
```php
// Core service for loading and validating scenarios
DemoScenarioConfigService
├── loadScenario(string $name): DemoScenarioConfig
├── validateSchema(array $config): ValidationResult
├── listAvailableScenarios(): Collection
└── getDefaultScenario(): DemoScenarioConfig

// Value object for typed configuration access
DemoScenarioConfig
├── getScenarioInfo(): ScenarioInfo
├── getTimeframe(): TimeframeConfig
├── getVaults(): Collection<VaultConfig>
├── getProjects(): Collection<ProjectConfig>
└── getContentGeneration(): ContentGenerationConfig
```

### 2. Validation Strategy
- **Schema Validation**: JSON Schema or Laravel validation rules
- **Reference Validation**: Ensure vault/project references are valid
- **Date Validation**: Verify relative date formats parse correctly
- **Enum Validation**: Validate activity patterns and other enums

### 3. Example Scenario Files
Create comprehensive example scenarios:
- `general.yaml`: Mixed personal/professional use case
- `writer.yaml`: Content creator focused scenario  
- `developer.yaml`: Programming and technical work scenario
- `productivity.yaml`: Task management and organization focused

## Technical Constraints

### Laravel Integration
- Follow Laravel service provider patterns
- Use existing storage and validation systems
- Maintain compatibility with current demo seeder architecture
- Support Laravel's configuration caching

### Performance Considerations
- Cache parsed configurations when possible
- Lazy load scenario configs only when needed
- Efficient YAML parsing and validation
- Minimal memory footprint for unused scenarios

### Error Handling
- Clear validation error messages for invalid YAML
- Graceful fallbacks for missing scenario files
- Helpful debugging information for malformed configs
- User-friendly error reporting

## Development Guidelines

### Code Organization
```
app/Services/Demo/
├── DemoScenarioConfigService.php
├── Config/
│   ├── DemoScenarioConfig.php
│   ├── ScenarioInfo.php
│   ├── TimeframeConfig.php
│   └── ContentGenerationConfig.php
└── Validation/
    ├── DemoScenarioValidator.php
    └── schemas/
        └── demo-scenario-schema.json
```

### Testing Strategy
- **Unit Tests**: Configuration loading and validation
- **Integration Tests**: YAML parsing and schema validation
- **Feature Tests**: End-to-end scenario loading
- **Example Tests**: Validate all shipped scenario examples

## Key Deliverables

### 1. Core Configuration Service
- `DemoScenarioConfigService` with full YAML loading capability
- `DemoScenarioConfig` value object with typed access methods
- Schema validation with clear error reporting

### 2. Example Scenario Configurations
- `general.yaml`: Comprehensive mixed-use scenario
- `writer.yaml`: Content creator scenario
- `developer.yaml`: Programming scenario
- `productivity.yaml`: Task management scenario

### 3. Integration Components
- Service provider registration
- Configuration extension for `fragments.php`
- Storage path configuration
- Validation rule definitions

### 4. Documentation & Testing
- Clear schema documentation
- Comprehensive test suite
- Usage examples and guides
- Error handling documentation

## Implementation Priority

### Phase 1: Foundation (High Priority)
1. Create core service architecture
2. Implement YAML loading and basic validation
3. Define configuration value objects
4. Set up storage integration

### Phase 2: Validation (High Priority)  
1. Implement comprehensive schema validation
2. Add reference validation (vault/project consistency)
3. Create validation error reporting
4. Add configuration testing

### Phase 3: Examples (Medium Priority)
1. Create example scenario configurations
2. Validate scenarios work with existing seeder
3. Document schema and usage patterns
4. Add scenario management utilities

## Success Validation

### Functional Testing
```bash
# Test scenario loading
php artisan demo:validate-scenario general
php artisan demo:list-scenarios

# Test configuration access
php artisan tinker --execute="
app('App\Services\Demo\DemoScenarioConfigService')
  ->loadScenario('general')
  ->getVaults()
  ->count()
"
```

### Integration Testing
- Scenarios load without errors
- Validation catches malformed YAML
- Configuration integrates with existing seeder components
- Multiple scenarios can be loaded simultaneously

This foundation will enable all subsequent task packs to build sophisticated AI-powered demo data generation on top of flexible, validated scenario configurations.