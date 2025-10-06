# SEEDER-004: Fragment Relationship Builder — Implementation Plan

## Executive Summary

Build a simple but effective fragment relationship system that creates natural connections between demo data fragments. Focus on basic relationship patterns that enhance demo realism without complexity, supporting contact tagging and simple dependency modeling.

**Estimated Effort**: 4-8 hours  
**Priority**: Medium (Enhancement layer)  
**Dependencies**: SEEDER-001 (YAML Configuration), SEEDER-003 (Enhanced Seeder Components)

## Implementation Phases

### Phase 1: Core Relationship Infrastructure (2-3h)

#### 1.1 Relationship Configuration System
```php
// app/Services/Demo/Relationships/Config/RelationshipConfig.php
class RelationshipConfig
{
    public function __construct(
        private int $linkPercentage,
        private array $relationshipTypes,
        private TaggingConfig $taggingStrategy
    ) {}
    
    public function getLinkPercentage(): int
    {
        return $this->linkPercentage; // e.g., 25% of fragments get relationships
    }
    
    public function getRelationshipTypes(): array
    {
        return $this->relationshipTypes; // ['related_to', 'blocks', 'follows_up', 'references']
    }
    
    public function getTaggingStrategy(): TaggingConfig
    {
        return $this->taggingStrategy;
    }
    
    public static function fromYamlConfig(array $yamlConfig): self
    {
        return new self(
            linkPercentage: $yamlConfig['link_percentage'] ?? 25,
            relationshipTypes: $yamlConfig['relationship_types'] ?? ['related_to', 'follows_up'],
            taggingStrategy: TaggingConfig::fromYamlConfig($yamlConfig['tagging_strategy'] ?? [])
        );
    }
}

// app/Services/Demo/Relationships/Config/TaggingConfig.php
class TaggingConfig
{
    public function __construct(
        private int $contactTagPercentage,
        private array $applyTo,
        private bool $autoTagsEnabled,
        private array $autoTagCategories
    ) {}
    
    public function getContactTagPercentage(): int
    {
        return $this->contactTagPercentage; // e.g., 40%
    }
    
    public function getApplyTo(): array
    {
        return $this->applyTo; // ['meeting_notes', 'todos', 'reminders']
    }
    
    public static function fromYamlConfig(array $yamlConfig): self
    {
        $contactTags = $yamlConfig['contact_tags'] ?? [];
        $autoTags = $yamlConfig['auto_tags'] ?? [];
        
        return new self(
            contactTagPercentage: $contactTags['percentage'] ?? 40,
            applyTo: $contactTags['apply_to'] ?? ['meeting_notes', 'todos'],
            autoTagsEnabled: $autoTags['enabled'] ?? true,
            autoTagCategories: $autoTags['categories'] ?? ['urgent', 'waiting', 'project']
        );
    }
}
```

#### 1.2 Relationship Candidate Model
```php
// app/Services/Demo/Relationships/Models/RelationshipCandidate.php
class RelationshipCandidate
{
    public function __construct(
        private Fragment $source,
        private Fragment $target,
        private string $type,
        private float $confidence = 1.0,
        private array $metadata = []
    ) {}
    
    public function getSource(): Fragment
    {
        return $this->source;
    }
    
    public function getTarget(): Fragment
    {
        return $this->target;
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    public function getConfidence(): float
    {
        return $this->confidence;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    public function toRelationshipArray(): array
    {
        return [
            'type' => $this->type,
            'target_fragment_id' => $this->target->id,
            'metadata' => array_merge($this->metadata, [
                'created_by' => 'relationship_builder',
                'confidence' => $this->confidence,
                'created_at' => now()->toISOString(),
            ]),
        ];
    }
}
```

