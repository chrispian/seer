# SEEDER-003: Enhanced Seeder Components — Implementation Plan

## Executive Summary

Upgrade existing demo seeder components to integrate with YAML scenario configurations and AI-powered content generation. Transform hardcoded, faker-based seeders into intelligent, configurable components that create realistic demo data while maintaining full backwards compatibility.

**Estimated Effort**: 8-12 hours  
**Priority**: High (Core integration layer)  
**Dependencies**: SEEDER-001 (YAML Configuration), SEEDER-002 (AI Content Generation)

## Implementation Phases

### Phase 1: Enhanced Base Architecture (2-3h)

#### 1.1 Enhanced Base Seeder Class
```php
// database/seeders/Demo/Seeders/Enhanced/EnhancedDemoSubSeeder.php
abstract class EnhancedDemoSubSeeder implements DemoSubSeeder
{
    protected DemoScenarioConfig $scenarioConfig;
    protected ?PersonaContext $personaContext = null;
    
    public function __construct(
        protected readonly DemoContentGenerationService $contentGenerator,
        protected readonly DemoScenarioConfigService $configService,
        protected readonly TimelineGenerator $timeline = new TimelineGenerator()
    ) {}
    
    public function seed(DemoSeedContext $context): void
    {
        $this->initializeFromContext($context);
        
        try {
            $content = $this->generateContent();
            $this->createFragments($content, $context);
        } catch (Exception $e) {
            $this->handleGenerationError($e, $context);
        }
        
        $this->postSeedActions($context);
    }
    
    public function cleanup(DemoSeedContext $context): void
    {
        // Default cleanup implementation - can be overridden
        $this->cleanupFragmentsByDemoFlag($context);
    }
    
    protected function initializeFromContext(DemoSeedContext $context): void
    {
        $this->scenarioConfig = $context->get('scenario_config', 'current');
        if (!$this->scenarioConfig) {
            throw new InvalidArgumentException('Scenario configuration not found in context');
        }
        
        $persona = $this->scenarioConfig->getScenarioInfo()->getPersona();
        $this->personaContext = app(PersonaConsistencyEngine::class)->generatePersonaContext($persona);
    }
    
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
    
    abstract protected function generateContent(): Collection;
    abstract protected function createFragments(Collection $content, DemoSeedContext $context): void;
    
    protected function handleGenerationError(Exception $e, DemoSeedContext $context): void
    {
        Log::error('Enhanced seeder generation failed', [
            'seeder' => static::class,
            'error' => $e->getMessage(),
            'scenario' => $this->scenarioConfig->getScenarioInfo()->getName(),
        ]);
        
        throw new SeederGenerationException("Failed to generate content for " . static::class, 0, $e);
    }
    
    protected function postSeedActions(DemoSeedContext $context): void
    {
        $context->info('<info>✔</info> ' . static::class . ' completed');
    }
}
```

#### 1.2 Enhanced DemoSeedContext
```php
// database/seeders/Demo/Support/EnhancedDemoSeedContext.php
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
        $vaultDistribution = $this->getScenarioConfig()
            ?->getContentGeneration()
            ?->getVaultDistribution($contentType);
        
        if (!$vaultDistribution) {
            // Fallback to existing round-robin logic
            $vaultKey = $index % 2 === 0 ? 'work' : 'personal';
            return $this->get('vaults', $vaultKey);
        }
        
        return $this->selectVaultByDistribution($vaultDistribution, $index);
    }
    
    private function selectVaultByDistribution(array $distribution, int $index): ?Vault
    {
        // Weighted selection based on distribution percentages
        $totalWeight = array_sum($distribution);
        $randomValue = ($index * 37) % $totalWeight; // Deterministic but distributed
        
        $currentWeight = 0;
        foreach ($distribution as $vaultSlug => $weight) {
            $currentWeight += $weight;
            if ($randomValue < $currentWeight) {
                return $this->get('vaults', $vaultSlug);
            }
        }
        
        // Fallback to first vault
        return $this->collection('vaults')->first();
    }
}
```

