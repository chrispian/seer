# SEEDER-002: AI Content Generation Agent — Implementation Plan

## Executive Summary

Build an AI-powered content generation system that creates realistic, contextually-aware demo data based on YAML scenario configurations. Replace generic faker content with authentic, persona-driven content using the existing AI provider infrastructure.

**Estimated Effort**: 12-16 hours  
**Priority**: High (Core intelligence layer)  
**Dependencies**: SEEDER-001 (YAML Configuration System)

## Implementation Phases

### Phase 1: Core AI Service Integration (4-5h)

#### 1.1 AI Provider Service Integration
```php
// app/Services/Demo/AI/DemoContentGenerationService.php
class DemoContentGenerationService
{
    public function __construct(
        private AiProviderService $aiProvider,
        private DemoScenarioConfig $scenarioConfig,
        private ContentTemplateService $templateService
    ) {}
    
    public function generateTodos(TodoGenerationConfig $config): Collection
    public function generateChatMessages(ChatGenerationConfig $config): Collection
    public function generateContacts(ContactGenerationConfig $config): Collection
    public function generateReminders(ReminderGenerationConfig $config): Collection
    
    private function generateWithProvider(string $prompt, array $options): string
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

#### 1.2 Configuration Extension
```php
// Extend config/fragments.php
'demo_seeder' => [
    'ai_generation' => [
        'provider' => env('DEMO_AI_PROVIDER'), // null = use default
        'model' => env('DEMO_AI_MODEL'),       // null = use default
        'temperature' => env('DEMO_AI_TEMPERATURE', 0.7),
        'max_tokens' => env('DEMO_AI_MAX_TOKENS', 500),
        'batch_size' => env('DEMO_AI_BATCH_SIZE', 10),
        'retry_attempts' => env('DEMO_AI_RETRY_ATTEMPTS', 3),
        'fallback_enabled' => env('DEMO_AI_FALLBACK_ENABLED', true),
    ],
],
```

#### 1.3 Content Value Objects
```php
// app/Services/Demo/AI/Content/TodoContent.php
class TodoContent
{
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly array $tags,
        public readonly string $vault,
        public readonly ?string $project,
        public readonly array $state
    ) {}
    
    public function toFragmentData(): array
    {
        return [
            'type' => 'todo',
            'title' => $this->title,
            'message' => $this->message,
            'tags' => $this->tags,
            'state' => $this->state,
            'vault' => $this->vault,
            'project_id' => $this->project,
            'metadata' => [
                'demo_seed' => true,
                'demo_category' => 'todo',
                'ai_generated' => true,
            ],
        ];
    }
}

// Similar for ChatContent, ContactContent, ReminderContent
```

#### 1.4 Error Handling & Fallback
```php
// app/Services/Demo/AI/Exceptions/
AiGenerationException.php
AiProviderUnavailableException.php
ContentValidationException.php

// Fallback strategy
class AiFallbackService
{
    public function generateFallbackTodos(TodoGenerationConfig $config): Collection
    {
        // Enhanced faker with realistic templates based on scenario
        return $this->templateService->generateRealisticTodos($config);
    }
}
```

### Phase 2: Content Generation Strategies (4-5h)

#### 2.1 Todo Generation Engine
```php
// app/Services/Demo/AI/Generators/TodoGenerator.php
class TodoGenerator
{
    public function generateTodos(TodoGenerationConfig $config): Collection
    {
        $batchSize = $config->getBatchSize();
        $totalCount = $config->getCount();
        
        $todos = collect();
        
        for ($i = 0; $i < $totalCount; $i += $batchSize) {
            $currentBatch = min($batchSize, $totalCount - $i);
            $prompt = $this->buildTodoPrompt($config, $currentBatch);
            
            try {
                $response = $this->aiProvider->generateText($prompt, $config->getAiOptions());
                $batchTodos = $this->parseTodoResponse($response, $config);
                $todos = $todos->merge($batchTodos);
            } catch (AiGenerationException $e) {
                $fallbackTodos = $this->fallbackService->generateFallbackTodos($config->withCount($currentBatch));
                $todos = $todos->merge($fallbackTodos);
            }
        }
        
        return $todos;
    }
    
    private function buildTodoPrompt(TodoGenerationConfig $config, int $count): string
    {
        $persona = $config->getPersona();
        $vault = $config->getVault();
        $examples = $config->getRealisticExamples();
        $rules = $config->getAiGenerationRules();
        
        return "You are a {$persona}. Generate {$count} realistic {$vault} todos.
                
                Examples of excellent todos:
                " . implode("\n- ", $examples) . "
                
                Generation Rules:
                " . implode("\n- ", $rules) . "
                
                Requirements:
                - Each todo must be specific with real places, people, times, or tasks
                - Include realistic timeframes and deadlines where appropriate
                - Use consistent voice matching the persona
                - Avoid generic placeholder content
                - Format as JSON array with 'title', 'message', 'tags', 'priority', 'due_date' fields
                
                Generate {$count} realistic {$vault} todos:";
    }
    
