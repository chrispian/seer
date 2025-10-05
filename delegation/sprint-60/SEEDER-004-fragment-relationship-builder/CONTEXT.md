# SEEDER-004: Fragment Relationship Builder — Context

## Current Fragment Relationship System

### Existing Fragment Model Structure
The Fragment model already has a relationships field for storing connections:

```php
// Current Fragment model relationship field
Schema::table('fragments', function (Blueprint $table) {
    $table->json('relationships')->nullable();
});

// Example relationship structure
'relationships' => [
    [
        'type' => 'related_to',
        'target_fragment_id' => 123,
        'metadata' => ['note' => 'Follow-up from meeting'],
    ],
    [
        'type' => 'blocks',
        'target_fragment_id' => 456,
        'metadata' => ['dependency' => 'Must complete first'],
    ],
],
```

### Current Demo Data Lacks Relationships
The existing demo seeders create isolated fragments with no connections:

```php
// Current TodoSeeder creates isolated todos
Fragment::create([
    'type' => 'todo',
    'title' => $title,
    'message' => $message,
    'relationships' => [], // Always empty
    'metadata' => ['demo_seed' => true],
]);

// ContactSeeder creates isolated contacts
Fragment::create([
    'type' => 'contact',
    'title' => $contactName,
    'relationships' => [], // Always empty
]);
```

**Result**: Demo data feels artificial because there are no natural connections between related items.

## Target Relationship Scenarios

### YAML Configuration for Relationships
From the scenario configurations (SEEDER-001):

```yaml
# general.yaml
fragment_relationships:
  link_percentage: 25  # 25% of fragments will have links
  relationship_types:
    - "related_to"
    - "blocks" 
    - "follows_up"
    - "references"

tagging_strategy:
  contact_tags:
    percentage: 40  # 40% of fragments get contact tags
    apply_to: ["meeting_notes", "todos", "reminders"]
  
  auto_tags:
    enabled: true
    categories: ["urgent", "waiting", "someday", "project", "area"]
```

### Natural Relationship Patterns

#### Meeting → Follow-up Todo Relationships
**Meeting Fragment:**
```json
{
  "type": "contact",
  "title": "Meeting with Sarah Chen",
  "message": "Discussed Q4 infrastructure improvements. Sarah outlined the container migration timeline - targeting December for production deployment. Action items: I need to review the Kubernetes configs by next Friday.",
  "metadata": {
    "demo_category": "contact",
    "has_meeting_notes": true,
    "contact_id": 123
  }
}
```

**Related Todo Fragment:**
```json
{
  "type": "todo",
  "title": "Review Kubernetes configs for Q4 migration",
  "message": "Review Kubernetes configurations by next Friday as discussed with Sarah Chen",
  "relationships": [
    {
      "type": "follows_up",
      "target_fragment_id": 456, // The meeting fragment ID
      "metadata": {
        "created_by": "relationship_builder",
        "context": "Action item from meeting"
      }
    }
  ],
  "tags": ["contact:Sarah Chen", "urgent", "infrastructure"]
}
```

#### Project Task Sequence Relationships
**Planning Todo:**
```json
{
  "type": "todo",
  "title": "Plan home lab network architecture",
  "project_id": 789,
  "relationships": []
}
```

**Implementation Todo:**
```json
{
  "type": "todo", 
  "title": "Configure router for home lab setup",
  "project_id": 789,
  "relationships": [
    {
      "type": "blocks",
      "target_fragment_id": 101, // The planning todo ID
      "metadata": {
        "created_by": "relationship_builder",
        "context": "Depends on network planning"
      }
    }
  ]
}
```

#### Chat → Action Relationships
**Chat Fragment:**
```json
{
  "type": "chat_message",
  "title": "Chat: Planning weekend home lab work",
  "message": "Need to configure the new router this weekend. The current setup isn't handling the VLANs properly.",
  "metadata": {
    "demo_category": "chat_message",
    "chat_session_id": 202
  }
}
```

**Related Todo:**
```json
{
  "type": "todo",
  "title": "Configure router VLANs for home lab",
  "relationships": [
    {
      "type": "references",
      "target_fragment_id": 303, // The chat fragment ID
      "metadata": {
        "created_by": "relationship_builder",
        "context": "Discussed in chat"
      }
    }
  ]
}
```

## Relationship Detection Strategies

### Content-Based Relationship Detection
Unlike complex NLP, use simple keyword and context matching:

```php
// Simple keyword-based relationship detection
class ContentBasedDetector
{
    private array $actionKeywords = [
        'review', 'schedule', 'follow up', 'contact', 'call', 'email',
        'complete', 'finish', 'deploy', 'configure', 'setup'
    ];
    
    private array $timeKeywords = [
        'next week', 'friday', 'monday', 'tomorrow', 'deadline',
        'before', 'after', 'by'
    ];
    
    public function findMeetingActionItems(Fragment $meetingFragment): Collection
    {
        $content = strtolower($meetingFragment->message);
        
        // Look for action-oriented phrases
        $hasActionWords = collect($this->actionKeywords)->some(function ($keyword) use ($content) {
            return str_contains($content, $keyword);
        });
        
        // Look for time references
        $hasTimeReferences = collect($this->timeKeywords)->some(function ($keyword) use ($content) {
            return str_contains($content, $keyword);
        });
        
        return $hasActionWords && $hasTimeReferences;
    }
}
```

### Project-Based Relationship Detection
Group fragments by project and find logical sequences:

```php
// Project task sequence detection
class ProjectSequenceDetector
{
    public function findTaskSequences(Collection $projectTodos): Collection
    {
        // Sort by created_at to find chronological order
        $sortedTodos = $projectTodos->sortBy('created_at');
        
        $sequences = collect();
        
        $sortedTodos->sliding(2)->each(function ($pair) use ($sequences) {
            [$earlier, $later] = $pair;
            
            if ($this->shouldCreateDependency($earlier, $later)) {
                $sequences->push(new RelationshipCandidate(
                    source: $later,
                    target: $earlier,
                    type: 'blocks',
                    confidence: 0.7
                ));
            }
        });
        
        return $sequences;
    }
    
    private function shouldCreateDependency(Fragment $earlier, Fragment $later): bool
    {
        // Simple heuristics for task dependencies
        $earlierTitle = strtolower($earlier->title);
        $laterTitle = strtolower($later->title);
        
        // Planning tasks usually come before implementation
        if (str_contains($earlierTitle, 'plan') && !str_contains($laterTitle, 'plan')) {
            return true;
        }
        
        // Setup tasks usually come before configuration
        if (str_contains($earlierTitle, 'setup') && str_contains($laterTitle, 'configure')) {
            return true;
        }
        
        return false;
    }
}
```

### Contact Tagging Logic
Apply contact tags to relevant fragments:

```php
// Contact tagging based on content and context
class ContactTagger
{
    public function shouldApplyContactTag(Fragment $fragment, Fragment $contact): bool
    {
        // Same vault relationship
        if ($fragment->vault === $contact->vault) {
            return true;
        }
        
        // Meeting notes always get contact tags
        if ($fragment->metadata['demo_category'] === 'contact' && 
            $fragment->metadata['has_meeting_notes'] === true) {
            return true;
        }
        
        // Todos that mention meetings or follow-ups
        $content = strtolower($fragment->message);
        $meetingKeywords = ['meeting', 'discussed', 'talked', 'follow up', 'action item'];
        
        return collect($meetingKeywords)->some(function ($keyword) use ($content) {
            return str_contains($content, $keyword);
        });
    }
    
    public function generateContactTag(Fragment $contact): string
    {
        // Extract contact name for tagging
        $contactName = $this->extractContactName($contact);
        return "contact:{$contactName}";
    }
    
    private function extractContactName(Fragment $contact): string
    {
        // For contact fragments, extract name from title
        $title = $contact->title;
        
        // Remove common prefixes
        $title = preg_replace('/^(Meeting with |Call with |Email from )/i', '', $title);
        
        // Take first two words as name
        $words = explode(' ', trim($title));
        return implode(' ', array_slice($words, 0, 2));
    }
}
```

## Integration with Enhanced Seeders

### Timing in Seeder Flow
Relationship building must happen after all fragments are created:

```php
// Enhanced seeder integration pattern
class EnhancedDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Phase 1: Create base data
        $this->runBaseSeeders($context);
        
        // Phase 2: Build relationships (after all fragments exist)
        $this->buildFragmentRelationships($context);
        
        // Phase 3: Apply final enhancements
        $this->applyFinalEnhancements($context);
    }
    
    private function buildFragmentRelationships(EnhancedDemoSeedContext $context): void
    {
        $relationshipConfig = $this->scenarioConfig->getFragmentRelationships();
        
        if ($relationshipConfig->getLinkPercentage() > 0) {
            $allFragments = $this->collectAllDemoFragments($context);
            
            $relationshipBuilder = app(FragmentRelationshipBuilder::class);
            $relationshipBuilder->buildRelationships($allFragments, $relationshipConfig);
            
            $context->info('<info>✔</info> Fragment relationships built');
        }
    }
}
```

### Fragment Collection Strategy
Collect fragments from context for relationship building:

```php
// Collect fragments from enhanced seeder context
private function collectAllDemoFragments(EnhancedDemoSeedContext $context): Collection
{
    $fragments = collect();
    
    // Todo fragments
    $fragments = $fragments->merge($context->collection('todo_fragments')->values());
    
    // Contact fragments (including meeting notes)
    $fragments = $fragments->merge($context->collection('contact_fragments')->values());
    
    // Chat message fragments
    $fragments = $fragments->merge($context->collection('chat_fragments')->values());
    
    // Reminder fragments (if any)
    $fragments = $fragments->merge($context->collection('reminder_fragments')->values());
    
    return $fragments->filter(function (Fragment $fragment) {
        // Only process demo fragments
        return $fragment->metadata['demo_seed'] === true;
    });
}
```

## Performance Considerations

### Relationship Building Complexity
For simple relationships, performance should be O(n²) or better:

```php
// Efficient relationship detection
class EfficientRelationshipBuilder
{
    public function buildRelationships(Collection $fragments, RelationshipConfig $config): void
    {
        // Group fragments by type for efficient processing
        $fragmentsByType = $fragments->groupBy('type');
        
        // Build type-specific relationship maps
        $contactFragments = $fragmentsByType->get('contact', collect());
        $todoFragments = $fragmentsByType->get('todo', collect());
        $chatFragments = $fragmentsByType->get('chat_message', collect());
        
        // Process relationships efficiently
        $this->processContactToTodoRelationships($contactFragments, $todoFragments, $config);
        $this->processProjectTaskSequences($todoFragments, $config);
        $this->processChatToActionRelationships($chatFragments, $todoFragments, $config);
    }
    
    private function processContactToTodoRelationships(
        Collection $contacts, 
        Collection $todos, 
        RelationshipConfig $config
    ): void {
        // Only process contacts with meeting notes
        $meetingContacts = $contacts->where('metadata.has_meeting_notes', true);
        
        foreach ($meetingContacts as $contact) {
            $candidateTodos = $this->findCandidateTodos($contact, $todos);
            $this->createMeetingFollowUpRelationships($contact, $candidateTodos, $config);
        }
    }
}
```

### Memory Usage Optimization
Handle large fragment collections efficiently:

```php
// Memory-efficient relationship processing
class MemoryEfficientBuilder
{
    public function buildRelationships(Collection $fragments, RelationshipConfig $config): void
    {
        // Process in chunks to avoid memory issues
        $fragments->chunk(100)->each(function (Collection $chunk) use ($fragments, $config) {
            $this->processFragmentChunk($chunk, $fragments, $config);
        });
    }
    
    private function processFragmentChunk(
        Collection $chunk, 
        Collection $allFragments, 
        RelationshipConfig $config
    ): void {
        foreach ($chunk as $fragment) {
            $this->buildRelationshipsForFragment($fragment, $allFragments, $config);
        }
    }
}
```

## Relationship Quality Metrics

### Success Criteria for Relationships
- **Logical Consistency**: Relationships should make sense contextually
- **Temporal Validity**: No relationships that violate timeline logic  
- **Percentage Targets**: Meet YAML configuration percentage requirements
- **Contact Integration**: Appropriate contact tagging applied
- **Performance**: Relationship building completes in <30 seconds

### Relationship Validation
```php
// Relationship quality validation
class RelationshipValidator
{
    public function validateRelationships(Collection $fragments): ValidationResult
    {
        $issues = collect();
        
        foreach ($fragments as $fragment) {
            $relationships = $fragment->relationships ?? [];
            
            foreach ($relationships as $relationship) {
                // Check if target fragment exists
                $targetExists = $fragments->contains('id', $relationship['target_fragment_id']);
                if (!$targetExists) {
                    $issues->push("Fragment {$fragment->id} references non-existent fragment {$relationship['target_fragment_id']}");
                }
                
                // Check temporal consistency
                if (!$this->isTemporallyValid($fragment, $relationship, $fragments)) {
                    $issues->push("Fragment {$fragment->id} has temporally invalid relationship");
                }
            }
        }
        
        return new ValidationResult($issues->isEmpty(), $issues);
    }
}
```

## Success Criteria

### Functional Requirements
- [ ] 25% of fragments have meaningful relationships
- [ ] Relationship types correctly implemented (related_to, blocks, follows_up, references)
- [ ] Contact tagging applied to 40% of appropriate fragments
- [ ] Natural connections between meeting notes and follow-up todos
- [ ] Project task sequences create logical dependencies

### Quality Requirements
- [ ] Relationships feel natural and enhance demo realism
- [ ] No circular or invalid relationships created
- [ ] Temporal consistency maintained (no future items blocking past items)
- [ ] Performance impact minimal (<30 seconds for relationship building)
- [ ] Configuration rules from YAML respected

### Integration Requirements
- [ ] Clean integration with enhanced seeder system
- [ ] Works with Fragment model relationship structure
- [ ] Respects scenario-specific relationship rules
- [ ] Maintains existing demo data quality and patterns

This fragment relationship system will add a crucial layer of interconnectedness to the demo data, transforming isolated fragments into a realistic web of related content that better demonstrates the application's relationship and tagging capabilities.