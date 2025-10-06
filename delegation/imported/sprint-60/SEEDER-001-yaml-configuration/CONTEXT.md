# SEEDER-001: YAML Configuration System — Context

## Current System Overview

### Existing Demo Seeder Architecture
The current demo data system uses a well-structured approach:

```php
// database/seeders/Demo/DemoDataSeeder.php
class DemoDataSeeder extends Seeder
{
    private array $seeders = [
        new UserSeeder,
        new VaultSeeder, 
        new ProjectSeeder,
        new TypeSeeder,
        new ContactSeeder,
        new TodoSeeder,
        new ChatSeeder,
    ];
}
```

Each seeder implements `DemoSubSeeder` interface:
```php
interface DemoSubSeeder
{
    public function seed(DemoSeedContext $context): void;
    public function cleanup(DemoSeedContext $context): void;
}
```

### Current Data Generation Patterns
**Static Configuration**: Hard-coded in each seeder class
```php
// TodoSeeder.php
private const TODO_COUNT = 100;
$statuses = ['open', 'in_progress', 'blocked', 'complete'];
$priorities = ['low', 'medium', 'high'];
$tags = ['demo', 'backlog', 'follow-up', 'urgent', 'learning', 'ops'];
```

**Generic Content**: Uses Laravel faker for basic content
```php
$title = Str::headline($faker->unique()->sentence(4));
$message = 'TODO: '.$title.' - '.$faker->sentence(6);
```

**Context Sharing**: `DemoSeedContext` allows seeders to share data
```php
$context->set('todo_fragments', (string) $fragment->id, $fragment);
$vault = $context->get('vaults', $vaultKey);
```

## Target Configuration System

### YAML Schema Structure
Based on requirements analysis, the configuration needs to support:

```yaml
scenario:
  name: "General Demo"
  description: "Mixed personal and professional use case"
  persona: "Busy professional with side projects and personal tasks"

timeframe:
  start_date: "-90d"  # 90 days ago
  end_date: "+30d"    # 30 days from now
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
  work:
    - name: "POS System Upgrade"
      description: "Retail point-of-sale system modernization"
      status: "active"

content_generation:
  todos:
    count: 50
    realistic_examples:
      personal:
        - "Pick up dry cleaning from Main Street cleaners"
        - "Schedule dentist appointment for cleaning"
      work:
        - "Review Q3 budget allocations with finance team"
        - "Update POS documentation for training"
  
  chats:
    count: 25
    messages_per_chat: 5
    topics:
      - "Planning weekend home lab work"
      - "Discussing moving timeline with partner"

  contacts:
    count: 25
    include_meeting_notes: true
    realistic_examples:
      - name: "Sarah Chen"
        role: "DevOps Lead"
        company: "TechCorp"
        relationship: "colleague"

fragment_relationships:
  link_percentage: 25
  relationship_types:
    - "related_to"
    - "blocks" 
    - "follows_up"

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
```

## Integration Requirements

### Laravel Configuration Integration
Extend `config/fragments.php` with demo seeder configuration:
```php
// config/fragments.php
return [
    // ... existing config ...
    
    'demo_seeder' => [
        'storage_path' => env('DEMO_SCENARIOS_PATH', 'demo-scenarios'),
        'default_scenario' => env('DEMO_DEFAULT_SCENARIO', 'general'),
        'cache_ttl' => env('DEMO_CONFIG_CACHE_TTL', 3600),
        'validation' => [
            'strict_mode' => env('DEMO_VALIDATION_STRICT', true),
            'schema_file' => 'demo-scenario-schema.json',
        ],
        
        // AI provider configuration for demo generation
        'ai_generation' => [
            'provider' => env('DEMO_AI_PROVIDER'), // null = use fragments.models.default_provider
            'model' => env('DEMO_AI_MODEL'),       // null = use fragments.models.default_text_model
            'temperature' => env('DEMO_AI_TEMPERATURE', 0.7),
            'max_tokens' => env('DEMO_AI_MAX_TOKENS', 500),
        ],
    ],
];
```

