# SEEDER-002: AI Content Generation Agent

## Agent Mission

You are an AI integration specialist and Laravel developer focused on creating intelligent content generation systems. Your mission is to build an AI-powered content generation agent that creates realistic, contextually-aware demo data based on YAML scenario configurations. You will transform generic faker content into authentic, persona-driven content that showcases real-world application usage.

## Core Objectives

### Primary Goal
Create a comprehensive AI content generation system that:
- Generates realistic todos, chat messages, contacts, and reminders
- Maintains persona consistency across all generated content
- Creates contextually appropriate content for different scenarios (general, writer, developer, productivity)
- Integrates with existing AI provider system from `config/fragments.php`
- Provides content templates and generation strategies

### Success Metrics
- [ ] Generate realistic todos like "Pick up dry cleaning from Main Street cleaners" instead of Lorem ipsum
- [ ] Create authentic chat conversations with natural flow and context
- [ ] Generate contacts with realistic meeting notes and relationship context
- [ ] Maintain consistent persona voice across all generated content
- [ ] Support multiple scenario types with appropriate content adaptation

## Technical Specifications

### AI Provider Integration
- **Use Existing System**: Integrate with `config/fragments.php` AI provider configuration
- **Default Model**: Use `fragments.models.default_provider` and `fragments.models.default_text_model`
- **Override Capability**: Support scenario-specific AI provider/model selection
- **Temperature Control**: Use configured temperature for content generation consistency

### Content Generation Architecture
```php
DemoContentGenerationService
├── generateTodos(TodoGenerationConfig $config): Collection<TodoContent>
├── generateChatMessages(ChatGenerationConfig $config): Collection<ChatContent>
├── generateContacts(ContactGenerationConfig $config): Collection<ContactContent>
├── generateReminders(ReminderGenerationConfig $config): Collection<ReminderContent>
└── generateMeetingNotes(ContactContent $contact): MeetingNoteContent

ContentGenerationStrategy
├── PersonaStrategy (maintains consistent voice)
├── ContextualStrategy (scenario-appropriate content)
├── RealisticStrategy (specific, actionable content)
└── RelationshipStrategy (connected content themes)
```

### Content Quality Requirements
- **Specific & Actionable**: "Schedule Q3 budget review with finance team" not "Schedule meeting"
- **Contextually Appropriate**: Work todos during business hours, personal todos in evenings/weekends
- **Persona Consistent**: Same voice and priorities across all content for a scenario
- **Realistic Timelines**: Logical sequences like plan → execute → follow-up
- **Natural Relationships**: Content that naturally connects to other fragments

## Implementation Approach

### 1. AI Service Integration
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
    {
        $prompt = $this->buildTodoPrompt($config);
        $response = $this->aiProvider->generateText($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 500,
            'provider' => $config->getAiProvider(),
            'model' => $config->getAiModel(),
        ]);
        
        return $this->parseTodoResponse($response, $config);
    }
}
```

### 2. Content Template System
```php
// app/Services/Demo/AI/ContentTemplateService.php
class ContentTemplateService
{
    private array $todoTemplates = [
        'personal' => [
            'Pick up {item} from {location}',
            'Schedule {appointment_type} with {provider}',
            'Buy {gift_type} for {person}',
            'Research {topic} for {project}',
        ],
        'work' => [
            'Review {document_type} with {team}',
            'Update {system} documentation for {purpose}',
            'Investigate {issue_type} reported {timeframe}',
            'Schedule {meeting_type} for {project}',
        ],
    ];
    
    public function getTodoPrompt(string $vault, string $persona, array $examples): string
    {
        return "You are a {$persona}. Generate realistic {$vault} todos that are specific and actionable. 
                Examples: " . implode(', ', $examples) . "
                Make each todo specific with real places, people, and tasks. Avoid generic content.";
    }
}
```

### 3. Persona Consistency Engine
```php
// app/Services/Demo/AI/PersonaConsistencyEngine.php
class PersonaConsistencyEngine
{
    public function generatePersonaContext(string $persona): PersonaContext
    {
        return new PersonaContext([
            'voice_traits' => $this->extractVoiceTraits($persona),
            'priority_patterns' => $this->determinePriorityPatterns($persona),
            'vocabulary_preferences' => $this->getVocabularyPreferences($persona),
            'time_management_style' => $this->getTimeManagementStyle($persona),
        ]);
    }
    