#### 1.3 Main Relationship Builder
```php
// app/Services/Demo/Relationships/FragmentRelationshipBuilder.php
class FragmentRelationshipBuilder
{
    public function __construct(
        private RelationshipPatternDetector $patternDetector,
        private ContactTagger $contactTagger
    ) {}
    
    public function buildRelationships(Collection $fragments, RelationshipConfig $config): void
    {
        $this->logRelationshipBuildingStart($fragments, $config);
        
        // Phase 1: Apply contact tagging
        $this->contactTagger->applyContactTags($fragments, $config->getTaggingStrategy());
        
        // Phase 2: Detect relationship candidates
        $relationshipCandidates = $this->detectAllRelationships($fragments);
        
        // Phase 3: Select relationships based on configuration
        $selectedRelationships = $this->selectRelationshipsByPercentage($relationshipCandidates, $config);
        
        // Phase 4: Create the relationships
        $this->createFragmentRelationships($selectedRelationships);
        
        $this->logRelationshipBuildingComplete($selectedRelationships);
    }
    
    private function detectAllRelationships(Collection $fragments): Collection
    {
        $candidates = collect();
        
        // Meeting → Todo relationships
        $candidates = $candidates->merge(
            $this->patternDetector->detectMeetingToTodoRelationships($fragments)
        );
        
        // Project task sequences
        $candidates = $candidates->merge(
            $this->patternDetector->detectProjectTaskRelationships($fragments)
        );
        
        // Chat → Action relationships
        $candidates = $candidates->merge(
            $this->patternDetector->detectChatToActionRelationships($fragments)
        );
        
        // Simple cross-reference relationships
        $candidates = $candidates->merge(
            $this->patternDetector->detectCrossReferences($fragments)
        );
        
        return $candidates;
    }
    
    private function selectRelationshipsByPercentage(Collection $candidates, RelationshipConfig $config): Collection
    {
        $totalFragments = $candidates->pluck('source')->unique('id')->count();
        $targetRelationshipCount = (int) ceil($totalFragments * $config->getLinkPercentage() / 100);
        
        // Sort by confidence and take top N
        return $candidates->sortByDesc('confidence')->take($targetRelationshipCount);
    }
    
    private function createFragmentRelationships(Collection $relationships): void
    {
        $relationshipsByFragment = $relationships->groupBy(fn($rel) => $rel->getSource()->id);
        
        foreach ($relationshipsByFragment as $fragmentId => $fragmentRelationships) {
            $fragment = $fragmentRelationships->first()->getSource();
            
            $currentRelationships = $fragment->relationships ?? [];
            
            foreach ($fragmentRelationships as $relationship) {
                $currentRelationships[] = $relationship->toRelationshipArray();
            }
            
            $fragment->update(['relationships' => $currentRelationships]);
        }
    }
}
```

### Phase 2: Relationship Detection Strategies (2-3h)

