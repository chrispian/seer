# SEEDER-003: Enhanced Seeder Components Agent

## Agent Mission

You are a Laravel backend developer specializing in database seeding and data generation systems. Your mission is to enhance the existing demo seeder components to integrate with AI-powered content generation and YAML scenario configurations. You will transform the current hardcoded seeders into intelligent, configurable components that create realistic demo data.

## Core Objectives

### Primary Goal
Upgrade existing demo seeder components to:
- Integrate with YAML scenario configurations (SEEDER-001)
- Utilize AI-generated realistic content (SEEDER-002)
- Maintain compatibility with existing `DemoSubSeeder` architecture
- Create configurable, scenario-driven data generation
- Provide enhanced content quality and authenticity

### Success Metrics
- [ ] All demo seeders use scenario configurations instead of hardcoded values
- [ ] Generated content uses AI-powered realistic examples
- [ ] Maintain existing data volumes and temporal distribution
- [ ] Clean integration with current `DemoSeedContext` system
- [ ] Backwards compatibility with existing seeder patterns

## Technical Specifications

### Enhanced Seeder Architecture
```php
// Enhanced seeder pattern
class EnhancedTodoSeeder implements DemoSubSeeder
{
    public function __construct(
        private readonly TimelineGenerator $timeline,
        private readonly DemoContentGenerationService $contentGenerator,
        private readonly DemoScenarioConfigService $configService
    ) {}
    
    public function seed(DemoSeedContext $context): void
    {
        $scenarioConfig = $context->get('scenario_config');
        $todoConfig = $scenarioConfig->getContentGeneration()->getTodos();
        
        // Use AI-generated content or fallback to enhanced templates
        $todos = $this->contentGenerator->generateTodos($todoConfig);
        
        // Create fragments using enhanced content
        $this->createFragmentsFromContent($todos, $context);
    }
}
```

### Integration Requirements
- **YAML Configuration**: Read generation rules from scenario configs
- **AI Content**: Use realistic, AI-generated content when available
- **Timeline Integration**: Maintain existing temporal distribution patterns
- **Context Sharing**: Enhance `DemoSeedContext` with scenario information
- **Fallback Support**: Graceful degradation when AI generation fails

### Seeder Enhancement Scope
1. **VaultSeeder**: Scenario-driven vault creation with metadata
2. **ProjectSeeder**: Project definitions from YAML configurations
3. **TodoSeeder**: AI-generated realistic todos with smart distribution
4. **ContactSeeder**: AI-generated contacts with meeting notes
5. **ChatSeeder**: AI-generated conversation flows
6. **TypeSeeder**: Enhanced with scenario-specific types
7. **UserSeeder**: Maintain existing functionality with scenario context

## Implementation Approach

### 1. Enhanced Base Architecture
```php
// app/Services/Demo/Seeders/EnhancedDemoSubSeeder.php
abstract class EnhancedDemoSubSeeder implements DemoSubSeeder
{
    protected DemoScenarioConfig $scenarioConfig;
    protected DemoContentGenerationService $contentGenerator;
    
    public function __construct(
        protected readonly DemoContentGenerationService $contentGenerator,
        protected readonly DemoScenarioConfigService $configService
    ) {}
    
    protected function initializeFromContext(DemoSeedContext $context): void
    {
        $this->scenarioConfig = $context->get('scenario_config');
        if (!$this->scenarioConfig) {
            throw new InvalidArgumentException('Scenario configuration not found in context');
        }
    }
    
    abstract protected function generateContent(): Collection;
    abstract protected function createFragments(Collection $content, DemoSeedContext $context): void;
}
```

### 2. Enhanced Vault Seeder
```php
// database/seeders/Demo/Seeders/EnhancedVaultSeeder.php
class EnhancedVaultSeeder extends EnhancedDemoSubSeeder
{
    public function seed(DemoSeedContext $context): void
    {
        $this->initializeFromContext($context);
        
        foreach ($this->scenarioConfig->getVaults() as $vaultConfig) {
            $vault = Vault::create([
                'name' => $vaultConfig->getName(),
                'slug' => $vaultConfig->getSlug(),
                'description' => $vaultConfig->getDescription(),
                'metadata' => array_merge($vaultConfig->getMetadata(), [
                    'demo_seed' => true,
                    'scenario' => $this->scenarioConfig->getScenarioInfo()->getName(),
                ]),
            ]);
            
            $context->set('vaults', $vaultConfig->getSlug(), $vault);
        }
    }
}
```

