# SEEDER-001: YAML Configuration System — Implementation Plan

## Executive Summary

Create a comprehensive YAML-based configuration system for AI-powered demo data scenarios. This foundation enables flexible, validated scenario definitions that replace hardcoded seeder configuration with dynamic, extensible YAML files.

**Estimated Effort**: 8-12 hours  
**Priority**: High (Foundation for all other task packs)  
**Dependencies**: None (creates foundation)

## Implementation Phases

### Phase 1: Core Configuration Infrastructure (3-4h)

#### 1.1 Service Architecture Setup
```php
// app/Services/Demo/DemoScenarioConfigService.php
class DemoScenarioConfigService
{
    public function loadScenario(string $name): DemoScenarioConfig
    public function validateSchema(array $config): ValidationResult  
    public function listAvailableScenarios(): Collection
    public function getDefaultScenario(): DemoScenarioConfig
}

// app/Services/Demo/Config/DemoScenarioConfig.php  
class DemoScenarioConfig
{
    public function getScenarioInfo(): ScenarioInfo
    public function getTimeframe(): TimeframeConfig
    public function getVaults(): Collection<VaultConfig>
    public function getProjects(): Collection<ProjectConfig>
    public function getContentGeneration(): ContentGenerationConfig
    public function getFragmentRelationships(): RelationshipConfig
    public function getTaggingStrategy(): TaggingConfig
    public function getAiGenerationRules(): Collection<string>
}
```

#### 1.2 Value Objects Creation
```php
// app/Services/Demo/Config/ScenarioInfo.php
class ScenarioInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $description, 
        public readonly string $persona
    ) {}
}

// app/Services/Demo/Config/TimeframeConfig.php
class TimeframeConfig
{
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $activityPattern
    ) {}
    
    public function getStartDate(): Carbon
    public function getEndDate(): Carbon
    public function getActivityPattern(): ActivityPattern
}

// app/Services/Demo/Config/VaultConfig.php
class VaultConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $description,
        public readonly array $metadata = []
    ) {}
}
```

#### 1.3 Storage Integration
```php
// Setup storage paths and file management
storage/app/demo-scenarios/
├── general.yaml
├── writer.yaml
├── developer.yaml
└── productivity.yaml

// Configuration in config/fragments.php
'demo_seeder' => [
    'storage_path' => env('DEMO_SCENARIOS_PATH', 'demo-scenarios'),
    'default_scenario' => env('DEMO_DEFAULT_SCENARIO', 'general'),
    'cache_ttl' => env('DEMO_CONFIG_CACHE_TTL', 3600),
]
```

### Phase 2: YAML Processing & Validation (3-4h)

#### 2.1 YAML Parser Integration
```php
// app/Services/Demo/DemoScenarioConfigService.php
use Symfony\Component\Yaml\Yaml;

private function loadYamlFile(string $name): array
{
    $path = "demo-scenarios/{$name}.yaml";
    
    if (!Storage::exists($path)) {
        throw new ScenarioNotFoundException("Scenario '{$name}' not found");
    }
    
    $content = Storage::get($path);
    
    try {
        return Yaml::parse($content);
    } catch (ParseException $e) {
        throw new ScenarioParseException("Invalid YAML in scenario '{$name}': {$e->getMessage()}");
    }
}
```

#### 2.2 Schema Validation System
```php
// app/Services/Demo/Validation/DemoScenarioValidator.php
class DemoScenarioValidator
{
    private array $rules = [
        'scenario' => 'required|array',
        'scenario.name' => 'required|string|max:255',
        'scenario.description' => 'required|string',
        'scenario.persona' => 'required|string',
        
        'timeframe' => 'required|array',
        'timeframe.start_date' => 'required|string|regex:/^[+-]\d+[dwmy]$/',
        'timeframe.end_date' => 'required|string|regex:/^[+-]\d+[dwmy]$/',
        'timeframe.activity_pattern' => 'required|in:business_hours_weighted,random,evening_heavy',
        
        'vaults' => 'required|array|min:1',
        'vaults.*.name' => 'required|string|max:255',
        'vaults.*.slug' => 'required|string|alpha_dash|max:50',
        'vaults.*.description' => 'required|string',
        
        'projects' => 'required|array',
        'content_generation' => 'required|array',
        'fragment_relationships' => 'required|array',
        'tagging_strategy' => 'required|array',
        'ai_generation_rules' => 'required|array|min:1',
    ];
    
    public function validate(array $config): ValidationResult
    {
        $validator = Validator::make($config, $this->rules);
        
        if ($validator->fails()) {
            return ValidationResult::failed($validator->errors());
        }
        
        // Custom validation logic
        $this->validateCrossReferences($config);
        $this->validateDateFormats($config);
        
        return ValidationResult::passed();
    }
}
```