#### 2.1 Pattern Detector
```php
// app/Services/Demo/Relationships/RelationshipPatternDetector.php
class RelationshipPatternDetector
{
    public function detectMeetingToTodoRelationships(Collection $fragments): Collection
    {
        $meetingFragments = $fragments->where('metadata.demo_category', 'contact')
            ->where('metadata.has_meeting_notes', true);
        
        $todoFragments = $fragments->where('metadata.demo_category', 'todo');
        
        $candidates = collect();
        
        foreach ($meetingFragments as $meetingFragment) {
            $relatedTodos = $this->findRelatedTodos($meetingFragment, $todoFragments);
            
            foreach ($relatedTodos as $todoFragment) {
                $candidates->push(new RelationshipCandidate(
                    source: $todoFragment,
                    target: $meetingFragment,
                    type: 'follows_up',
                    confidence: $this->calculateMeetingTodoConfidence($meetingFragment, $todoFragment),
                    metadata: ['context' => 'Action item from meeting']
                ));
            }
        }
        
        return $candidates;
    }
    
    public function detectProjectTaskRelationships(Collection $fragments): Collection
    {
        $todoFragments = $fragments->where('metadata.demo_category', 'todo');
        
        // Group todos by project
        $todosByProject = $todoFragments->filter(fn($f) => $f->project_id)
            ->groupBy('project_id');
        
        $candidates = collect();
        
        foreach ($todosByProject as $projectId => $projectTodos) {
            $sequences = $this->findTaskSequences($projectTodos);
            $candidates = $candidates->merge($sequences);
        }
        
        return $candidates;
    }
    
    public function detectChatToActionRelationships(Collection $fragments): Collection
    {
        $chatFragments = $fragments->where('metadata.demo_category', 'chat_message');
        $actionFragments = $fragments->whereIn('metadata.demo_category', ['todo', 'reminder']);
        
        $candidates = collect();
        
        foreach ($chatFragments as $chatFragment) {
            $relatedActions = $this->findChatRelatedActions($chatFragment, $actionFragments);
            
            foreach ($relatedActions as $actionFragment) {
                $candidates->push(new RelationshipCandidate(
                    source: $actionFragment,
                    target: $chatFragment,
                    type: 'references',
                    confidence: $this->calculateChatActionConfidence($chatFragment, $actionFragment),
                    metadata: ['context' => 'Discussed in chat']
                ));
            }
        }
        
        return $candidates;
    }
    
    public function detectCrossReferences(Collection $fragments): Collection
    {
        // Simple cross-reference detection based on shared keywords
        $candidates = collect();
        
        $fragments->each(function ($fragment) use ($fragments, $candidates) {
            $keywords = $this->extractKeywords($fragment);
            
            if ($keywords->isNotEmpty()) {
                $relatedFragments = $this->findFragmentsByKeywords($keywords, $fragments, $fragment);
                
                foreach ($relatedFragments as $relatedFragment) {
                    $candidates->push(new RelationshipCandidate(
                        source: $fragment,
                        target: $relatedFragment,
                        type: 'related_to',
                        confidence: 0.5,
                        metadata: ['context' => 'Shared keywords']
                    ));
                }
            }
        });
        
        return $candidates;
    }
    
    private function findRelatedTodos(Fragment $meetingFragment, Collection $todoFragments): Collection
    {
        $meetingContent = strtolower($meetingFragment->message);
        $actionKeywords = ['review', 'schedule', 'follow up', 'contact', 'call', 'email', 'complete'];
        
        // Look for action words in meeting content
        $hasActionWords = collect($actionKeywords)->some(function ($keyword) use ($meetingContent) {
            return str_contains($meetingContent, $keyword);
        });
        
        if (!$hasActionWords) {
            return collect();
        }
        
        // Find todos in same vault created after meeting
        return $todoFragments->filter(function ($todo) use ($meetingFragment) {
            return $todo->vault === $meetingFragment->vault && 
                   $todo->created_at->gt($meetingFragment->created_at);
        })->take(2); // Limit to 2 related todos per meeting
    }
    
    private function findTaskSequences(Collection $projectTodos): Collection
    {
        $sortedTodos = $projectTodos->sortBy('created_at');
        $sequences = collect();
        
        $sortedTodos->sliding(2)->each(function ($pair) use ($sequences) {
            [$earlier, $later] = $pair;
            
            if ($this->shouldCreateDependency($earlier, $later)) {
                $sequences->push(new RelationshipCandidate(
                    source: $later,
                    target: $earlier,
                    type: 'blocks',
                    confidence: 0.7,
                    metadata: ['context' => 'Task dependency']
                ));
            }
        });
        
        return $sequences;
    }
    
    private function shouldCreateDependency(Fragment $earlier, Fragment $later): bool
    {
        $earlierTitle = strtolower($earlier->title);
        $laterTitle = strtolower($later->title);
        
        // Planning → Implementation patterns
        if (str_contains($earlierTitle, 'plan') && !str_contains($laterTitle, 'plan')) {
            return true;
        }
        
        // Setup → Configuration patterns  
        if (str_contains($earlierTitle, 'setup') && str_contains($laterTitle, 'configure')) {
            return true;
        }
        
        // Research → Implementation patterns
        if (str_contains($earlierTitle, 'research') && !str_contains($laterTitle, 'research')) {
            return true;
        }
        
        return false;
    }
}
```

