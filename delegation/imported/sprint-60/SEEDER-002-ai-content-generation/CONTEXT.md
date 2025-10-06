# SEEDER-002: AI Content Generation Agent — Context

## Current Demo Content Analysis

### Existing Content Generation Patterns
The current demo seeders use basic faker-generated content:

```php
// TodoSeeder.php - Current approach
$title = Str::headline($faker->unique()->sentence(4));
$message = 'TODO: '.$title.' - '.$faker->sentence(6);

// Results in generic content like:
"TODO: Voluptas Eius Aut Consectetur - Lorem ipsum dolor sit amet consectetur."
```

**Problems with Current Approach:**
- Generic, unrealistic content that doesn't demonstrate real usage
- No persona consistency or contextual awareness  
- Limited content variety and repetitive patterns
- No relationship between generated content items
- Missing real-world specificity and actionable details

### Target Content Quality Examples

**Current Generic Content:**
```
TODO: "Voluptas Eius Aut Consectetur"
Contact: "Dr. Janet Smith" (generic faker name)
Chat: "Lorem ipsum dolor sit amet"
```

**Target Realistic Content:**
```
TODO: "Pick up dry cleaning from Main Street cleaners by 6pm"
Contact: "Sarah Chen - DevOps Lead at TechCorp" (with realistic meeting notes)
Chat: "Planning weekend home lab work - need to configure the new router"
```

## AI Provider Integration Strategy

### Existing AI Infrastructure
The application already has a comprehensive AI provider system in `config/fragments.php`:

```php
// Current AI configuration structure
'models' => [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'default_text_model' => env('AI_DEFAULT_TEXT_MODEL', 'gpt-4o-mini'),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'ollama'),
    
    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'text_models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo'],
            'config_keys' => ['OPENAI_API_KEY'],
        ],
        'anthropic' => [...],
        'ollama' => [...],
    ],
]
```

### Demo Seeder AI Configuration Extension
Extend the existing configuration to support demo-specific AI settings:

```php
// config/fragments.php extension
'demo_seeder' => [
    'ai_generation' => [
        'provider' => env('DEMO_AI_PROVIDER'), // null = use default
        'model' => env('DEMO_AI_MODEL'),       // null = use default
        'temperature' => env('DEMO_AI_TEMPERATURE', 0.7),
        'max_tokens' => env('DEMO_AI_MAX_TOKENS', 500),
        'batch_size' => env('DEMO_AI_BATCH_SIZE', 10),
        'retry_attempts' => env('DEMO_AI_RETRY_ATTEMPTS', 3),
    ],
]
```

### Integration with Existing AI Services
Build on existing AI service patterns:

```php
// Existing pattern (from fragments system)
app('ai.provider')->generateText($prompt, $options);

// Demo seeder integration
class DemoContentGenerationService
{
    public function __construct(
        private AiProviderService $aiProvider,
        private DemoScenarioConfig $scenarioConfig
    ) {}
    
    private function generateWithProvider(string $prompt, array $options = []): string
    {
        $provider = $options['provider'] ?? $this->getDefaultProvider();
        $model = $options['model'] ?? $this->getDefaultModel();
        
        return $this->aiProvider->generateText($prompt, [
            'provider' => $provider,
            'model' => $model,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 500,
        ]);
    }
}
```

## Content Generation Architecture

### Scenario-Driven Content Strategy
Content generation will be driven by YAML scenario configurations:

```yaml
# From SEEDER-001 YAML configs
scenario:
  persona: "Busy professional with side projects and personal tasks"

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

ai_generation_rules:
  - "Use realistic, specific content rather than generic placeholders"
  - "Create natural relationships between related fragments"
  - "Use consistent persona voice across all content"
```

### Content Generation Flow
```
1. Load scenario configuration (SEEDER-001)
2. Extract persona and content rules
3. Generate AI prompts with context and examples
4. Request content from AI provider
5. Parse and validate generated content
6. Apply persona consistency checks
7. Create Fragment-compatible content objects
8. Return to enhanced seeders (SEEDER-003)
```

### Content Types & Generation Strategies