### 3. Enhanced Project Seeder
```php
// database/seeders/Demo/Seeders/EnhancedProjectSeeder.php
class EnhancedProjectSeeder extends EnhancedDemoSubSeeder
{
    public function seed(DemoSeedContext $context): void
    {
        $this->initializeFromContext($context);
        
        foreach ($this->scenarioConfig->getProjects() as $vaultSlug => $projects) {
            $vault = $context->get('vaults', $vaultSlug);
            
            foreach ($projects as $projectConfig) {
                $project = Project::create([
                    'name' => $projectConfig->getName(),
                    'description' => $projectConfig->getDescription(),
                    'status' => $projectConfig->getStatus(),
                    'vault_id' => $vault->id,
                    'metadata' => [
                        'demo_seed' => true,
                        'scenario' => $this->scenarioConfig->getScenarioInfo()->getName(),
                    ],
                ]);
                
                $context->set('projects', "{$vaultSlug}.{$projectConfig->getSlug()}", $project);
            }
        }
    }
}
```

### 4. Enhanced Todo Seeder
```php
// database/seeders/Demo/Seeders/EnhancedTodoSeeder.php
class EnhancedTodoSeeder extends EnhancedDemoSubSeeder
{
    private const DEMO_FLAG = 'demo_seed';
    
    public function seed(DemoSeedContext $context): void
    {
        $this->initializeFromContext($context);
        
        $todoConfig = $this->scenarioConfig->getContentGeneration()->getTodos();
        
        // Generate AI-powered content
        try {
            $todos = $this->contentGenerator->generateTodos($todoConfig);
        } catch (AiGenerationException $e) {
            Log::warning('AI todo generation failed, using fallback', ['error' => $e->getMessage()]);
            $todos = $this->generateFallbackTodos($todoConfig);
        }
        
        // Create fragments with timeline distribution
        $this->createTodoFragments($todos, $context);
    }
    
    private function createTodoFragments(Collection $todos, DemoSeedContext $context): void
    {
        $dates = $this->timeline->generate($todos->count());
        
        $todos->zip($dates)->each(function ($pair, $index) use ($context) {
            [$todoContent, $timestamp] = $pair;
            
            $fragmentData = $todoContent->toFragmentData();
            $fragmentData['created_at'] = $timestamp;
            $fragmentData['updated_at'] = $timestamp;
            $fragmentData['inbox_at'] = $timestamp;
            
            $fragment = Fragment::create($fragmentData);
            
            // Create associated Todo model
            Todo::create([
                'fragment_id' => $fragment->id,
                'title' => $todoContent->getTitle(),
                'state' => $todoContent->getState(),
            ]);
            
            $context->set('todo_fragments', (string) $fragment->id, $fragment);
        });
    }
}
```

### 5. Enhanced Contact Seeder
```php
// database/seeders/Demo/Seeders/EnhancedContactSeeder.php
class EnhancedContactSeeder extends EnhancedDemoSubSeeder
{
    public function seed(DemoSeedContext $context): void
    {
        $this->initializeFromContext($context);
        
        $contactConfig = $this->scenarioConfig->getContentGeneration()->getContacts();
        
        // Generate AI-powered contacts
        try {
            $contacts = $this->contentGenerator->generateContacts($contactConfig);
        } catch (AiGenerationException $e) {
            $contacts = $this->generateFallbackContacts($contactConfig);
        }
        
        $this->createContactFragments($contacts, $context);
    }
    
    private function createContactFragments(Collection $contacts, DemoSeedContext $context): void
    {
        $dates = $this->timeline->generate($contacts->count());
        
        $contacts->zip($dates)->each(function ($pair) use ($context) {
            [$contactContent, $timestamp] = $pair;
            
            // Create contact record
            $contact = Contact::create([
                'name' => $contactContent->getName(),
                'role' => $contactContent->getRole(),
                'company' => $contactContent->getCompany(),
                'metadata' => [
                    'demo_seed' => true,
                    'relationship' => $contactContent->getRelationship(),
                ],
            ]);
            
            // Create fragment for contact
            $fragment = Fragment::create([
                'type' => 'contact',
                'title' => $contactContent->getName(),
                'message' => $contactContent->getMeetingNotes() ?? "Contact: {$contactContent->getName()}",
                'tags' => $contactContent->getTags(),
                'metadata' => [
                    'demo_seed' => true,
                    'demo_category' => 'contact',
                    'contact_id' => $contact->id,
                ],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
            
            $context->set('contact_fragments', (string) $fragment->id, $fragment);
            $context->set('contacts', (string) $contact->id, $contact);
        });
    }
}
```

## Technical Constraints

### Existing System Compatibility
- **DemoSubSeeder Interface**: Maintain existing `seed()` and `cleanup()` methods
- **DemoSeedContext**: Enhance without breaking existing context usage
- **TimelineGenerator**: Integrate temporal distribution with enhanced content
- **Fragment Model**: Ensure generated content is compatible with existing schema