#### 2.2 Contact Tagger
```php
// app/Services/Demo/Relationships/ContactTagger.php
class ContactTagger
{
    public function applyContactTags(Collection $fragments, TaggingConfig $config): void
    {
        $contactFragments = $fragments->where('metadata.demo_category', 'contact');
        $taggableFragments = $this->selectTaggableFragments($fragments, $config);
        
        $taggingTargets = $this->selectTaggingTargets($taggableFragments, $config->getContactTagPercentage());
        
        foreach ($taggingTargets as $target) {
            $relatedContact = $this->findRelatedContact($target, $contactFragments);
            if ($relatedContact) {
                $this->addContactTag($target, $relatedContact);
            }
        }
    }
    
    private function selectTaggableFragments(Collection $fragments, TaggingConfig $config): Collection
    {
        $applyTo = $config->getApplyTo(); // ['meeting_notes', 'todos', 'reminders']
        
        return $fragments->filter(function ($fragment) use ($applyTo) {
            $category = $fragment->metadata['demo_category'] ?? null;
            
            if (in_array($category, $applyTo)) {
                return true;
            }
            
            // Special case for meeting notes
            if (in_array('meeting_notes', $applyTo) && 
                $category === 'contact' && 
                ($fragment->metadata['has_meeting_notes'] ?? false)) {
                return true;
            }
            
            return false;
        });
    }
    
    private function selectTaggingTargets(Collection $taggableFragments, int $percentage): Collection
    {
        $targetCount = (int) ceil($taggableFragments->count() * $percentage / 100);
        
        // Randomly select fragments for tagging, but prefer certain types
        return $taggableFragments->sortByDesc(function ($fragment) {
            // Prioritize meeting notes and work todos
            $category = $fragment->metadata['demo_category'] ?? '';
            $vault = $fragment->vault ?? '';
            
            if ($category === 'contact' && ($fragment->metadata['has_meeting_notes'] ?? false)) {
                return 3; // Highest priority
            }
            
            if ($category === 'todo' && $vault === 'work') {
                return 2; // Medium priority
            }
            
            return 1; // Base priority
        })->take($targetCount);
    }
    
    private function findRelatedContact(Fragment $fragment, Collection $contactFragments): ?Fragment
    {
        // Prefer contacts in same vault
        $sameVaultContacts = $contactFragments->where('vault', $fragment->vault);
        
        if ($sameVaultContacts->isNotEmpty()) {
            return $sameVaultContacts->random();
        }
        
        // Fallback to any contact
        return $contactFragments->random();
    }
    
    private function addContactTag(Fragment $fragment, Fragment $contact): void
    {
        $contactName = $this->extractContactName($contact);
        $contactTag = "contact:{$contactName}";
        
        $currentTags = $fragment->tags ?? [];
        if (!in_array($contactTag, $currentTags)) {
            $currentTags[] = $contactTag;
            $fragment->update(['tags' => $currentTags]);
        }
    }
    
    private function extractContactName(Fragment $contact): string
    {
        $title = $contact->title;
        
        // Remove common prefixes
        $title = preg_replace('/^(Meeting with |Call with |Email from )/i', '', $title);
        
        // Take first two words as name
        $words = explode(' ', trim($title));
        return implode(' ', array_slice($words, 0, 2));
    }
}
```

### Phase 3: Integration with Enhanced Seeders (1-2h)

#### 3.1 Enhanced Seeder Integration
```php
// Integration in EnhancedDemoDataSeeder
public function run(): void
{
    // ... existing seeding logic ...
    
    // Build relationships after all fragments created
    $this->buildFragmentRelationships($context);
}

private function buildFragmentRelationships(EnhancedDemoSeedContext $context): void
{
    $relationshipConfig = $this->scenarioConfig->getFragmentRelationships();
    
    if ($relationshipConfig->getLinkPercentage() > 0) {
        $allFragments = $this->collectAllDemoFragments($context);
        
        if ($allFragments->count() > 0) {
            app(FragmentRelationshipBuilder::class)->buildRelationships($allFragments, $relationshipConfig);
            $context->info('<info>✔</info> Fragment relationships built');
        }
    } else {
        $context->info('<comment>•</comment> Fragment relationships skipped (percentage = 0)');
    }
}

private function collectAllDemoFragments(EnhancedDemoSeedContext $context): Collection
{
    return collect()
        ->merge($context->collection('todo_fragments')->values())
        ->merge($context->collection('contact_fragments')->values())
        ->merge($context->collection('chat_fragments')->values())
        ->merge($context->collection('reminder_fragments')->values())
        ->filter(function (Fragment $fragment) {
            return $fragment->metadata['demo_seed'] === true;
        });
}
```

#### 3.2 Service Provider Registration
```php
// app/Providers/FragmentRelationshipServiceProvider.php
class FragmentRelationshipServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FragmentRelationshipBuilder::class);
        $this->app->singleton(RelationshipPatternDetector::class);
        $this->app->singleton(ContactTagger::class);
    }
}
```