#### 1.3 Exception Classes
```php
// database/seeders/Demo/Exceptions/
SeederGenerationException.php
ScenarioConfigurationException.php
ContentIntegrationException.php
```

### Phase 2: Core Enhanced Seeders (4-5h)

#### 2.1 Enhanced Vault Seeder
```php
// database/seeders/Demo/Seeders/Enhanced/EnhancedVaultSeeder.php
class EnhancedVaultSeeder extends EnhancedDemoSubSeeder
{
    protected function generateContent(): Collection
    {
        // Vaults don't need AI generation - they come from configuration
        return $this->scenarioConfig->getVaults();
    }
    
    protected function createFragments(Collection $vaultConfigs, DemoSeedContext $context): void
    {
        foreach ($vaultConfigs as $vaultConfig) {
            $vault = Vault::create([
                'name' => $vaultConfig->getName(),
                'slug' => $vaultConfig->getSlug(),
                'description' => $vaultConfig->getDescription(),
                'metadata' => array_merge($vaultConfig->getMetadata(), [
                    'demo_seed' => true,
                    'scenario' => $this->scenarioConfig->getScenarioInfo()->getName(),
                    'created_by_enhanced_seeder' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $context->set('vaults', $vaultConfig->getSlug(), $vault);
        }
    }
    
    public function cleanup(DemoSeedContext $context): void
    {
        // Remove vaults created by enhanced seeder
        Vault::where('metadata->demo_seed', true)
            ->where('metadata->created_by_enhanced_seeder', true)
            ->each(function (Vault $vault) use ($context) {
                $context->forget('vaults', $vault->slug);
                $vault->delete();
            });
    }
}
```

#### 2.2 Enhanced Project Seeder
```php
// database/seeders/Demo/Seeders/Enhanced/EnhancedProjectSeeder.php
class EnhancedProjectSeeder extends EnhancedDemoSubSeeder
{
    protected function generateContent(): Collection
    {
        // Projects come from configuration, don't need AI generation
        return collect($this->scenarioConfig->getProjects());
    }
    
    protected function createFragments(Collection $projectsByVault, DemoSeedContext $context): void
    {
        foreach ($projectsByVault as $vaultSlug => $projects) {
            $vault = $context->get('vaults', $vaultSlug);
            
            if (!$vault) {
                Log::warning("Vault '{$vaultSlug}' not found for projects", ['vault' => $vaultSlug]);
                continue;
            }
            
            foreach ($projects as $projectConfig) {
                $project = Project::create([
                    'name' => $projectConfig->getName(),
                    'description' => $projectConfig->getDescription(),
                    'status' => $projectConfig->getStatus(),
                    'vault_id' => $vault->id,
                    'metadata' => [
                        'demo_seed' => true,
                        'scenario' => $this->scenarioConfig->getScenarioInfo()->getName(),
                        'project_type' => $projectConfig->getType() ?? 'general',
                    ],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $context->set('projects', "{$vaultSlug}.{$projectConfig->getSlug()}", $project);
            }
        }
    }
    
    public function cleanup(DemoSeedContext $context): void
    {
        Project::where('metadata->demo_seed', true)->each(function (Project $project) use ($context) {
            // Remove from context
            $projectKey = $this->findProjectKey($project, $context);
            if ($projectKey) {
                $context->forget('projects', $projectKey);
            }
            
            $project->delete();
        });
    }
}
```

