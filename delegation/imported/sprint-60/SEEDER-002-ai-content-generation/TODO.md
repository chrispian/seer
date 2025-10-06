# SEEDER-002: AI Content Generation Agent — TODO

## Implementation Checklist

### Phase 1: Core AI Service Integration ⏱️ 4-5h

#### AI Provider Service Integration
- [ ] Create `app/Services/Demo/AI/DemoContentGenerationService.php`
  - [ ] `generateTodos(TodoGenerationConfig $config): Collection` method
  - [ ] `generateChatMessages(ChatGenerationConfig $config): Collection` method
  - [ ] `generateContacts(ContactGenerationConfig $config): Collection` method
  - [ ] `generateReminders(ReminderGenerationConfig $config): Collection` method
  - [ ] Private `generateWithProvider(string $prompt, array $options): string` method
  - [ ] AI provider configuration resolution (default vs override)
  - [ ] Integration with existing `AiProviderService` from fragments system

#### Configuration Extension
- [ ] Extend `config/fragments.php` with demo seeder AI configuration
  - [ ] Add `demo_seeder.ai_generation` section
  - [ ] Provider and model override settings
  - [ ] Temperature, max_tokens, batch_size configuration
  - [ ] Retry attempts and fallback settings
  - [ ] Rate limiting configuration

#### Content Value Objects
- [ ] Create `app/Services/Demo/AI/Content/TodoContent.php`
  - [ ] Constructor with title, message, tags, vault, project, state
  - [ ] `toFragmentData(): array` method for Fragment model compatibility
  - [ ] Validation methods for content quality

- [ ] Create `app/Services/Demo/AI/Content/ChatContent.php`
  - [ ] Constructor with messages, topic, session data
  - [ ] `toFragmentData(): array` method
  - [ ] `toChatSessionData(): array` method

- [ ] Create `app/Services/Demo/AI/Content/ContactContent.php`
  - [ ] Constructor with name, role, company, relationship, meetingNotes
  - [ ] `toFragmentData(): array` method
  - [ ] `toContactData(): array` method

- [ ] Create `app/Services/Demo/AI/Content/ReminderContent.php`
  - [ ] Constructor with content, type, timing, priority
  - [ ] `toFragmentData(): array` method

#### Error Handling & Fallback
- [ ] Create exception classes in `app/Services/Demo/AI/Exceptions/`
  - [ ] `AiGenerationException.php`
  - [ ] `AiProviderUnavailableException.php`
  - [ ] `ContentValidationException.php`
  - [ ] `RateLimitExceededException.php`

- [ ] Create `app/Services/Demo/AI/AiFallbackService.php`
  - [ ] `generateFallbackTodos(TodoGenerationConfig $config): Collection`
  - [ ] `generateFallbackChats(ChatGenerationConfig $config): Collection`
  - [ ] `generateFallbackContacts(ContactGenerationConfig $config): Collection`
  - [ ] Template-based fallback generation

### Phase 2: Content Generation Strategies ⏱️ 4-5h

#### Todo Generation Engine
- [ ] Create `app/Services/Demo/AI/Generators/TodoGenerator.php`
  - [ ] `generateTodos(TodoGenerationConfig $config): Collection` method
  - [ ] `buildTodoPrompt(TodoGenerationConfig $config, int $count): string` method
  - [ ] `parseTodoResponse(string $response, TodoGenerationConfig $config): Collection` method
  - [ ] Batch processing for large todo counts
  - [ ] JSON response parsing and validation
  - [ ] Error handling and fallback integration

#### Chat Generation Engine
- [ ] Create `app/Services/Demo/AI/Generators/ChatGenerator.php`
  - [ ] `generateChatSessions(ChatGenerationConfig $config): Collection` method
  - [ ] `generateConversation(string $topic, ChatGenerationConfig $config): ChatContent` method
  - [ ] `buildChatPrompt(string $topic, ChatGenerationConfig $config): string` method
  - [ ] `parseConversationResponse(string $response, string $topic, ChatGenerationConfig $config): ChatContent` method
  - [ ] Multi-message conversation flow generation
  - [ ] Natural conversation progression logic