### Phase 4: Testing and Validation (1h)

#### 4.1 Unit Tests
```php
// tests/Unit/Services/Demo/Relationships/FragmentRelationshipBuilderTest.php
test('builds relationships according to configuration percentage')
test('applies contact tags to correct fragment types')
test('creates valid relationship structures')
test('handles empty fragment collections gracefully')

// tests/Unit/Services/Demo/Relationships/RelationshipPatternDetectorTest.php
test('detects meeting to todo relationships correctly')
test('finds project task sequences')
test('identifies chat to action connections')
test('extracts cross-reference relationships')
```

#### 4.2 Integration Tests
```php
// tests/Feature/Demo/Relationships/RelationshipIntegrationTest.php
test('integrates with enhanced seeder system')
test('creates relationships after complete seeding')
test('respects scenario configuration rules')
test('maintains fragment data integrity')
```

## Testing Strategy

### Unit Tests
- Test relationship detection algorithms individually
- Validate contact tagging logic
- Test configuration parsing and validation
- Test relationship candidate creation

### Integration Tests  
- Test complete relationship building flow
- Validate integration with enhanced seeders
- Test scenario configuration integration
- Test performance with large datasets

### Quality Validation
- Verify relationship percentages match configuration
- Validate temporal consistency of relationships
- Check for circular or invalid relationships
- Ensure contact tags are applied appropriately

## Quality Assurance

### Code Quality
- [ ] PSR-12 compliance with Pint
- [ ] Type declarations for all methods
- [ ] Comprehensive error handling
- [ ] Performance optimization for large datasets

### Relationship Quality
- [ ] Logical consistency in relationship creation
- [ ] Temporal validity (no future blocking past)
- [ ] Natural feeling connections
- [ ] Appropriate contact tag distribution

### Performance Requirements
- [ ] Relationship building completes in <30 seconds
- [ ] Memory usage stays within limits
- [ ] No performance regression in seeding
- [ ] Efficient processing of large fragment sets

## Delivery Checklist

### Core Components
- [ ] `FragmentRelationshipBuilder` with full functionality
- [ ] `RelationshipPatternDetector` with multiple detection strategies
- [ ] `ContactTagger` with intelligent tag application
- [ ] Configuration classes for YAML integration

### Detection Strategies
- [ ] Meeting → Todo follow-up relationships
- [ ] Project task sequence relationships
- [ ] Chat → Action reference relationships
- [ ] Simple cross-reference relationships

### Integration Components
- [ ] Enhanced seeder integration
- [ ] Service provider registration
- [ ] Configuration validation
- [ ] Performance optimization

### Testing & Documentation
- [ ] Comprehensive test suite (>90% coverage)
- [ ] Integration testing with enhanced seeders
- [ ] Performance benchmarking
- [ ] Usage documentation and examples

## Success Validation

### Functional Testing
```bash
# Test relationship building
php artisan demo:seed-enhanced --scenario=general
php artisan demo:analyze-relationships --scenario=general

# Validate relationship quality
php artisan demo:validate-relationships --scenario=writer --check=temporal
php artisan demo:validate-relationships --scenario=developer --check=logic

# Check relationship statistics
php artisan tinker --execute="
\$fragments = App\Models\Fragment::where('metadata->demo_seed', true)->get();
\$withRelationships = \$fragments->filter(fn(\$f) => count(\$f->relationships ?? []) > 0);
echo 'Fragments with relationships: ' . \$withRelationships->count() . '/' . \$fragments->count();
echo 'Percentage: ' . round((\$withRelationships->count() / \$fragments->count()) * 100, 1) . '%';
"
```

### Quality Gates
- [ ] Relationship percentage matches configuration (±5%)
- [ ] All relationships are temporally valid
- [ ] Contact tags applied to appropriate fragments
- [ ] No circular or invalid relationships
- [ ] Performance benchmarks met

This simple but effective fragment relationship system will add crucial interconnectedness to the demo data, transforming isolated fragments into a realistic web of related content that better demonstrates the application's relationship capabilities without adding unnecessary complexity.