#### 2.3 Error Handling & Reporting
```php
// app/Services/Demo/Exceptions/
ScenarioNotFoundException.php
ScenarioParseException.php
ScenarioValidationException.php

// Clear error messages with context
class ValidationResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly Collection $errors = new Collection(),
        public readonly Collection $warnings = new Collection()
    ) {}
    
    public function getErrorSummary(): string
    public function getDetailedErrors(): array
}
```

### Phase 3: Example Scenarios Creation (2-3h)

#### 3.1 General Demo Scenario
```yaml
# storage/app/demo-scenarios/general.yaml
scenario:
  name: "General Demo"
  description: "Mixed personal and professional use case"
  persona: "Busy professional with side projects and personal tasks"

timeframe:
  start_date: "-90d"
  end_date: "+30d"
  activity_pattern: "business_hours_weighted"

vaults:
  - name: "Personal"
    slug: "personal"
    description: "Personal life organization"
  - name: "Work"  
    slug: "work"
    description: "Professional work projects"

projects:
  personal:
    - name: "Home Lab Setup"
      description: "Setting up home server and network"
      status: "active"
    - name: "Moving Preparation"
      description: "Planning and organizing move to new apartment"
      status: "active"
  
  work:
    - name: "POS System Upgrade"
      description: "Retail point-of-sale system modernization"
      status: "active"
    - name: "Salesforce Support Cases"
      description: "Customer support ticket management"
      status: "active"

content_generation:
  todos:
    count: 50
    realistic_examples:
      personal:
        - "Pick up dry cleaning from Main Street cleaners"
        - "Schedule dentist appointment for cleaning"
        - "Research moving companies for October move"
        - "Buy birthday gift for mom"
        - "Set up router in home lab"
      work:
        - "Review Q3 budget allocations with finance team"
        - "Update POS documentation for training"
        - "Investigate customer login issues reported yesterday"
        - "Schedule team retrospective for sprint 23"
        - "Deploy hotfix for payment processing bug"

  chats:
    count: 25
    messages_per_chat: 5
    topics:
      - "Planning weekend home lab work"
      - "Discussing moving timeline with partner"
      - "Brainstorming POS upgrade approach"
      - "Troubleshooting server issues"
      - "Sprint planning discussion"

  contacts:
    count: 25
    include_meeting_notes: true
    realistic_examples:
      - name: "Sarah Chen"
        role: "DevOps Lead"
        company: "TechCorp"
        relationship: "colleague"
      - name: "Mike Rodriguez"
        role: "Moving Specialist"
        company: "City Movers"
        relationship: "service_provider"
      - name: "Jennifer Park"
        role: "Product Manager"
        company: "TechCorp"
        relationship: "colleague"

  reminders:
    count: 12
    types: ["meeting", "deadline", "followup", "personal"]

fragment_relationships:
  link_percentage: 25
  relationship_types:
    - "related_to"
    - "blocks"
    - "follows_up"
    - "references"

tagging_strategy:
  contact_tags:
    percentage: 40
    apply_to: ["meeting_notes", "todos", "reminders"]
  
  auto_tags:
    enabled: true
    categories: ["urgent", "waiting", "someday", "project", "area"]

ai_generation_rules:
  - "Use realistic, specific content rather than generic placeholders"
  - "Create natural relationships between related fragments"
  - "Include realistic project timelines and dependencies"
  - "Generate authentic meeting notes with actionable items"
  - "Use consistent persona voice across all content"
  - "Include both completed and pending items for realism"
```

#### 3.2 Specialized Scenarios
Create additional scenarios for specific use cases:
- `writer.yaml`: Content creator focused scenario
- `developer.yaml`: Programming and technical work scenario
- `productivity.yaml`: Task management and organization focused

### Phase 4: Laravel Integration (1-2h)

#### 4.1 Service Provider Registration
```php
// app/Providers/DemoSeederServiceProvider.php
class DemoSeederServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DemoScenarioConfigService::class);
        
        $this->app->bind('demo.scenario.config', function ($app) {
            return $app->make(DemoScenarioConfigService::class);
        });
    }
    
    public function boot()
    {
        if ($this->app->environment(['local', 'testing'])) {
            $this->publishes([
                __DIR__.'/../../storage/app/demo-scenarios' => storage_path('app/demo-scenarios'),
            ], 'demo-scenarios');
        }
    }
}
```