### Performance Considerations
- **AI Generation**: Handle potential latency from AI content generation
- **Batch Processing**: Optimize for large content volumes
- **Memory Usage**: Efficient handling of generated content collections
- **Database Performance**: Maintain existing seeder performance characteristics

### Error Handling
- **AI Failures**: Graceful fallback to enhanced template generation
- **Configuration Errors**: Clear error messages for invalid YAML configurations
- **Data Validation**: Ensure generated content meets Fragment model requirements
- **Rollback Support**: Maintain existing cleanup functionality

## Development Guidelines

### Code Organization
```
database/seeders/Demo/Seeders/Enhanced/
├── EnhancedDemoSubSeeder.php (base class)
├── EnhancedVaultSeeder.php
├── EnhancedProjectSeeder.php
├── EnhancedTodoSeeder.php
├── EnhancedContactSeeder.php
├── EnhancedChatSeeder.php
├── EnhancedTypeSeeder.php
└── EnhancedUserSeeder.php

database/seeders/Demo/
├── EnhancedDemoDataSeeder.php (updated main seeder)
└── Support/
    ├── EnhancedDemoSeedContext.php
    └── ScenarioContextInitializer.php
```

### Integration Pattern
```php
// Updated main demo seeder
class EnhancedDemoDataSeeder extends Seeder
{
    private array $enhancedSeeders;
    
    public function __construct(
        DemoScenarioConfigService $configService,
        DemoContentGenerationService $contentGenerator
    ) {
        $this->enhancedSeeders = [
            new EnhancedUserSeeder($contentGenerator, $configService),
            new EnhancedVaultSeeder($contentGenerator, $configService),
            new EnhancedProjectSeeder($contentGenerator, $configService),
            new EnhancedTypeSeeder($contentGenerator, $configService),
            new EnhancedContactSeeder($contentGenerator, $configService),
            new EnhancedTodoSeeder($contentGenerator, $configService),
            new EnhancedChatSeeder($contentGenerator, $configService),
        ];
    }
    
    public function run(): void
    {
        $scenarioName = config('fragments.demo_seeder.default_scenario', 'general');
        $scenarioConfig = $this->configService->loadScenario($scenarioName);
        
        $context = new EnhancedDemoSeedContext($this->command?->getOutput());
        $context->set('scenario_config', 'current', $scenarioConfig);
        
        // Run enhanced seeders
        foreach ($this->enhancedSeeders as $seeder) {
            $seeder->seed($context);
        }
    }
}
```

## Key Deliverables

### 1. Enhanced Seeder Components
- Enhanced versions of all existing demo seeders
- Integration with YAML scenario configurations
- AI-powered content generation integration
- Maintained compatibility with existing patterns

### 2. Improved DemoSeedContext
- Scenario configuration management
- Enhanced context sharing between seeders
- Better error handling and validation
- Performance optimization for large datasets

### 3. Fallback Systems
- Template-based content generation for AI failures
- Configuration validation and error recovery
- Graceful degradation strategies
- Comprehensive logging and monitoring

### 4. Integration Testing
- Comprehensive test suite for enhanced seeders
- Scenario configuration testing
- AI integration testing with mocking
- Performance benchmarking

## Implementation Priority

### Phase 1: Foundation (High Priority)
1. Create enhanced base seeder architecture
2. Implement EnhancedDemoSeedContext
3. Create scenario configuration integration
4. Add error handling and fallback systems

### Phase 2: Core Seeders (High Priority)
1. Enhance VaultSeeder and ProjectSeeder
2. Upgrade TodoSeeder with AI integration
3. Enhance ContactSeeder with meeting notes
4. Update ChatSeeder for conversation flows

### Phase 3: Integration (Medium Priority)
1. Create EnhancedDemoDataSeeder
2. Implement comprehensive testing
3. Add performance optimization
4. Create migration utilities

## Success Validation

### Functional Testing
```bash
# Test enhanced seeders with scenarios
php artisan demo:seed --scenario=general
php artisan demo:seed --scenario=writer
php artisan demo:seed --scenario=developer

# Validate generated content
php artisan demo:validate-scenario general --check-fragments
php artisan demo:validate-scenario writer --check-relationships
```

### Quality Assurance
- Generated content uses scenario configurations
- AI content integration works correctly
- Fallback systems function when AI unavailable
- Performance remains acceptable for demo volumes
- Existing seeder patterns maintained

This enhanced seeder system will bridge the YAML configurations and AI content generation with the existing demo data infrastructure, creating a seamless experience that generates realistic, scenario-driven demo data.