### Storage Integration
Use Laravel's storage system for scenario management:
```php
// Storage structure
storage/app/demo-scenarios/
├── general.yaml
├── writer.yaml
├── developer.yaml
├── productivity.yaml
└── schemas/
    └── demo-scenario-schema.json
```

### Service Container Registration
Register configuration services in a service provider:
```php
// app/Providers/DemoSeederServiceProvider.php
public function register()
{
    $this->app->singleton(DemoScenarioConfigService::class);
    
    $this->app->bind('demo.scenario.config', function ($app) {
        return $app->make(DemoScenarioConfigService::class);
    });
}
```

## Technical Dependencies

### Required Packages
- **Symfony YAML**: For YAML parsing (`symfony/yaml` - likely already included)
- **Laravel Validation**: For schema validation (built-in)
- **JSON Schema Validation**: Optional for advanced schema validation

### Existing System Dependencies
- **Fragment Model**: Core data model for all content
- **DemoSeedContext**: Context sharing between seeders
- **TimelineGenerator**: Existing temporal distribution system
- **Storage System**: Laravel's storage abstraction

### AI Provider Integration
- **Fragment Config**: Use existing `config/fragments.php` AI provider setup
- **Default Provider**: OpenAI with configurable model selection
- **Override Capability**: Allow scenario-specific AI provider selection

## Data Flow Architecture

### Configuration Loading Flow
```
1. DemoScenarioConfigService::loadScenario('general')
2. Storage::disk('local')->get('demo-scenarios/general.yaml')
3. Symfony\Component\Yaml\Yaml::parse($yamlContent)
4. DemoScenarioValidator::validate($parsedConfig)
5. DemoScenarioConfig::fromArray($validatedConfig)
6. Return typed configuration object
```

### Validation Flow
```
1. Schema validation (structure, types, required fields)
2. Reference validation (vault/project consistency)
3. Date format validation (relative date parsing)
4. Enum validation (activity patterns, statuses)
5. Content validation (realistic example formats)
```

### Integration with Existing Seeders
```
1. DemoDataSeeder loads scenario configuration
2. Configuration passed to each DemoSubSeeder via DemoSeedContext
3. Individual seeders access relevant config sections
4. AI generation services use scenario-specific rules
5. Generated content follows scenario persona and guidelines
```

## Known Challenges & Solutions

### Challenge: YAML Complexity
**Issue**: YAML can be error-prone for complex configurations
**Solution**: 
- Comprehensive validation with clear error messages
- Well-documented schema with examples
- JSON Schema validation for advanced structure checking

### Challenge: Configuration Caching
**Issue**: YAML parsing can be expensive for large configurations
**Solution**:
- Laravel configuration caching integration
- Selective caching of parsed configurations
- Cache invalidation on scenario file changes

### Challenge: Backward Compatibility
**Issue**: Existing seeders expect current hardcoded patterns
**Solution**:
- Gradual migration with fallback to current behavior
- Configuration provides defaults matching current behavior
- Existing seeders work unchanged until explicitly updated

### Challenge: Validation Complexity
**Issue**: Cross-references between configuration sections need validation
**Solution**:
- Multi-phase validation (structure → references → constraints)
- Clear validation error messages with context
- Validation helper methods for common patterns

## Success Criteria

### Configuration Loading
- [ ] Load YAML scenarios from storage without errors
- [ ] Parse complex nested configurations correctly
- [ ] Handle missing files gracefully with clear error messages
- [ ] Support multiple scenarios simultaneously

### Schema Validation
- [ ] Validate required fields and data types
- [ ] Check cross-references (vault slugs in projects)
- [ ] Validate date formats and relative date expressions
- [ ] Ensure enum values are within allowed sets

### Laravel Integration
- [ ] Service container registration works correctly
- [ ] Configuration extends `fragments.php` cleanly
- [ ] Storage integration uses Laravel patterns
- [ ] Caching integrates with Laravel cache system

### Developer Experience
- [ ] Clear documentation for schema format
- [ ] Helpful validation error messages
- [ ] Easy scenario creation and testing
- [ ] Clean integration with existing seeder patterns

This context provides the foundation for building a robust, extensible YAML configuration system that will enable sophisticated AI-powered demo data generation while maintaining clean integration with the existing Laravel demo seeder architecture.