#### Todo Generation Strategy
```php
class TodoGenerator
{
    public function generateTodos(TodoGenerationConfig $config): Collection
    {
        $prompt = $this->buildTodoPrompt($config);
        $batchSize = $config->getBatchSize();
        
        $todos = collect();
        for ($i = 0; $i < $config->getCount(); $i += $batchSize) {
            $batch = $this->generateTodoBatch($prompt, min($batchSize, $config->getCount() - $i));
            $todos = $todos->merge($batch);
        }
        
        return $todos;
    }
    
    private function buildTodoPrompt(TodoGenerationConfig $config): string
    {
        $persona = $config->getPersona();
        $vault = $config->getVault();
        $examples = $config->getRealisticExamples();
        
        return "You are a {$persona}. Generate realistic {$vault} todos that are specific and actionable.
                
                Examples of good todos:
                " . implode("\n", $examples) . "
                
                Requirements:
                - Each todo must be specific with real places, people, or tasks
                - Include realistic timeframes and deadlines where appropriate
                - Use consistent voice matching the persona
                - Avoid generic placeholder content
                
                Generate {$config->getBatchSize()} realistic {$vault} todos:";
    }
}
```

#### Chat Generation Strategy
```php
class ChatGenerator
{
    public function generateChatSessions(ChatGenerationConfig $config): Collection
    {
        $sessions = collect();
        
        foreach ($config->getTopics() as $topic) {
            $conversation = $this->generateConversation($topic, $config);
            $sessions->push($conversation);
        }
        
        return $sessions;
    }
    
    private function generateConversation(string $topic, ChatGenerationConfig $config): ChatContent
    {
        $persona = $config->getPersona();
        $messageCount = $config->getMessagesPerChat();
        
        $prompt = "You are a {$persona}. Create a realistic chat conversation about: {$topic}
                  
                  Generate {$messageCount} messages that form a natural conversation flow.
                  Include realistic context, questions, and responses.
                  Use consistent voice and terminology throughout.
                  
                  Format as JSON array of messages with 'content' and 'timestamp' fields.";
        
        $response = $this->aiProvider->generateText($prompt);
        return $this->parseConversationResponse($response, $topic);
    }
}
```

#### Contact Generation Strategy
```php
class ContactGenerator
{
    public function generateContacts(ContactGenerationConfig $config): Collection
    {
        $contacts = collect();
        
        foreach ($config->getRealisticExamples() as $example) {
            $contact = $this->generateDetailedContact($example, $config);
            $contacts->push($contact);
        }
        
        // Generate additional contacts if needed
        $remaining = $config->getCount() - $contacts->count();
        if ($remaining > 0) {
            $additionalContacts = $this->generateAdditionalContacts($remaining, $config);
            $contacts = $contacts->merge($additionalContacts);
        }
        
        return $contacts;
    }
    
    private function generateMeetingNotes(ContactContent $contact, ContactGenerationConfig $config): ?string
    {
        if (!$config->shouldIncludeMeetingNotes()) {
            return null;
        }
        
        $persona = $config->getPersona();
        $relationship = $contact->getRelationship();
        
        $prompt = "You are a {$persona} who recently met with {$contact->getName()} ({$contact->getRole()}).
                  
                  Generate realistic meeting notes that include:
                  - Meeting purpose and context
                  - Key discussion points
                  - Action items and next steps
                  - Realistic follow-up plans
                  
                  Keep it concise but specific, matching a {$relationship} relationship.";
        
        return $this->aiProvider->generateText($prompt);
    }
}
```

## Persona Consistency Strategy

### Persona Context Engine
Maintain consistent voice and priorities across all generated content:

```php
class PersonaConsistencyEngine
{
    private array $personaPatterns = [
        'busy_professional' => [
            'voice_traits' => ['efficient', 'goal-oriented', 'time-conscious'],
            'vocabulary' => ['action items', 'follow up', 'deadline', 'priority'],
            'time_patterns' => ['business_hours_focused', 'weekend_personal'],
        ],
        'content_creator' => [
            'voice_traits' => ['creative', 'deadline-driven', 'client-focused'],
            'vocabulary' => ['content calendar', 'editorial', 'publish', 'draft'],
            'time_patterns' => ['flexible_schedule', 'client_deadlines'],
        ],
    ];
    
    public function applyPersonaConsistency(string $content, string $persona): string
    {
        $patterns = $this->getPersonaPatterns($persona);
        
        // Apply vocabulary preferences
        $content = $this->enhanceVocabulary($content, $patterns['vocabulary']);
        
        // Apply voice consistency
        $content = $this->adjustTone($content, $patterns['voice_traits']);
        
        return $content;
    }
}
```

### Content Quality Validation
Ensure generated content meets quality standards:

```php
class ContentQualityValidator
{
    public function validateTodo(string $todo): ValidationResult
    {
        $checks = [
            'specificity' => $this->checkSpecificity($todo),
            'actionability' => $this->checkActionability($todo),
            'realism' => $this->checkRealism($todo),
            'persona_consistency' => $this->checkPersonaConsistency($todo),
        ];
        
        return new ValidationResult($checks);
    }
    
    private function checkSpecificity(string $todo): bool
    {
        // Check for specific places, people, times, or tasks
        $specificPatterns = [
            '/\b\d{1,2}(:\d{2})?\s*(am|pm)\b/i', // specific times
            '/\b(at|from|to)\s+[A-Z][a-z]+/i',   // specific places
            '/\bwith\s+[A-Z][a-z]+/i',           // specific people
        ];
        
        foreach ($specificPatterns as $pattern) {
            if (preg_match($pattern, $todo)) {
                return true;
            }
        }
        
        return false;
    }
}
```

## Integration Requirements

### Current Seeder Integration
The AI content generation system must integrate seamlessly with existing demo seeders:

```php
// Enhanced TodoSeeder with AI integration
class TodoSeeder implements DemoSubSeeder
{
    public function __construct(
        private readonly TimelineGenerator $timeline = new TimelineGenerator,
        private readonly DemoContentGenerationService $contentGenerator = null
    ) {}
    
    public function seed(DemoSeedContext $context): void
    {
        // Get scenario configuration from context
        $scenarioConfig = $context->get('scenario_config');
        
        if ($this->contentGenerator && $scenarioConfig) {
            // Use AI-generated content
            $todos = $this->contentGenerator->generateTodos(
                $scenarioConfig->getContentGeneration()->getTodos()
            );
        } else {
            // Fallback to existing faker content
            $todos = $this->generateFakerTodos();
        }
        
        // Continue with existing seeding logic
        $this->createFragmentsFromTodos($todos, $context);
    }
}
```

### Fragment Model Compatibility
Generated content must be compatible with existing Fragment model structure:

```php
// Content objects must provide Fragment-compatible data
class TodoContent
{
    public function toFragmentData(): array
    {
        return [
            'type' => 'todo',
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'tags' => $this->getTags(),
            'metadata' => [
                'demo_seed' => true,
                'demo_category' => 'todo',
                'ai_generated' => true,
                'persona' => $this->getPersona(),
            ],
            'state' => $this->getState(),
        ];
    }
}
```

## Error Handling & Fallback Strategy

### AI Provider Failures
Handle AI provider issues gracefully:

```php
class DemoContentGenerationService
{
    public function generateTodos(TodoGenerationConfig $config): Collection
    {
        try {
            return $this->generateWithAI($config);
        } catch (AiProviderException $e) {
            Log::warning('AI generation failed, falling back to templates', [
                'error' => $e->getMessage(),
                'config' => $config->toArray(),
            ]);
            
            return $this->generateWithTemplates($config);
        }
    }
    
    private function generateWithTemplates(TodoGenerationConfig $config): Collection
    {
        // Use enhanced faker with realistic templates
        return $this->templateService->generateRealisticTodos($config);
    }
}
```

### Rate Limiting & Batching
Manage AI provider rate limits efficiently:

```php
class AiContentBatcher
{
    public function batchGenerate(array $prompts, array $options = []): Collection
    {
        $batchSize = $options['batch_size'] ?? 10;
        $delay = $options['delay_ms'] ?? 1000;
        
        $results = collect();
        $batches = array_chunk($prompts, $batchSize);
        
        foreach ($batches as $batch) {
            $batchResults = $this->processPromptBatch($batch, $options);
            $results = $results->merge($batchResults);
            
            if (count($batches) > 1) {
                usleep($delay * 1000); // Rate limiting delay
            }
        }
        
        return $results;
    }
}
```

## Success Criteria

### Content Quality Benchmarks
- **Specificity**: 90% of generated todos include specific times, places, or people
- **Actionability**: 95% of todos are actionable with clear next steps
- **Persona Consistency**: Content matches scenario persona voice and priorities
- **Realism**: Generated content feels authentic and realistic

### Performance Requirements
- **Generation Speed**: Complete scenario generation in <2 minutes
- **AI Provider Efficiency**: Optimize token usage for cost control
- **Error Recovery**: Graceful fallback for 100% of AI failures
- **Content Variety**: No repetitive or duplicate content patterns

This AI content generation system will provide the intelligence layer that transforms static demo data into dynamic, realistic content that truly demonstrates the application's capabilities in authentic usage scenarios.