    private function parseTodoResponse(string $response, TodoGenerationConfig $config): Collection
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            
            return collect($data)->map(function ($item) use ($config) {
                return new TodoContent(
                    title: $item['title'],
                    message: $item['message'] ?? "TODO: {$item['title']}",
                    tags: $item['tags'] ?? ['demo'],
                    vault: $config->getVault(),
                    project: $config->getProject(),
                    state: [
                        'status' => $item['status'] ?? 'open',
                        'priority' => $item['priority'] ?? 'medium',
                        'due_at' => $item['due_date'] ?? null,
                    ]
                );
            });
        } catch (JsonException $e) {
            throw new ContentValidationException("Invalid JSON response from AI provider: {$e->getMessage()}");
        }
    }
}
```

#### 2.2 Chat Generation Engine
```php
// app/Services/Demo/AI/Generators/ChatGenerator.php
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
        
        $prompt = "You are a {$persona} having a conversation about: {$topic}
                  
                  Generate a realistic chat conversation with {$messageCount} messages.
                  Create natural conversation flow with:
                  - Realistic context and background
                  - Natural questions and responses
                  - Consistent voice and terminology
                  - Progression toward resolution or next steps
                  
                  Format as JSON array with messages containing 'content', 'role' (user/assistant), 'timestamp' fields.
                  Use relative timestamps like '10 minutes ago', '2 hours ago' etc.";
        
        $response = $this->aiProvider->generateText($prompt, $config->getAiOptions());
        return $this->parseConversationResponse($response, $topic, $config);
    }
}
```

#### 2.3 Contact Generation Engine
```php
// app/Services/Demo/AI/Generators/ContactGenerator.php
class ContactGenerator
{
    public function generateContacts(ContactGenerationConfig $config): Collection
    {
        $contacts = collect();
        
        // Generate from realistic examples first
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
    
    private function generateDetailedContact(array $example, ContactGenerationConfig $config): ContactContent
    {
        $meetingNotes = null;
        if ($config->shouldIncludeMeetingNotes()) {
            $meetingNotes = $this->generateMeetingNotes($example, $config);
        }
        
        return new ContactContent(
            name: $example['name'],
            role: $example['role'] ?? null,
            company: $example['company'] ?? null,
            relationship: $example['relationship'] ?? 'professional',
            meetingNotes: $meetingNotes,
            tags: $this->generateContactTags($example, $config),
        );
    }
    
    private function generateMeetingNotes(array $contact, ContactGenerationConfig $config): string
    {
        $persona = $config->getPersona();
        $relationship = $contact['relationship'] ?? 'professional';
        
        $prompt = "You are a {$persona} who recently met with {$contact['name']} ({$contact['role']}).
                  
                  Generate realistic meeting notes that include:
                  - Meeting purpose and context
                  - Key discussion points  
                  - Action items and next steps
                  - Realistic follow-up plans
                  
                  Keep it concise but specific, matching a {$relationship} relationship.
                  Write in first person as meeting notes would be written.";
        
        return $this->aiProvider->generateText($prompt, $config->getAiOptions());
    }
}
```

### Phase 3: Persona Consistency System (2-3h)

#### 3.1 Persona Engine
```php
// app/Services/Demo/AI/PersonaConsistencyEngine.php
class PersonaConsistencyEngine
{
    private array $personaPatterns = [
        'busy_professional' => [
            'voice_traits' => ['efficient', 'goal-oriented', 'time-conscious'],
            'vocabulary' => ['action items', 'follow up', 'deadline', 'priority', 'deliverables'],
            'time_patterns' => 'business_hours_focused',
            'communication_style' => 'direct and professional',
        ],
        'content_creator' => [
            'voice_traits' => ['creative', 'deadline-driven', 'client-focused'],
            'vocabulary' => ['content calendar', 'editorial', 'publish', 'draft', 'engagement'],
            'time_patterns' => 'flexible_schedule',
            'communication_style' => 'creative and collaborative',
        ],
        'developer' => [
            'voice_traits' => ['analytical', 'problem-solving', 'technical'],
            'vocabulary' => ['deployment', 'refactor', 'documentation', 'testing', 'architecture'],
            'time_patterns' => 'sprint_focused',
            'communication_style' => 'technical and precise',
        ],
    ];
    
    public function generatePersonaContext(string $persona): PersonaContext
    {
        $patterns = $this->getPersonaPatterns($persona);
        
        return new PersonaContext([
            'voice_traits' => $patterns['voice_traits'],
            'preferred_vocabulary' => $patterns['vocabulary'],
            'time_management_style' => $patterns['time_patterns'],
            'communication_style' => $patterns['communication_style'],
        ]);
    }
    
