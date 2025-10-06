# SEEDER-004: Fragment Relationship Builder Agent

## Agent Mission

You are a data relationship specialist and Laravel backend developer focused on creating intelligent connections between demo data fragments. Your mission is to build a simple but effective fragment relationship system that creates natural links between generated content, making demo data feel more authentic and interconnected.

## Core Objectives

### Primary Goal
Create a fragment relationship builder that:
- Links related fragments based on natural content relationships
- Maintains simple relationship patterns (no complex dependency modeling)
- Creates realistic connections between todos, contacts, chats, and reminders
- Integrates with existing Fragment model relationship structure
- Follows scenario-specific relationship rules from YAML configurations

### Success Metrics
- [ ] 25% of fragments have meaningful relationships to other fragments
- [ ] Relationship types include "related_to", "blocks", "follows_up", "references"
- [ ] Natural connections between meeting notes and follow-up todos
- [ ] Contact tagging applied to appropriate fragments (meeting notes, todos)
- [ ] Relationships enhance demo realism without complexity

## Technical Specifications

### Simple Relationship Architecture
```php
// Basic relationship builder pattern
class FragmentRelationshipBuilder
{
    public function buildRelationships(Collection $fragments, RelationshipConfig $config): void
    {
        $relationshipCandidates = $this->identifyRelationshipCandidates($fragments);
        $relationshipsToCreate = $this->selectRelationshipsToCreate($relationshipCandidates, $config);
        $this->createFragmentRelationships($relationshipsToCreate);
    }
    
    private function identifyRelationshipCandidates(Collection $fragments): Collection
    {
        // Find fragments that could logically relate to each other
        return $fragments->groupBy('type')->map(function ($typeFragments, $type) {
            return $this->findCandidatesForType($type, $typeFragments);
        });
    }
}
```

### Relationship Types (Simple)
Based on your requirement for simplicity, focus on basic relationship types:

1. **"related_to"**: General relatedness between fragments
2. **"blocks"**: One task blocks another (simple dependency)
3. **"follows_up"**: One fragment follows up on another
4. **"references"**: One fragment references another

### Integration with Fragment Model
Use existing Fragment model relationship structure:
```php
// Existing Fragment model relationship field
'relationships' => [
    [
        'type' => 'related_to',
        'target_fragment_id' => 123,
        'metadata' => ['created_by' => 'relationship_builder'],
    ],
],
```

## Implementation Approach

### 1. Relationship Detection Patterns
```php
// app/Services/Demo/Relationships/RelationshipPatternDetector.php
class RelationshipPatternDetector
{
    public function detectMeetingToTodoRelationships(Collection $fragments): Collection
    {
        $meetingFragments = $fragments->where('metadata.demo_category', 'contact')
            ->where('metadata.has_meeting_notes', true);
        
        $todoFragments = $fragments->where('metadata.demo_category', 'todo');
        
        return $meetingFragments->map(function ($meetingFragment) use ($todoFragments) {
            // Find todos that could follow up on this meeting
            $relatedTodos = $this->findRelatedTodos($meetingFragment, $todoFragments);
            
            return $relatedTodos->map(function ($todoFragment) use ($meetingFragment) {
                return new RelationshipCandidate(
                    source: $meetingFragment,
                    target: $todoFragment,
                    type: 'follows_up',
                    confidence: $this->calculateConfidence($meetingFragment, $todoFragment)
                );
            });
        })->flatten();
    }
    
    public function detectProjectTaskRelationships(Collection $fragments): Collection
    {
        // Group todos by project and find logical sequences
        return $fragments->where('metadata.demo_category', 'todo')
            ->groupBy('project_id')
            ->map(function ($projectTodos) {
                return $this->findTaskSequences($projectTodos);
            })
            ->flatten();
    }
    
    public function detectChatToActionRelationships(Collection $fragments): Collection
    {
        // Connect chat discussions to resulting todos or reminders
        $chatFragments = $fragments->where('metadata.demo_category', 'chat_message');
        $actionFragments = $fragments->whereIn('metadata.demo_category', ['todo', 'reminder']);
        
        return $this->findChatActionConnections($chatFragments, $actionFragments);
    }
}
```

