# SEEDER-003: Enhanced Seeder Components — Context

## Current Demo Seeder Architecture Analysis

### Existing Seeder Structure
The current demo seeder system has a well-established architecture:

```php
// Current DemoDataSeeder
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
    
    // Cleanup → Seed pattern with DemoSeedContext sharing
}
```

### Current DemoSubSeeder Pattern
All seeders implement a consistent interface:

```php
interface DemoSubSeeder
{
    public function seed(DemoSeedContext $context): void;
    public function cleanup(DemoSeedContext $context): void;
}
```

### Current Content Generation Examples

#### TodoSeeder Current Implementation
```php
// database/seeders/Demo/Seeders/TodoSeeder.php
class TodoSeeder implements DemoSubSeeder
{
    private const TODO_COUNT = 100; // Hardcoded
    
    public function seed(DemoSeedContext $context): void
    {
        $statuses = ['open', 'in_progress', 'blocked', 'complete']; // Hardcoded
        $priorities = ['low', 'medium', 'high']; // Hardcoded
        $tags = ['demo', 'backlog', 'follow-up', 'urgent', 'learning', 'ops']; // Hardcoded
        
        $faker = fake();
        $dates = $this->timeline->generate(self::TODO_COUNT);
        
        $dates->each(function (Carbon $timestamp, int $index) use ($context, $statuses, $priorities, $tags, $faker) {
            // Generic faker content
            $title = Str::headline($faker->unique()->sentence(4));
            $message = 'TODO: '.$title.' - '.$faker->sentence(6);
            
            // Hardcoded vault selection
            $vaultKey = $index % 2 === 0 ? 'work' : 'personal';
            
            // Create fragment and todo records...
        });
    }
}
```

**Issues with Current Implementation:**
- **Hardcoded Values**: Counts, statuses, priorities, tags are all hardcoded
- **Generic Content**: Faker generates unrealistic "Lorem ipsum" style content
- **Limited Scenarios**: Only supports basic work/personal split
- **No Persona**: No consistent voice or user type consideration
- **No AI Integration**: No use of intelligent content generation

### Current Context Sharing System
The `DemoSeedContext` provides data sharing between seeders:

```php
// DemoSeedContext usage patterns
$context->set('vaults', $vaultKey, $vault);              // Store created vaults
$vault = $context->get('vaults', $vaultKey);             // Retrieve vaults
$projects = $context->collection('projects');            // Get all projects
$context->forget('todo_fragments', (string) $fragment->id); // Clean up
```

## Target Enhanced Architecture

### Enhanced Seeder Integration Flow
```
1. EnhancedDemoDataSeeder loads scenario configuration
2. Scenario config injected into EnhancedDemoSeedContext
3. Each enhanced seeder reads scenario-specific configuration
4. AI content generation called with scenario context
5. Generated content creates realistic fragments
6. Context sharing maintains relationships between seeders
```

### Configuration-Driven Content Generation
Instead of hardcoded values, seeders will read from YAML scenarios:

```yaml
# From general.yaml scenario
content_generation:
  todos:
    count: 50                    # Instead of hardcoded TODO_COUNT = 100
    realistic_examples:          # Instead of faker.sentence()
      personal:
        - "Pick up dry cleaning from Main Street cleaners"
        - "Schedule dentist appointment for cleaning"
      work:
        - "Review Q3 budget allocations with finance team"
        - "Update POS documentation for training"
    
    distribution:
      personal: 60%              # Instead of hardcoded 50/50 split
      work: 40%
    
    statuses:                    # Configurable instead of hardcoded
      open: 40%
      in_progress: 30%
      complete: 20%
      blocked: 10%
```

### AI-Powered Content Integration
Enhanced seeders will use AI-generated content from SEEDER-002:

```php
// Enhanced TodoSeeder approach
class EnhancedTodoSeeder extends EnhancedDemoSubSeeder
{
    public function seed(DemoSeedContext $context): void
    {
        $todoConfig = $this->scenarioConfig->getContentGeneration()->getTodos();
        
        // AI-generated realistic content
        $todos = $this->contentGenerator->generateTodos($todoConfig);
        
        // Timeline distribution with realistic content
        $this->createFragmentsFromAIContent($todos, $context);
    }
}
```

## Seeder Enhancement Strategy

### VaultSeeder Enhancement
**Current**: Hardcoded vault creation
```php
// Current approach
Vault::create(['name' => 'Demo Work Vault', 'slug' => 'work']);
```