#### Contact Generation Engine
- [ ] Create `app/Services/Demo/AI/Generators/ContactGenerator.php`
  - [ ] `generateContacts(ContactGenerationConfig $config): Collection` method
  - [ ] `generateDetailedContact(array $example, ContactGenerationConfig $config): ContactContent` method
  - [ ] `generateAdditionalContacts(int $count, ContactGenerationConfig $config): Collection` method
  - [ ] `generateMeetingNotes(array $contact, ContactGenerationConfig $config): string` method
  - [ ] `generateContactTags(array $contact, ContactGenerationConfig $config): array` method
  - [ ] Realistic contact relationship modeling

#### Reminder Generation Engine
- [ ] Create `app/Services/Demo/AI/Generators/ReminderGenerator.php`
  - [ ] `generateReminders(ReminderGenerationConfig $config): Collection` method
  - [ ] Support for meeting, deadline, followup, personal reminder types
  - [ ] Context-aware reminder generation based on other content
  - [ ] Realistic timing and priority assignment

#### Configuration Objects
- [ ] Create configuration objects for each generator:
  - [ ] `TodoGenerationConfig.php` - todos configuration
  - [ ] `ChatGenerationConfig.php` - chat configuration  
  - [ ] `ContactGenerationConfig.php` - contact configuration
  - [ ] `ReminderGenerationConfig.php` - reminder configuration
- [ ] Each config should include:
  - [ ] Count, examples, persona, vault information
  - [ ] AI provider options (model, temperature, etc.)
  - [ ] Generation rules and constraints
  - [ ] Quality validation settings

### Phase 3: Persona Consistency System ⏱️ 2-3h

#### Persona Engine
- [ ] Create `app/Services/Demo/AI/PersonaConsistencyEngine.php`
  - [ ] Define persona patterns for different user types:
    - [ ] `busy_professional` - efficient, goal-oriented, time-conscious
    - [ ] `content_creator` - creative, deadline-driven, client-focused
    - [ ] `developer` - analytical, problem-solving, technical
    - [ ] `productivity_enthusiast` - organized, systematic, optimization-focused
  - [ ] `generatePersonaContext(string $persona): PersonaContext` method
  - [ ] `enhancePromptWithPersona(string $basePrompt, PersonaContext $context): string` method
  - [ ] Voice trait and vocabulary consistency

- [ ] Create `app/Services/Demo/AI/PersonaContext.php`
  - [ ] Constructor with voice traits, vocabulary, time patterns, communication style
  - [ ] Getter methods for all persona attributes
  - [ ] `applyToPrompt(string $prompt): string` method

#### Content Quality Validation
- [ ] Create `app/Services/Demo/AI/ContentQualityValidator.php`
  - [ ] `validateTodo(string $todo, PersonaContext $context): ValidationResult` method
  - [ ] `validateChat(array $messages, PersonaContext $context): ValidationResult` method
  - [ ] `validateContact(array $contact, PersonaContext $context): ValidationResult` method
  - [ ] Specificity checking (times, places, people)
  - [ ] Actionability verification
  - [ ] Realism assessment
  - [ ] Persona consistency validation

- [ ] Create `app/Services/Demo/AI/ValidationResult.php`
  - [ ] Constructor with passed status and detailed checks
  - [ ] Individual check results (specificity, actionability, realism, consistency)
  - [ ] Overall quality score calculation
  - [ ] `getFailureReasons(): array` method

#### Content Enhancement
- [ ] Implement content enhancement methods:
  - [ ] Vocabulary consistency application
  - [ ] Tone adjustment based on persona
  - [ ] Time pattern application (business hours, flexible schedule, etc.)
  - [ ] Communication style enforcement

### Phase 4: Template & Fallback System ⏱️ 2-3h

#### Content Template Service
- [ ] Create `app/Services/Demo/AI/ContentTemplateService.php`
  - [ ] Define realistic todo templates for personal and work contexts
  - [ ] Define template variables (items, locations, appointment types, etc.)
  - [ ] `generateRealisticTodos(TodoGenerationConfig $config): Collection` method
  - [ ] `generateRealisticChats(ChatGenerationConfig $config): Collection` method
  - [ ] `generateRealisticContacts(ContactGenerationConfig $config): Collection` method
  - [ ] `fillTemplate(string $template, array $variables): string` method

- [ ] Template definitions:
  - [ ] Personal todo templates (pick up, schedule, buy, research patterns)
  - [ ] Work todo templates (review, update, investigate, deploy patterns)
  - [ ] Chat topic templates for different scenarios
  - [ ] Contact role and company templates
  - [ ] Meeting note templates