#### 2.3 Enhanced Todo Seeder
```php
// database/seeders/Demo/Seeders/Enhanced/EnhancedTodoSeeder.php
class EnhancedTodoSeeder extends EnhancedDemoSubSeeder
{
    private const DEMO_FLAG = 'demo_seed';
    
    protected function generateContent(): Collection
    {
        $todoConfig = $this->scenarioConfig->getContentGeneration()->getTodos();
        
        return $this->generateContentWithFallback(
            fn() => $this->contentGenerator->generateTodos($todoConfig),
            fn() => $this->generateFallbackTodos($todoConfig)
        );
    }
    
    protected function createFragments(Collection $todos, DemoSeedContext $context): void
    {
        $dates = $this->timeline->generate($todos->count());
        
        $todos->zip($dates)->each(function ($pair, $index) use ($context) {
            [$todoContent, $timestamp] = $pair;
            
            $vault = $this->selectVaultForTodo($todoContent, $context, $index);
            $project = $this->selectProjectForTodo($todoContent, $vault, $context);
            
            $fragmentData = $todoContent->toFragmentData();
            $fragmentData['vault'] = $vault?->slug ?? 'personal';
            $fragmentData['project_id'] = $project?->id;
            $fragmentData['created_at'] = $timestamp;
            $fragmentData['updated_at'] = $timestamp;
            $fragmentData['inbox_at'] = $timestamp;
            
            $fragment = Fragment::create($fragmentData);
            
            // Create associated Todo model
            Model::unguarded(function () use ($fragment, $todoContent) {
                Todo::create([
                    'fragment_id' => $fragment->id,
                    'title' => $todoContent->getTitle(),
                    'state' => $todoContent->getState(),
                ]);
            });
            
            $context->set('todo_fragments', (string) $fragment->id, $fragment);
        });
    }
    
    private function selectVaultForTodo(TodoContent $todoContent, DemoSeedContext $context, int $index): ?Vault
    {
        // Use vault from content if specified, otherwise use distribution
        if ($todoContent->getVault()) {
            return $context->get('vaults', $todoContent->getVault());
        }
        
        return $context->getVaultForContent('todos', $index);
    }
    
    private function selectProjectForTodo(TodoContent $todoContent, ?Vault $vault, DemoSeedContext $context): ?Project
    {
        if (!$vault) {
            return null;
        }
        
        // Find projects for this vault
        $vaultProjects = $context->collection('projects')
            ->filter(fn ($project) => $project->vault_id === $vault->id);
        
        if ($vaultProjects->isEmpty()) {
            return null;
        }
        
        // Select project based on content context or randomly
        return $vaultProjects->random();
    }
    
    private function generateFallbackTodos(TodoGenerationConfig $config): Collection
    {
        // Use enhanced template service for realistic fallback content
        return app(ContentTemplateService::class)->generateRealisticTodos($config);
    }
    
    public function cleanup(DemoSeedContext $context): void
    {
        Fragment::with('todo')
            ->where('metadata->'.self::DEMO_FLAG, true)
            ->where('metadata->demo_category', 'todo')
            ->get()
            ->each(function (Fragment $fragment) use ($context) {
                $fragment->todo?->delete();
                $fragment->forceDelete();
                $context->forget('todo_fragments', (string) $fragment->id);
            });
    }
}
```