**Enhanced**: Scenario-driven vault creation
```php
// Enhanced approach
foreach ($this->scenarioConfig->getVaults() as $vaultConfig) {
    Vault::create([
        'name' => $vaultConfig->getName(),          // "Personal" or "Work"
        'slug' => $vaultConfig->getSlug(),          // "personal" or "work"
        'description' => $vaultConfig->getDescription(),
        'metadata' => [
            'demo_seed' => true,
            'scenario' => $this->scenarioConfig->getScenarioInfo()->getName(),
        ],
    ]);
}
```

### ProjectSeeder Enhancement
**Current**: Basic project creation with faker names
```php
// Current approach (simplified)
$project = Project::create([
    'name' => $faker->company(),
    'description' => $faker->sentence(),
]);
```

**Enhanced**: Scenario-specific projects
```php
// Enhanced approach
foreach ($this->scenarioConfig->getProjects() as $vaultSlug => $projects) {
    foreach ($projects as $projectConfig) {
        Project::create([
            'name' => $projectConfig->getName(),           // "Home Lab Setup"
            'description' => $projectConfig->getDescription(), // "Setting up home server..."
            'status' => $projectConfig->getStatus(),       // "active"
            'vault_id' => $vault->id,
        ]);
    }
}
```

### ContactSeeder Enhancement
**Current**: Basic faker contacts
```php
// Current approach
Contact::create([
    'name' => $faker->name(),
    'role' => $faker->jobTitle(),
    'company' => $faker->company(),
]);
```

**Enhanced**: AI-generated realistic contacts with meeting notes
```php
// Enhanced approach
$contacts = $this->contentGenerator->generateContacts($contactConfig);

foreach ($contacts as $contactContent) {
    $contact = Contact::create([
        'name' => $contactContent->getName(),        // "Sarah Chen"
        'role' => $contactContent->getRole(),        // "DevOps Lead"
        'company' => $contactContent->getCompany(),  // "TechCorp"
    ]);
    
    // Create fragment with AI-generated meeting notes
    Fragment::create([
        'type' => 'contact',
        'title' => $contactContent->getName(),
        'message' => $contactContent->getMeetingNotes(), // Realistic meeting notes
        'tags' => $contactContent->getTags(),            // Context-appropriate tags
    ]);
}
```

## Integration Requirements

### DemoSeedContext Enhancement
The existing context system needs enhancement to support scenario configurations:

```php
// Enhanced DemoSeedContext
class EnhancedDemoSeedContext extends DemoSeedContext
{
    public function setScenarioConfig(DemoScenarioConfig $config): void
    {
        $this->set('scenario_config', 'current', $config);
    }
    
    public function getScenarioConfig(): ?DemoScenarioConfig
    {
        return $this->get('scenario_config', 'current');
    }
    
    public function getPersona(): ?string
    {
        return $this->getScenarioConfig()?->getScenarioInfo()->getPersona();
    }
    
    public function getVaultForContent(string $contentType, int $index): ?Vault
    {
        // Intelligent vault selection based on scenario configuration
        $distribution = $this->getScenarioConfig()
            ->getContentGeneration()
            ->getContentTypeConfig($contentType)
            ->getVaultDistribution();
            
        return $this->selectVaultByDistribution($distribution, $index);
    }
}
```

### Error Handling and Fallback Integration
Enhanced seeders must handle AI generation failures gracefully:

```php
// Fallback strategy integration
abstract class EnhancedDemoSubSeeder implements DemoSubSeeder
{
    protected function generateContentWithFallback(callable $aiGenerator, callable $fallbackGenerator): Collection
    {
        try {
            return $aiGenerator();
        } catch (AiGenerationException $e) {
            Log::warning('AI generation failed, using fallback', [
                'seeder' => static::class,
                'error' => $e->getMessage(),
            ]);
            
            return $fallbackGenerator();
        }
    }
}
```

### Timeline Integration
Enhanced seeders must maintain the existing temporal distribution system:

```php
// Timeline integration with AI content
class EnhancedTodoSeeder extends EnhancedDemoSubSeeder
{
    private function createFragmentsFromAIContent(Collection $todos, DemoSeedContext $context): void
    {
        // Existing TimelineGenerator integration
        $dates = $this->timeline->generate($todos->count());
        
        $todos->zip($dates)->each(function ($pair) use ($context) {
            [$todoContent, $timestamp] = $pair;
            
            $fragmentData = $todoContent->toFragmentData();
            $fragmentData['created_at'] = $timestamp;
            $fragmentData['updated_at'] = $timestamp;
            $fragmentData['inbox_at'] = $timestamp;
            
            $fragment = Fragment::create($fragmentData);
            
            // Maintain existing context sharing patterns
            $context->set('todo_fragments', (string) $fragment->id, $fragment);
        });
    }
}
```