### 2. Contact Tagging System
```php
// app/Services/Demo/Relationships/ContactTagger.php
class ContactTagger
{
    public function applyContactTags(Collection $fragments, TaggingConfig $config): void
    {
        $contactFragments = $fragments->where('metadata.demo_category', 'contact');
        $taggableFragments = $fragments->whereIn('metadata.demo_category', ['todo', 'reminder']);
        
        $taggingTargets = $this->selectTaggingTargets($taggableFragments, $config->getContactTagPercentage());
        
        foreach ($taggingTargets as $target) {
            $relatedContact = $this->findRelatedContact($target, $contactFragments);
            if ($relatedContact) {
                $this->addContactTag($target, $relatedContact);
            }
        }
    }
    
    private function findRelatedContact(Fragment $fragment, Collection $contacts): ?Fragment
    {
        // Simple logic: find contact in same vault or with related keywords
        $sameVaultContacts = $contacts->where('vault', $fragment->vault);
        
        if ($sameVaultContacts->isNotEmpty()) {
            return $sameVaultContacts->random();
        }
        
        return $contacts->random();
    }
    
    private function addContactTag(Fragment $fragment, Fragment $contact): void
    {
        $contactName = $contact->title;
        $contactTag = "contact:{$contactName}";
        
        $currentTags = $fragment->tags ?? [];
        if (!in_array($contactTag, $currentTags)) {
            $currentTags[] = $contactTag;
            $fragment->update(['tags' => $currentTags]);
        }
    }
}
```

### 3. Simple Relationship Builder
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
        // Apply contact tagging first
        $this->contactTagger->applyContactTags($fragments, $config->getTaggingStrategy());
        
        // Detect and create relationships
        $relationshipCandidates = $this->detectAllRelationships($fragments);
        $selectedRelationships = $this->selectRelationshipsByPercentage($relationshipCandidates, $config);
        $this->createFragmentRelationships($selectedRelationships);
    }
    
    private function detectAllRelationships(Collection $fragments): Collection
    {
        $candidates = collect();
        
        // Meeting → Todo relationships
        $candidates = $candidates->merge($this->patternDetector->detectMeetingToTodoRelationships($fragments));
        
        // Project task sequences
        $candidates = $candidates->merge($this->patternDetector->detectProjectTaskRelationships($fragments));
        
        // Chat → Action relationships
        $candidates = $candidates->merge($this->patternDetector->detectChatToActionRelationships($fragments));
        
        // Simple cross-reference relationships
        $candidates = $candidates->merge($this->patternDetector->detectCrossReferences($fragments));
        
        return $candidates;
    }
    
    private function selectRelationshipsByPercentage(Collection $candidates, RelationshipConfig $config): Collection
    {
        $targetCount = $this->calculateTargetRelationshipCount($candidates, $config);
        
        // Sort by confidence and take top N
        return $candidates->sortByDesc('confidence')->take($targetCount);
    }
    
    private function createFragmentRelationships(Collection $relationships): void
    {
        foreach ($relationships as $relationship) {
            $this->addRelationshipToFragment($relationship);
        }
    }
    
    private function addRelationshipToFragment(RelationshipCandidate $relationship): void
    {
        $sourceFragment = $relationship->getSource();
        $currentRelationships = $sourceFragment->relationships ?? [];
        
        $newRelationship = [
            'type' => $relationship->getType(),
            'target_fragment_id' => $relationship->getTarget()->id,
            'metadata' => [
                'created_by' => 'relationship_builder',
                'confidence' => $relationship->getConfidence(),
                'created_at' => now()->toISOString(),
            ],
        ];
        
        $currentRelationships[] = $newRelationship;
        
        $sourceFragment->update(['relationships' => $currentRelationships]);
    }
}
```

### 4. Relationship Configuration
```php
// app/Services/Demo/Relationships/RelationshipConfig.php
class RelationshipConfig
{
    public function __construct(
        private int $linkPercentage,
        private array $relationshipTypes,
        private TaggingConfig $taggingStrategy
    ) {}
    