#### 2.4 Enhanced Contact Seeder
```php
// database/seeders/Demo/Seeders/Enhanced/EnhancedContactSeeder.php
class EnhancedContactSeeder extends EnhancedDemoSubSeeder
{
    protected function generateContent(): Collection
    {
        $contactConfig = $this->scenarioConfig->getContentGeneration()->getContacts();
        
        return $this->generateContentWithFallback(
            fn() => $this->contentGenerator->generateContacts($contactConfig),
            fn() => $this->generateFallbackContacts($contactConfig)
        );
    }
    
    protected function createFragments(Collection $contacts, DemoSeedContext $context): void
    {
        $dates = $this->timeline->generate($contacts->count());
        
        $contacts->zip($dates)->each(function ($pair, $index) use ($context) {
            [$contactContent, $timestamp] = $pair;
            
            // Create contact record
            $contact = Contact::create([
                'name' => $contactContent->getName(),
                'role' => $contactContent->getRole(),
                'company' => $contactContent->getCompany(),
                'email' => $contactContent->getEmail(),
                'phone' => $contactContent->getPhone(),
                'metadata' => [
                    'demo_seed' => true,
                    'relationship' => $contactContent->getRelationship(),
                    'scenario' => $this->scenarioConfig->getScenarioInfo()->getName(),
                ],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
            
            // Create fragment for contact (especially if has meeting notes)
            if ($contactContent->getMeetingNotes()) {
                $fragment = Fragment::create([
                    'type' => 'contact',
                    'title' => "Meeting with {$contactContent->getName()}",
                    'message' => $contactContent->getMeetingNotes(),
                    'tags' => $contactContent->getTags(),
                    'metadata' => [
                        'demo_seed' => true,
                        'demo_category' => 'contact',
                        'contact_id' => $contact->id,
                        'has_meeting_notes' => true,
                    ],
                    'vault' => $this->selectVaultForContact($contactContent, $context, $index)?->slug,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
                
                $context->set('contact_fragments', (string) $fragment->id, $fragment);
            }
            
            $context->set('contacts', (string) $contact->id, $contact);
        });
    }
    
    private function selectVaultForContact(ContactContent $contactContent, DemoSeedContext $context, int $index): ?Vault
    {
        // Business contacts usually go to work vault, personal contacts to personal vault
        $relationship = $contactContent->getRelationship();
        
        if (in_array($relationship, ['colleague', 'client', 'vendor', 'professional'])) {
            return $context->get('vaults', 'work');
        }
        
        return $context->get('vaults', 'personal');
    }
    
    private function generateFallbackContacts(ContactGenerationConfig $config): Collection
    {
        return app(ContentTemplateService::class)->generateRealisticContacts($config);
    }
    
    public function cleanup(DemoSeedContext $context): void
    {
        // Clean up contact fragments
        Fragment::where('metadata->demo_seed', true)
            ->where('metadata->demo_category', 'contact')
            ->get()
            ->each(function (Fragment $fragment) use ($context) {
                $fragment->forceDelete();
                $context->forget('contact_fragments', (string) $fragment->id);
            });
        
        // Clean up contact records
        Contact::where('metadata->demo_seed', true)
            ->get()
            ->each(function (Contact $contact) use ($context) {
                $context->forget('contacts', (string) $contact->id);
                $contact->delete();
            });
    }
}
```

### Phase 3: Additional Enhanced Seeders (2-3h)

#### 3.1 Enhanced Chat Seeder
```php
// database/seeders/Demo/Seeders/Enhanced/EnhancedChatSeeder.php
class EnhancedChatSeeder extends EnhancedDemoSubSeeder
{
    protected function generateContent(): Collection
    {
        $chatConfig = $this->scenarioConfig->getContentGeneration()->getChats();
        
        return $this->generateContentWithFallback(
            fn() => $this->contentGenerator->generateChatMessages($chatConfig),
            fn() => $this->generateFallbackChats($chatConfig)
        );
    }
    
    protected function createFragments(Collection $chatSessions, DemoSeedContext $context): void
    {
        $dates = $this->timeline->generate($chatSessions->count());
        
        $chatSessions->zip($dates)->each(function ($pair, $index) use ($context) {
            [$chatContent, $timestamp] = $pair;
            
            // Create chat session
            $session = ChatSession::create([
                'title' => $chatContent->getTopic(),
                'messages' => $chatContent->getMessages(),
                'message_count' => count($chatContent->getMessages()),
                'last_activity_at' => $timestamp,
                'metadata' => [
                    'demo_seed' => true,
                    'scenario' => $this->scenarioConfig->getScenarioInfo()->getName(),
                ],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
            
            // Create fragments for each message
            foreach ($chatContent->getMessages() as $messageIndex => $message) {
                $messageTimestamp = $timestamp->copy()->addMinutes($messageIndex * 2);
                
                $fragment = Fragment::create([
                    'type' => 'chat_message',
                    'title' => "Chat: {$chatContent->getTopic()}",
                    'message' => $message['content'],
                    'tags' => ['demo', 'chat'],
                    'metadata' => [
                        'demo_seed' => true,
                        'demo_category' => 'chat_message',
                        'chat_session_id' => $session->id,
                        'message_index' => $messageIndex,
                    ],
                    'created_at' => $messageTimestamp,
                    'updated_at' => $messageTimestamp,
                ]);
                
                $context->set('chat_fragments', (string) $fragment->id, $fragment);
            }
            
            $context->set('chat_sessions', (string) $session->id, $session);
        });
    }
    
    private function generateFallbackChats(ChatGenerationConfig $config): Collection
    {
        return app(ContentTemplateService::class)->generateRealisticChats($config);
    }
    
    public function cleanup(DemoSeedContext $context): void
    {
        // Clean up chat message fragments
        Fragment::where('metadata->demo_seed', true)
            ->where('metadata->demo_category', 'chat_message')
            ->get()
            ->each(function (Fragment $fragment) use ($context) {
                $fragment->forceDelete();
                $context->forget('chat_fragments', (string) $fragment->id);
            });
        
        // Clean up chat sessions
        ChatSession::where('metadata->demo_seed', true)
            ->get()
            ->each(function (ChatSession $session) use ($context) {
                $context->forget('chat_sessions', (string) $session->id);
                $session->delete();
            });
    }
}
```