#### Enhanced Faker Integration
- [ ] Create realistic variable sets for template filling:
  - [ ] Personal context: cleaners, appointments, errands, family tasks
  - [ ] Work context: meetings, projects, systems, deadlines
  - [ ] Technical context: deployments, bugs, architecture, testing
  - [ ] Creative context: content, deadlines, clients, publishing

#### Rate Limiting & Batching
- [ ] Create `app/Services/Demo/AI/AiContentBatcher.php`
  - [ ] `batchGenerate(array $prompts, array $options): Collection` method
  - [ ] Configurable batch size and delay management
  - [ ] Exponential backoff retry logic
  - [ ] Rate limit detection and handling
  - [ ] Progress tracking for large generation tasks

#### Monitoring & Logging
- [ ] Add comprehensive logging for AI generation:
  - [ ] Generation request logging (prompts, timing, tokens)
  - [ ] Error and fallback logging
  - [ ] Quality validation logging
  - [ ] Performance metrics logging
- [ ] Create monitoring for:
  - [ ] AI provider response times
  - [ ] Content quality scores
  - [ ] Fallback usage rates
  - [ ] Generation success rates

### Testing & Quality Assurance ⏱️ 1-2h

#### Unit Tests
- [ ] Create `tests/Unit/Services/Demo/AI/DemoContentGenerationServiceTest.php`
  - [ ] Test AI provider integration
  - [ ] Test configuration override handling
  - [ ] Test error handling and fallback
  - [ ] Test content generation coordination

- [ ] Create `tests/Unit/Services/Demo/AI/Generators/TodoGeneratorTest.php`
  - [ ] Test prompt building with different configurations
  - [ ] Test JSON response parsing
  - [ ] Test invalid response handling
  - [ ] Test batch processing logic

- [ ] Create `tests/Unit/Services/Demo/AI/PersonaConsistencyEngineTest.php`
  - [ ] Test persona pattern definition
  - [ ] Test context generation
  - [ ] Test prompt enhancement
  - [ ] Test vocabulary and tone consistency

- [ ] Create `tests/Unit/Services/Demo/AI/ContentQualityValidatorTest.php`
  - [ ] Test specificity checking algorithms
  - [ ] Test actionability validation
  - [ ] Test persona consistency detection
  - [ ] Test overall quality scoring

#### Integration Tests
- [ ] Create `tests/Feature/Demo/AI/ContentGenerationIntegrationTest.php`
  - [ ] Test complete generation flow with AI provider
  - [ ] Test fallback system activation
  - [ ] Test large batch processing
  - [ ] Test scenario configuration integration

#### Content Quality Tests
- [ ] Create `tests/Feature/Demo/AI/ContentQualityTest.php`
  - [ ] Test generated content specificity (90% target)
  - [ ] Test actionability rates (95% target)
  - [ ] Test persona consistency across content types
  - [ ] Test content uniqueness and variety

#### Performance Tests
- [ ] Create `tests/Feature/Demo/AI/ContentGenerationPerformanceTest.php`
  - [ ] Test generation speed benchmarks
  - [ ] Test memory usage during large generations
  - [ ] Test AI provider timeout handling
  - [ ] Test rate limiting compliance

### Documentation & Console Commands ⏱️ 1h

#### Console Commands
- [ ] Create `app/Console/Commands/Demo/GenerateSampleContentCommand.php`
  - [ ] Command signature: `demo:generate-sample {type} {--scenario=} {--count=10}`
  - [ ] Support for todos, chats, contacts, reminders
  - [ ] Display generated content for review
  - [ ] Quality validation reporting

- [ ] Create `app/Console/Commands/Demo/ValidateContentCommand.php`
  - [ ] Command signature: `demo:validate-content {--scenario=} {--type=} {--check=all}`
  - [ ] Content quality analysis and reporting
  - [ ] Persona consistency verification
  - [ ] Specificity and actionability scoring

- [ ] Create `app/Console/Commands/Demo/TestAiProviderCommand.php`
  - [ ] Command signature: `demo:test-ai-provider {--provider=} {--model=}`
  - [ ] Test AI provider connectivity and response quality
  - [ ] Benchmark generation performance
  - [ ] Validate configuration settings