## Backwards Compatibility Strategy

### Existing Interface Compliance
All enhanced seeders must maintain the existing `DemoSubSeeder` interface:

```php
// Maintained interface compliance
class EnhancedTodoSeeder extends EnhancedDemoSubSeeder
{
    // Required DemoSubSeeder methods
    public function seed(DemoSeedContext $context): void { /* Enhanced implementation */ }
    public function cleanup(DemoSeedContext $context): void { /* Existing cleanup logic */ }
    
    // Additional enhanced functionality
    protected function generateContent(): Collection { /* AI integration */ }
    protected function createFragments(Collection $content, DemoSeedContext $context): void { /* Enhanced creation */ }
}
```

### Gradual Migration Strategy
Enhanced seeders can coexist with existing seeders during migration:

```php
// Migration-friendly main seeder
class EnhancedDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $useEnhancedSeeders = config('fragments.demo_seeder.use_enhanced', true);
        
        if ($useEnhancedSeeders) {
            $this->runEnhancedSeeders();
        } else {
            $this->runLegacySeeders();
        }
    }
}
```

## Data Model Compatibility

### Fragment Model Integration
Enhanced content must be compatible with existing Fragment model structure:

```php
// Fragment compatibility requirements
class TodoContent
{
    public function toFragmentData(): array
    {
        return [
            'type' => 'todo',                    // Existing type system
            'title' => $this->title,             // Existing field
            'message' => $this->message,         // Existing field
            'tags' => $this->tags,               // Existing field
            'relationships' => [],               // Existing field
            'metadata' => [
                'demo_seed' => true,             // Existing demo flag
                'demo_category' => 'todo',       // Existing category
                'ai_generated' => true,          // New: AI generation flag
                'scenario' => $this->scenario,   // New: Scenario tracking
            ],
            'state' => $this->state,             // Existing field
            'vault' => $this->vault,             // Existing field
            'project_id' => $this->projectId,    // Existing field
            'inbox_status' => 'accepted',        // Existing field
        ];
    }
}
```

### Database Schema Compatibility
No database schema changes required - enhanced seeders work with existing tables:
- `fragments` table: Uses existing structure with enhanced metadata
- `todos`, `contacts`, `chat_sessions` tables: Maintain existing relationships
- `vaults`, `projects` tables: Enhanced metadata within existing JSON columns

## Performance Considerations

### AI Generation Overhead
Enhanced seeders must account for AI generation latency:

```php
// Performance optimization strategies
class EnhancedTodoSeeder extends EnhancedDemoSubSeeder
{
    private function generateContentEfficiently(TodoGenerationConfig $config): Collection
    {
        // Batch AI requests to reduce round trips
        $batchSize = config('fragments.demo_seeder.ai_generation.batch_size', 10);
        
        // Parallel generation for large datasets
        if ($config->getCount() > 50) {
            return $this->generateInParallel($config, $batchSize);
        }
        
        return $this->generateSequentially($config);
    }
}
```

### Memory Usage Optimization
Handle large content generation efficiently:

```php
// Memory-efficient content processing
private function processLargeContentSet(Collection $content, DemoSeedContext $context): void
{
    // Process in chunks to avoid memory exhaustion
    $content->chunk(100)->each(function ($chunk) use ($context) {
        $this->createFragmentsFromChunk($chunk, $context);
    });
}
```

## Success Criteria

### Functional Integration
- [ ] Enhanced seeders read YAML scenario configurations
- [ ] AI-generated content integrates with existing Fragment model
- [ ] Timeline distribution maintains existing patterns
- [ ] Context sharing works with enhanced data
- [ ] Cleanup functionality preserved

### Quality Improvements
- [ ] Generated content significantly more realistic than faker
- [ ] Scenario-appropriate content for different user types
- [ ] Consistent persona voice across generated content
- [ ] Natural relationships between generated items

### Performance Maintenance
- [ ] Total seeding time remains under 2 minutes for standard scenarios
- [ ] Memory usage stays within acceptable limits
- [ ] Error handling doesn't significantly impact performance
- [ ] Fallback systems activate quickly when needed

This enhanced seeder architecture provides the bridge between YAML scenario configurations, AI-generated content, and the existing demo data infrastructure, creating a seamless system that generates realistic, scenario-driven demo data while maintaining all existing functionality and performance characteristics.