#### 3.2 Enhanced Type Seeder & User Seeder
```php
// Enhanced versions that maintain existing functionality
// but add scenario context and enhanced metadata

class EnhancedTypeSeeder extends EnhancedDemoSubSeeder { /* Existing + scenario metadata */ }
class EnhancedUserSeeder extends EnhancedDemoSubSeeder { /* Existing + scenario context */ }
```

### Phase 4: Integration & Main Seeder (1-2h)

#### 4.1 Enhanced Demo Data Seeder
```php
// database/seeders/Demo/EnhancedDemoDataSeeder.php
class EnhancedDemoDataSeeder extends Seeder
{
    private array $enhancedSeeders;
    
    public function __construct(
        private readonly DemoScenarioConfigService $configService,
        private readonly DemoContentGenerationService $contentGenerator
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
        if (!$this->shouldRun()) {
            $this->command?->warn('EnhancedDemoDataSeeder skipped: run in local/testing or enable app.seed_demo_data.');
            return;
        }
        
        $scenarioName = config('fragments.demo_seeder.default_scenario', 'general');
        
        try {
            $scenarioConfig = $this->configService->loadScenario($scenarioName);
        } catch (ScenarioNotFoundException $e) {
            $this->command?->error("Scenario '{$scenarioName}' not found. Using fallback.");
            $this->runFallbackSeeder();
            return;
        }
        
        $context = new EnhancedDemoSeedContext($this->command?->getOutput());
        $context->setScenarioConfig($scenarioConfig);
        
        // Cleanup phase
        foreach (array_reverse($this->enhancedSeeders) as $seeder) {
            $seeder->cleanup($context);
        }
        
        // Seeding phase
        foreach ($this->enhancedSeeders as $seeder) {
            $seeder->seed($context);
        }
        
        $context->info('<comment>Enhanced demo dataset seeded successfully.</comment>');
        $this->logScenarioSummary($scenarioConfig, $context);
    }
    
    private function shouldRun(): bool
    {
        return app()->environment(['local', 'development', 'testing']) || config('app.seed_demo_data', false);
    }
    
    private function runFallbackSeeder(): void
    {
        // Run original DemoDataSeeder as fallback
        $fallbackSeeder = new DemoDataSeeder();
        $fallbackSeeder->run();
    }
    
    private function logScenarioSummary(DemoScenarioConfig $scenarioConfig, EnhancedDemoSeedContext $context): void
    {
        $summary = [
            'scenario' => $scenarioConfig->getScenarioInfo()->getName(),
            'persona' => $scenarioConfig->getScenarioInfo()->getPersona(),
            'vaults' => $context->collection('vaults')->count(),
            'projects' => $context->collection('projects')->count(),
            'todo_fragments' => $context->collection('todo_fragments')->count(),
            'contact_fragments' => $context->collection('contact_fragments')->count(),
            'chat_fragments' => $context->collection('chat_fragments')->count(),
        ];
        
        Log::info('Enhanced demo data seeded', $summary);
    }
}
```