    public function getLinkPercentage(): int
    {
        return $this->linkPercentage; // e.g., 25
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
```

## Technical Constraints

### Simplicity Requirements
- **No Complex Dependencies**: Avoid sophisticated dependency modeling
- **Basic Relationship Types**: Stick to simple, understandable relationship types
- **Performance Focus**: Relationship building should complete quickly
- **Fragment Model Compatibility**: Use existing relationship field structure

### Integration Requirements
- **YAML Configuration**: Read relationship rules from scenario configurations
- **Enhanced Seeder Integration**: Work with SEEDER-003 enhanced seeder output
- **Timeline Respect**: Don't create relationships that violate temporal logic
- **Context Awareness**: Use scenario context for appropriate relationships

### Quality Guidelines
- **Natural Relationships**: Only create relationships that make logical sense
- **Realistic Percentages**: Follow YAML configuration for relationship density
- **Contact Integration**: Apply contact tags where appropriate
- **Bidirectional Awareness**: Consider creating mutual relationships where logical

## Development Guidelines

### Code Organization
```
app/Services/Demo/Relationships/
├── FragmentRelationshipBuilder.php
├── RelationshipPatternDetector.php
├── ContactTagger.php
├── Config/
│   ├── RelationshipConfig.php
│   └── TaggingConfig.php
├── Models/
│   └── RelationshipCandidate.php
└── Strategies/
    ├── MeetingToTodoStrategy.php
    ├── ProjectTaskSequenceStrategy.php
    └── ChatToActionStrategy.php
```

### Integration with Enhanced Seeders
```php
// Integration pattern in enhanced seeders
class EnhancedDemoDataSeeder extends Seeder
{
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
            
            app(FragmentRelationshipBuilder::class)->buildRelationships($allFragments, $relationshipConfig);
            
            $context->info('<info>✔</info> Fragment relationships built');
        }
    }
    
    private function collectAllDemoFragments(EnhancedDemoSeedContext $context): Collection
    {
        return collect()
            ->merge($context->collection('todo_fragments')->values())
            ->merge($context->collection('contact_fragments')->values())
            ->merge($context->collection('chat_fragments')->values())
            ->merge($context->collection('reminder_fragments')->values());
    }
}
```

## Key Deliverables

### 1. Relationship Builder System
- `FragmentRelationshipBuilder` with pattern detection and relationship creation
- `RelationshipPatternDetector` with multiple relationship detection strategies
- `ContactTagger` for applying contact tags to appropriate fragments

### 2. Configuration Integration
- `RelationshipConfig` and `TaggingConfig` for YAML configuration integration
- Support for scenario-specific relationship rules
- Configurable relationship percentages and types

### 3. Relationship Strategies
- Meeting → Todo follow-up relationships
- Project task sequence relationships
- Chat → Action relationships
- Simple cross-reference relationships

### 4. Integration Components
- Enhanced seeder integration
- Fragment collection and relationship building
- Performance optimization for large datasets

## Implementation Priority

### Phase 1: Core Builder (High Priority)
1. Create basic relationship builder architecture
2. Implement simple relationship detection patterns
3. Add contact tagging system
4. Create configuration integration

### Phase 2: Relationship Strategies (Medium Priority)
1. Implement meeting → todo relationships
2. Add project task sequences
3. Create chat → action connections
4. Add cross-reference detection

### Phase 3: Enhanced Integration (Medium Priority)
1. Integrate with enhanced seeder system
2. Add performance optimization
3. Create comprehensive testing
4. Add relationship validation

## Success Validation

### Functional Testing
```bash
# Test relationship building
php artisan demo:seed-enhanced --scenario=general
php artisan demo:validate-relationships --scenario=general

# Check relationship statistics
php artisan demo:analyze-relationships --scenario=writer --type=all
```

### Quality Assurance
- Relationships feel natural and logical
- Contact tagging applied appropriately
- Relationship percentages match configuration
- Performance impact minimal on seeding time
- No circular or invalid relationships created

This simple but effective fragment relationship builder will add a layer of interconnectedness to the demo data, making it feel more realistic and showcasing the application's relationship capabilities without adding unnecessary complexity.