    public function enhancePromptWithPersona(string $basePrompt, PersonaContext $context): string
    {
        $personaInstructions = "
            Voice and Style Guidelines:
            - Communicate in a {$context->getCommunicationStyle()} manner
            - Use vocabulary like: " . implode(', ', $context->getPreferredVocabulary()) . "
            - Embody these traits: " . implode(', ', $context->getVoiceTraits()) . "
            - Follow {$context->getTimeManagementStyle()} patterns
        ";
        
        return $basePrompt . "\n" . $personaInstructions;
    }
}
```

#### 3.2 Content Quality Validation
```php
// app/Services/Demo/AI/ContentQualityValidator.php
class ContentQualityValidator
{
    public function validateTodo(string $todo, PersonaContext $context): ValidationResult
    {
        $checks = [
            'specificity' => $this->checkSpecificity($todo),
            'actionability' => $this->checkActionability($todo),
            'realism' => $this->checkRealism($todo),
            'persona_consistency' => $this->checkPersonaConsistency($todo, $context),
        ];
        
        $score = array_sum($checks) / count($checks);
        
        return new ValidationResult($score >= 0.8, $checks);
    }
    
    private function checkSpecificity(string $todo): float
    {
        $specificPatterns = [
            '/\b\d{1,2}(:\d{2})?\s*(am|pm)\b/i', // specific times
            '/\b(at|from|to)\s+[A-Z][a-z]+/i',   // specific places
            '/\bwith\s+[A-Z][a-z]+\s+[A-Z][a-z]+/i', // specific people
            '/\b(by|before|after)\s+\w+day\b/i', // specific deadlines
        ];
        
        $matches = 0;
        foreach ($specificPatterns as $pattern) {
            if (preg_match($pattern, $todo)) {
                $matches++;
            }
        }
        
        return min(1.0, $matches * 0.3); // 0.3 per pattern match, max 1.0
    }
    
    private function checkActionability(string $todo): float
    {
        $actionPatterns = [
            '/^(pick up|schedule|buy|research|review|update|investigate|deploy|fix)/i',
            '/\b(call|email|meet|complete|finish|start)\b/i',
        ];
        
        foreach ($actionPatterns as $pattern) {
            if (preg_match($pattern, $todo)) {
                return 1.0;
            }
        }
        
        return 0.0;
    }
}
```

### Phase 4: Template & Fallback System (2-3h)

#### 4.1 Content Template Service
```php
// app/Services/Demo/AI/ContentTemplateService.php
class ContentTemplateService
{
    private array $todoTemplates = [
        'personal' => [
            'Pick up {item} from {location} by {time}',
            'Schedule {appointment_type} with {provider} for next week',
            'Buy {gift_type} for {person}\'s {occasion}',
            'Research {topic} for {project} project',
            'Call {person} about {topic}',
            'Pay {bill_type} bill before {date}',
        ],
        'work' => [
            'Review {document_type} with {team} team',
            'Update {system} documentation for {purpose}',
            'Investigate {issue_type} reported {timeframe}',
            'Schedule {meeting_type} for {project} project',
            'Deploy {feature} to {environment}',
            'Fix {bug_type} in {system}',
        ],
    ];
    
    private array $templateVariables = [
        'personal' => [
            'item' => ['dry cleaning', 'groceries', 'prescription', 'package', 'car'],
            'location' => ['Main Street cleaners', 'Safeway', 'CVS', 'post office', 'mechanic'],
            'appointment_type' => ['dentist appointment', 'doctor visit', 'haircut', 'oil change'],
            'provider' => ['Dr. Smith', 'Oak Dental', 'Auto Shop', 'hair salon'],
        ],
        'work' => [
            'document_type' => ['Q3 budget', 'project proposal', 'requirements doc', 'test plan'],
            'team' => ['finance', 'engineering', 'product', 'QA'],
            'system' => ['POS system', 'API', 'database', 'deployment pipeline'],
            'issue_type' => ['login problems', 'performance issues', 'data corruption'],
        ],
    ];
    
    public function generateRealisticTodos(TodoGenerationConfig $config): Collection
    {
        $vault = $config->getVault();
        $templates = $this->todoTemplates[$vault] ?? $this->todoTemplates['personal'];
        $variables = $this->templateVariables[$vault] ?? $this->templateVariables['personal'];
        
        $todos = collect();
        
        for ($i = 0; $i < $config->getCount(); $i++) {
            $template = $templates[array_rand($templates)];
            $todo = $this->fillTemplate($template, $variables);
            
            $todos->push(new TodoContent(
                title: $todo,
                message: "TODO: {$todo}",
                tags: ['demo', $vault],
                vault: $vault,
                project: $config->getProject(),
                state: [
                    'status' => ['open', 'in_progress'][array_rand(['open', 'in_progress'])],
                    'priority' => ['low', 'medium', 'high'][array_rand(['low', 'medium', 'high'])],
                    'due_at' => null,
                ]
            ));
        }
        
        return $todos;
    }
    