    public function applyPersonaConsistency(string $content, PersonaContext $context): string
    {
        // Apply consistent voice, terminology, and style patterns
    }
}
```

### 4. Content Generation Strategies

#### Realistic Todo Generation
- **Personal Todos**: "Pick up dry cleaning from Main Street cleaners", "Schedule dentist appointment for cleaning"
- **Work Todos**: "Review Q3 budget allocations with finance team", "Deploy hotfix for payment processing bug"
- **Project-Specific**: Connected to scenario projects with logical task progression

#### Authentic Chat Generation
- **Natural Conversations**: Multi-turn discussions with realistic context
- **Topic Consistency**: Chat threads stay on topic with natural progression
- **Persona Voice**: Consistent communication style matching scenario persona

#### Realistic Contact Generation
- **Professional Contacts**: With realistic roles, companies, and relationship context
- **Meeting Notes**: Authentic meeting summaries with actionable outcomes
- **Relationship History**: Consistent interaction patterns and communication themes

## Technical Constraints

### AI Provider Requirements
- **Configuration**: Use existing `config/fragments.php` AI provider system
- **Fallback Support**: Handle AI provider failures gracefully with fallback content
- **Rate Limiting**: Respect AI provider rate limits with batching and delays
- **Cost Management**: Optimize prompts for efficiency and cost control

### Content Quality Standards
- **Authenticity**: All content must feel realistic and specific
- **Consistency**: Maintain persona and context across all generated content
- **Relevance**: Content must be appropriate for the scenario and time period
- **Actionability**: Todos and reminders must be specific and actionable

### Integration Requirements
- **YAML Configuration**: Read generation rules from scenario YAML files
- **Existing Seeders**: Integrate with current `DemoSubSeeder` architecture
- **Timeline System**: Coordinate with existing `TimelineGenerator` for temporal distribution
- **Fragment System**: Generate content compatible with Fragment model structure

## Development Guidelines

### Code Organization
```
app/Services/Demo/AI/
├── DemoContentGenerationService.php
├── ContentTemplateService.php
├── PersonaConsistencyEngine.php
├── Strategies/
│   ├── PersonaStrategy.php
│   ├── ContextualStrategy.php
│   ├── RealisticStrategy.php
│   └── RelationshipStrategy.php
├── Generators/
│   ├── TodoGenerator.php
│   ├── ChatGenerator.php
│   ├── ContactGenerator.php
│   └── ReminderGenerator.php
└── Content/
    ├── TodoContent.php
    ├── ChatContent.php
    ├── ContactContent.php
    └── ReminderContent.php
```

### Prompt Engineering Best Practices
- **Clear Instructions**: Specific, actionable prompts with examples
- **Context Provision**: Include persona, scenario, and relationship context
- **Output Format**: Specify exact output format and structure requirements
- **Quality Guidelines**: Include authenticity and specificity requirements

### Error Handling Strategy
- **AI Failures**: Graceful fallback to template-based generation
- **Invalid Responses**: Content validation and regeneration
- **Rate Limiting**: Intelligent batching and retry logic
- **Quality Assurance**: Content quality validation before acceptance

## Key Deliverables

### 1. AI Content Generation Service
- `DemoContentGenerationService` with full generation capabilities
- Integration with existing AI provider system
- Support for all content types (todos, chats, contacts, reminders)
- Persona consistency and context awareness

### 2. Content Generation Strategies
- Realistic content generation with specific examples
- Persona-based voice consistency
- Scenario-appropriate content adaptation
- Natural relationship and timeline awareness

### 3. Template & Prompt System
- Content templates for different scenarios
- Optimized prompts for AI generation
- Quality validation and improvement
- Fallback content strategies

### 4. Integration Components
- Service provider registration
- Configuration integration with scenario YAML
- Error handling and logging
- Performance optimization

## Implementation Priority

### Phase 1: Core AI Integration (High Priority)
1. Create AI service integration with existing provider system
2. Implement basic content generation for todos and contacts
3. Set up prompt templates and response parsing
4. Add error handling and fallback strategies

### Phase 2: Content Quality (High Priority)
1. Implement persona consistency engine
2. Create realistic content generation strategies
3. Add content validation and quality assurance
4. Develop scenario-specific content adaptation

### Phase 3: Advanced Features (Medium Priority)
1. Add sophisticated relationship generation
2. Implement chat conversation flows
3. Create meeting notes and reminder generation
4. Add content optimization and refinement

## Success Validation

### Content Quality Testing
```bash
# Generate content samples
php artisan demo:generate-sample todos --scenario=general --count=10
php artisan demo:generate-sample chats --scenario=writer --count=5

# Validate content quality
php artisan demo:validate-content --scenario=general --check=realism
php artisan demo:validate-content --scenario=developer --check=persona
```

### Integration Testing
- Content integrates with existing Fragment model
- Generated content follows scenario configurations
- AI provider integration works correctly
- Persona consistency maintained across content types

This AI content generation system will transform demo data from generic faker content into authentic, realistic scenarios that truly showcase the application's capabilities and provide meaningful demonstration experiences.