#### 4.2 Configuration Extension
```php
// config/fragments.php extension
'demo_seeder' => [
    'storage_path' => env('DEMO_SCENARIOS_PATH', 'demo-scenarios'),
    'default_scenario' => env('DEMO_DEFAULT_SCENARIO', 'general'),
    'cache_ttl' => env('DEMO_CONFIG_CACHE_TTL', 3600),
    
    'validation' => [
        'strict_mode' => env('DEMO_VALIDATION_STRICT', true),
        'schema_file' => 'demo-scenario-schema.json',
    ],
    
    'ai_generation' => [
        'provider' => env('DEMO_AI_PROVIDER'), // null = use default
        'model' => env('DEMO_AI_MODEL'),       // null = use default
        'temperature' => env('DEMO_AI_TEMPERATURE', 0.7),
        'max_tokens' => env('DEMO_AI_MAX_TOKENS', 500),
    ],
],
```

#### 4.3 Console Commands
```php
// app/Console/Commands/Demo/ValidateScenarioCommand.php
php artisan demo:validate-scenario general

// app/Console/Commands/Demo/ListScenariosCommand.php  
php artisan demo:list-scenarios

// app/Console/Commands/Demo/CreateScenarioCommand.php
php artisan demo:create-scenario writer --template=general
```

## Testing Strategy

### Unit Tests
```php
// tests/Unit/Services/Demo/DemoScenarioConfigServiceTest.php
test('can load valid scenario configuration')
test('throws exception for missing scenario')
test('validates schema correctly')
test('handles malformed YAML gracefully')

// tests/Unit/Services/Demo/Config/DemoScenarioConfigTest.php
test('provides typed access to configuration sections')
test('converts relative dates correctly')
test('validates cross-references')
```

### Integration Tests
```php
// tests/Feature/Demo/ScenarioLoadingTest.php
test('loads general scenario successfully')
test('validates all shipped scenarios')
test('integrates with Laravel storage system')
test('caches configurations properly')
```

### Example Validation Tests
```php
// tests/Feature/Demo/ScenarioValidationTest.php
test('general scenario passes validation')
test('writer scenario has valid structure')
test('developer scenario references are consistent')
test('productivity scenario generates valid configurations')
```

## Quality Assurance

### Code Quality
- [ ] PSR-12 compliance with Pint
- [ ] Type declarations for all methods
- [ ] Comprehensive docblocks
- [ ] Exception handling for all failure modes

### Documentation
- [ ] Schema documentation with examples
- [ ] Usage guides for creating new scenarios
- [ ] Integration documentation for other task packs
- [ ] Error troubleshooting guide

### Performance
- [ ] Configuration caching implementation
- [ ] Lazy loading of unused configuration sections
- [ ] Efficient YAML parsing
- [ ] Memory usage optimization

## Delivery Checklist

### Core Implementation
- [ ] `DemoScenarioConfigService` with full functionality
- [ ] Configuration value objects with typed access
- [ ] YAML parsing and validation system
- [ ] Error handling and reporting

### Example Scenarios
- [ ] `general.yaml` - comprehensive mixed-use scenario
- [ ] `writer.yaml` - content creator scenario
- [ ] `developer.yaml` - programming scenario  
- [ ] `productivity.yaml` - task management scenario

### Laravel Integration
- [ ] Service provider registration
- [ ] Configuration file extension
- [ ] Storage integration
- [ ] Console command utilities

### Documentation & Testing
- [ ] Comprehensive test suite (>90% coverage)
- [ ] Schema documentation
- [ ] Usage examples
- [ ] Integration guides for other task packs

## Success Validation

### Functional Testing
```bash
# Basic functionality
php artisan demo:list-scenarios
php artisan demo:validate-scenario general

# Integration testing
php artisan tinker --execute="
\$config = app('App\Services\Demo\DemoScenarioConfigService')
    ->loadScenario('general');
echo \$config->getVaults()->count() . ' vaults loaded';
echo \$config->getContentGeneration()->getTodos()->getCount() . ' todos configured';
"
```

### Quality Gates
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] All example scenarios validate successfully
- [ ] Performance benchmarks met
- [ ] Code coverage >90%

This implementation plan provides a robust foundation for the AI-powered demo data seeder system, enabling subsequent task packs to build sophisticated content generation on top of flexible, validated scenario configurations.