    private function fillTemplate(string $template, array $variables): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($variables) {
            $key = $matches[1];
            $options = $variables[$key] ?? [$key];
            return $options[array_rand($options)];
        }, $template);
    }
}
```

#### 4.2 Rate Limiting & Batching
```php
// app/Services/Demo/AI/AiContentBatcher.php
class AiContentBatcher
{
    public function batchGenerate(array $prompts, array $options = []): Collection
    {
        $batchSize = $options['batch_size'] ?? 10;
        $delay = $options['delay_ms'] ?? 1000;
        $maxRetries = $options['max_retries'] ?? 3;
        
        $results = collect();
        $batches = array_chunk($prompts, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            $retryCount = 0;
            
            while ($retryCount < $maxRetries) {
                try {
                    $batchResults = $this->processPromptBatch($batch, $options);
                    $results = $results->merge($batchResults);
                    break;
                } catch (AiProviderException $e) {
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        Log::warning('AI batch generation failed after retries', [
                            'batch_index' => $batchIndex,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                    
                    // Exponential backoff
                    usleep($delay * 1000 * pow(2, $retryCount - 1));
                }
            }
            
            // Rate limiting delay between batches
            if ($batchIndex < count($batches) - 1) {
                usleep($delay * 1000);
            }
        }
        
        return $results;
    }
}
```

## Testing Strategy

### Unit Tests
```php
// tests/Unit/Services/Demo/AI/DemoContentGenerationServiceTest.php
test('generates realistic todos with AI provider')
test('falls back to templates when AI provider fails')
test('maintains persona consistency across content')
test('validates generated content quality')

// tests/Unit/Services/Demo/AI/Generators/TodoGeneratorTest.php
test('builds appropriate prompts for different scenarios')
test('parses AI responses correctly')
test('handles invalid JSON responses gracefully')
test('applies content validation rules')
```

### Integration Tests
```php
// tests/Feature/Demo/AI/ContentGenerationIntegrationTest.php
test('generates complete scenario content successfully')
test('integrates with existing AI provider system')
test('handles rate limiting and batching correctly')
test('fallback system works when AI unavailable')
```

### Content Quality Tests
```php
// tests/Feature/Demo/AI/ContentQualityTest.php
test('generated todos are specific and actionable')
test('content matches scenario persona consistently')
test('no duplicate or repetitive content generated')
test('realistic examples meet quality standards')
```

## Quality Assurance

### Code Quality
- [ ] PSR-12 compliance with Pint
- [ ] Type declarations for all methods  
- [ ] Comprehensive error handling
- [ ] Performance optimization for AI calls

### Content Quality
- [ ] Specificity validation (90% include specific details)
- [ ] Actionability validation (95% are actionable)
- [ ] Persona consistency checks
- [ ] Realism verification

### Performance Requirements
- [ ] Complete scenario generation in <2 minutes
- [ ] Efficient token usage for cost control
- [ ] Graceful fallback for AI failures
- [ ] No repetitive or duplicate content

## Delivery Checklist

### Core AI Services
- [ ] `DemoContentGenerationService` with full AI integration
- [ ] Content generators for todos, chats, contacts, reminders
- [ ] AI provider integration using existing fragments.php system
- [ ] Error handling and fallback strategies

### Content Generation
- [ ] Realistic todo generation with specific examples
- [ ] Authentic chat conversation generation
- [ ] Contact generation with meeting notes
- [ ] Reminder and follow-up generation

### Quality & Consistency
- [ ] Persona consistency engine
- [ ] Content quality validation
- [ ] Template fallback system
- [ ] Rate limiting and batching

### Integration Components
- [ ] Service provider registration
- [ ] Configuration extension
- [ ] Error logging and monitoring
- [ ] Performance optimization

## Success Validation

### Functional Testing
```bash
# Test AI content generation
php artisan demo:generate-sample todos --scenario=general --count=10
php artisan demo:generate-sample chats --scenario=writer --count=5

# Validate content quality
php artisan demo:validate-content --scenario=general --type=todos
php artisan demo:validate-content --scenario=developer --type=all

# Test fallback system
php artisan demo:generate-sample todos --scenario=general --force-fallback
```

### Quality Gates
- [ ] All unit tests pass (>95% coverage)
- [ ] Content quality validation passes
- [ ] AI provider integration works correctly
- [ ] Fallback system functions properly
- [ ] Performance benchmarks met

This AI content generation system will provide the intelligence layer that transforms static demo data into dynamic, realistic content that authentically demonstrates the application's capabilities across different user scenarios and use cases.