#### 4.2 Service Provider Integration
```php
// app/Providers/EnhancedDemoSeederServiceProvider.php
class EnhancedDemoSeederServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind enhanced seeders in container
        $this->app->bind(EnhancedDemoDataSeeder::class);
        
        // Register enhanced seeders when demo environment
        if ($this->app->environment(['local', 'testing'])) {
            $this->app->extend(DatabaseSeeder::class, function ($seeder, $app) {
                $seeder->addEnhancedDemoSeeder($app->make(EnhancedDemoDataSeeder::class));
                return $seeder;
            });
        }
    }
}
```

## Testing Strategy

### Unit Tests
```php
// tests/Unit/Seeders/Enhanced/EnhancedTodoSeederTest.php
test('generates content from scenario configuration')
test('falls back to template generation when AI fails')
test('creates fragments with correct timeline distribution')
test('maintains context sharing with other seeders')

// tests/Unit/Seeders/Enhanced/EnhancedDemoSeedContextTest.php
test('manages scenario configuration correctly')
test('provides vault selection based on distribution')
test('maintains backwards compatibility with existing context')
```

### Integration Tests
```php
// tests/Feature/Seeders/EnhancedDemoDataSeederTest.php
test('seeds complete scenario successfully')
test('integrates with AI content generation')
test('falls back gracefully when scenario missing')
test('maintains existing data volumes and patterns')
```

### Content Quality Tests
```php
// tests/Feature/Seeders/ContentQualityTest.php
test('generated content is significantly more realistic than faker')
test('scenario persona is maintained across all content')
test('fragment relationships work correctly')
test('temporal distribution matches expected patterns')
```

## Quality Assurance

### Code Quality
- [ ] PSR-12 compliance with Pint
- [ ] Type declarations for all methods
- [ ] Comprehensive error handling
- [ ] Backwards compatibility maintained

### Integration Quality
- [ ] Seamless integration with YAML configurations
- [ ] AI content generation works correctly
- [ ] Fallback systems function properly
- [ ] Context sharing maintains existing patterns

### Performance Requirements
- [ ] Seeding time remains under 2 minutes
- [ ] Memory usage stays within limits
- [ ] Error handling doesn't impact performance
- [ ] Large dataset handling optimized

## Delivery Checklist

### Enhanced Seeder Components
- [ ] `EnhancedDemoSubSeeder` base class with common functionality
- [ ] Enhanced versions of all existing seeders (Vault, Project, Todo, Contact, Chat, Type, User)
- [ ] `EnhancedDemoSeedContext` with scenario configuration support
- [ ] Error handling and fallback strategies

### Integration Components
- [ ] `EnhancedDemoDataSeeder` main seeder with scenario loading
- [ ] Service provider registration
- [ ] Configuration integration
- [ ] Migration utilities for existing projects

### Testing & Documentation
- [ ] Comprehensive test suite covering all enhanced seeders
- [ ] Integration testing with AI content generation
- [ ] Performance benchmarking
- [ ] Documentation for enhanced seeder usage

## Success Validation

### Functional Testing
```bash
# Test enhanced seeders with different scenarios
php artisan db:seed --class=EnhancedDemoDataSeeder
php artisan demo:seed --scenario=writer --enhanced
php artisan demo:seed --scenario=developer --enhanced

# Validate generated content quality
php artisan demo:validate-scenario general --enhanced --check-realism
php artisan demo:validate-scenario writer --enhanced --check-persona

# Test fallback systems
php artisan demo:seed --scenario=nonexistent --enhanced # Should fallback gracefully
```

### Quality Gates
- [ ] All unit tests pass (>95% coverage)
- [ ] Integration tests with AI generation pass
- [ ] Content quality significantly improved over faker
- [ ] Performance benchmarks met
- [ ] Backwards compatibility maintained

This enhanced seeder system provides the crucial integration layer that connects YAML scenario configurations and AI-generated content with the existing demo data infrastructure, creating realistic, scenario-driven demo data while maintaining full compatibility and performance characteristics.