#### Documentation
- [ ] Create comprehensive documentation:
  - [ ] AI content generation system overview
  - [ ] Persona configuration guide
  - [ ] Template customization guide
  - [ ] Quality validation criteria
  - [ ] Troubleshooting common issues

## Acceptance Criteria

### Functional Requirements
- [ ] Generate realistic, specific todos (not generic faker content)
- [ ] Create authentic chat conversations with natural flow
- [ ] Generate contacts with realistic meeting notes
- [ ] Maintain persona consistency across all content types
- [ ] Support multiple scenario types (general, writer, developer, productivity)

### Quality Requirements
- [ ] 90% of todos include specific details (times, places, people)
- [ ] 95% of todos are actionable with clear next steps
- [ ] Content matches scenario persona voice and priorities
- [ ] No repetitive or duplicate content patterns
- [ ] Generated content feels authentic and realistic

### Performance Requirements
- [ ] Complete scenario generation in <2 minutes
- [ ] Efficient AI token usage for cost control
- [ ] Graceful fallback for 100% of AI failures
- [ ] Handle rate limiting without user intervention

### Integration Requirements
- [ ] Clean integration with existing AI provider system
- [ ] Compatible with Fragment model structure
- [ ] Works with SEEDER-001 YAML configuration system
- [ ] Provides content for SEEDER-003 enhanced seeders

## Success Validation Commands

```bash
# Test basic AI content generation
php artisan demo:generate-sample todos --scenario=general --count=10
php artisan demo:generate-sample chats --scenario=writer --count=5
php artisan demo:generate-sample contacts --scenario=developer --count=8

# Validate content quality
php artisan demo:validate-content --scenario=general --type=todos --check=specificity
php artisan demo:validate-content --scenario=writer --type=all --check=persona
php artisan demo:validate-content --scenario=developer --type=contacts --check=realism

# Test AI provider integration
php artisan demo:test-ai-provider --provider=openai --model=gpt-4o-mini
php artisan demo:test-ai-provider --provider=anthropic --model=claude-3-5-sonnet-latest

# Test fallback system
php artisan demo:generate-sample todos --scenario=general --force-fallback

# Performance benchmarking
php artisan demo:generate-sample todos --scenario=general --count=100 --benchmark
```

## Content Quality Examples

### Target Todo Quality
**Excellent Examples:**
- "Pick up dry cleaning from Main Street cleaners by 6pm"
- "Review Q3 budget allocations with finance team by Friday"
- "Schedule dentist appointment for cleaning - call Dr. Smith's office"
- "Deploy payment processing hotfix to production environment"

**Avoid (Generic Examples):**
- "Complete task"
- "Schedule meeting"
- "Review document"
- "Fix issue"

### Target Chat Quality
**Excellent Example:**
```json
[
  {"content": "Planning to set up the new router this weekend for the home lab", "role": "user", "timestamp": "2 hours ago"},
  {"content": "What's the target setup? Are you going with the mesh system we discussed?", "role": "assistant", "timestamp": "1 hour ago"},
  {"content": "Yeah, the Ubiquiti Dream Machine. Need to configure VLANs for the different project networks", "role": "user", "timestamp": "45 minutes ago"}
]
```

### Target Contact Quality
**Excellent Example:**
```
Name: Sarah Chen
Role: DevOps Lead
Company: TechCorp
Meeting Notes: Discussed Q4 infrastructure improvements. Sarah outlined the container migration timeline - targeting December for production deployment. Action items: I need to review the Kubernetes configs by next Friday. Follow up on budget approval for additional nodes.
```

## Notes & Considerations

### AI Provider Cost Management
- Optimize prompts for efficiency and cost control
- Use appropriate models for different content types
- Implement intelligent batching to reduce API calls
- Monitor token usage and implement budgeting

### Content Uniqueness Strategy
- Avoid repetitive patterns in generated content
- Implement content variation algorithms
- Track generated content to prevent duplicates
- Use diverse prompt strategies for variety

### Future Extensibility
- Design system to support additional content types
- Build extensible persona system for new user types
- Create plugin architecture for custom generators
- Support for multi-language content generation

This comprehensive AI content generation system will transform the demo data seeder from generic faker content to intelligent, realistic content that authentically demonstrates the application's capabilities across different user personas and scenarios.