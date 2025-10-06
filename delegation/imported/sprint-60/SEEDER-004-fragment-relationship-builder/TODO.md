# SEEDER-004: Fragment Relationship Builder — TODO

## Implementation Checklist

### Phase 1: Core Relationship Infrastructure ⏱️ 2-3h

#### Relationship Configuration System
- [ ] Create `app/Services/Demo/Relationships/Config/RelationshipConfig.php`
  - [ ] Constructor with `linkPercentage`, `relationshipTypes`, `taggingStrategy`
  - [ ] `getLinkPercentage(): int` method
  - [ ] `getRelationshipTypes(): array` method
  - [ ] `getTaggingStrategy(): TaggingConfig` method
  - [ ] `static fromYamlConfig(array $yamlConfig): self` method
  - [ ] Default values for missing configuration

- [ ] Create `app/Services/Demo/Relationships/Config/TaggingConfig.php`
  - [ ] Constructor with contact tag percentage, apply-to list, auto tags settings
  - [ ] `getContactTagPercentage(): int` method
  - [ ] `getApplyTo(): array` method (meeting_notes, todos, reminders)
  - [ ] `isAutoTagsEnabled(): bool` method
  - [ ] `getAutoTagCategories(): array` method
  - [ ] `static fromYamlConfig(array $yamlConfig): self` method

#### Relationship Candidate Model
- [ ] Create `app/Services/Demo/Relationships/Models/RelationshipCandidate.php`
  - [ ] Constructor with `source`, `target`, `type`, `confidence`, `metadata`
  - [ ] Getter methods for all properties
  - [ ] `toRelationshipArray(): array` method for Fragment model compatibility
  - [ ] Validation for relationship types and confidence values

#### Main Relationship Builder
- [ ] Create `app/Services/Demo/Relationships/FragmentRelationshipBuilder.php`
  - [ ] Constructor with `RelationshipPatternDetector`, `ContactTagger`
  - [ ] `buildRelationships(Collection $fragments, RelationshipConfig $config): void` method
  - [ ] `detectAllRelationships(Collection $fragments): Collection` method
  - [ ] `selectRelationshipsByPercentage(Collection $candidates, RelationshipConfig $config): Collection` method
  - [ ] `createFragmentRelationships(Collection $relationships): void` method
  - [ ] Logging for relationship building start/completion
  - [ ] Error handling for relationship creation failures

### Phase 2: Relationship Detection Strategies ⏱️ 2-3h

#### Pattern Detector
- [ ] Create `app/Services/Demo/Relationships/RelationshipPatternDetector.php`
  - [ ] `detectMeetingToTodoRelationships(Collection $fragments): Collection` method
  - [ ] `detectProjectTaskRelationships(Collection $fragments): Collection` method
  - [ ] `detectChatToActionRelationships(Collection $fragments): Collection` method
  - [ ] `detectCrossReferences(Collection $fragments): Collection` method

#### Meeting → Todo Detection
- [ ] Implement `findRelatedTodos(Fragment $meetingFragment, Collection $todoFragments): Collection`
  - [ ] Look for action keywords in meeting content (review, schedule, follow up, etc.)
  - [ ] Find todos in same vault created after meeting
  - [ ] Limit to 2 related todos per meeting for realism
  - [ ] Calculate confidence based on keyword matches and timing

- [ ] Implement `calculateMeetingTodoConfidence(Fragment $meeting, Fragment $todo): float`
  - [ ] Check for shared keywords
  - [ ] Consider temporal proximity
  - [ ] Factor in vault relationship
  - [ ] Return confidence score 0.0-1.0

#### Project Task Sequence Detection
- [ ] Implement `findTaskSequences(Collection $projectTodos): Collection`
  - [ ] Sort todos by created_at timestamp
  - [ ] Use sliding window to find sequential pairs
  - [ ] Apply dependency detection logic
  - [ ] Create "blocks" relationships for dependencies

- [ ] Implement `shouldCreateDependency(Fragment $earlier, Fragment $later): bool`
  - [ ] Planning → Implementation patterns
  - [ ] Setup → Configuration patterns
  - [ ] Research → Implementation patterns
  - [ ] Generic dependency indicators

#### Chat → Action Detection
- [ ] Implement `findChatRelatedActions(Fragment $chatFragment, Collection $actionFragments): Collection`
  - [ ] Extract keywords from chat content
  - [ ] Find actions with similar keywords
  - [ ] Consider temporal proximity (actions after chats)
  - [ ] Limit to reasonable number of connections

- [ ] Implement `calculateChatActionConfidence(Fragment $chat, Fragment $action): float`
  - [ ] Keyword similarity scoring
  - [ ] Temporal relationship scoring
  - [ ] Context relevance assessment

#### Cross-Reference Detection
- [ ] Implement `detectCrossReferences(Collection $fragments): Collection`
  - [ ] Extract meaningful keywords from fragment content
  - [ ] Find fragments with shared keywords
  - [ ] Create "related_to" relationships
  - [ ] Apply conservative confidence scoring

- [ ] Implement keyword extraction utilities:
  - [ ] `extractKeywords(Fragment $fragment): Collection` method
  - [ ] `findFragmentsByKeywords(Collection $keywords, Collection $fragments, Fragment $exclude): Collection` method
  - [ ] Keyword filtering (exclude common words)
  - [ ] Priority weighting for technical terms, names, places

#### Contact Tagger
- [ ] Create `app/Services/Demo/Relationships/ContactTagger.php`
  - [ ] `applyContactTags(Collection $fragments, TaggingConfig $config): void` method
  - [ ] `selectTaggableFragments(Collection $fragments, TaggingConfig $config): Collection` method
  - [ ] `selectTaggingTargets(Collection $taggableFragments, int $percentage): Collection` method
  - [ ] `findRelatedContact(Fragment $fragment, Collection $contactFragments): ?Fragment` method
  - [ ] `addContactTag(Fragment $fragment, Fragment $contact): void` method
  - [ ] `extractContactName(Fragment $contact): string` method

#### Contact Tag Selection Logic
- [ ] Implement taggable fragment selection:
  - [ ] Filter by category (meeting_notes, todos, reminders)
  - [ ] Include meeting notes with has_meeting_notes metadata
  - [ ] Exclude fragments that already have contact tags

- [ ] Implement target selection strategy:
  - [ ] Prioritize meeting notes (highest priority)
  - [ ] Prioritize work vault todos (medium priority)
  - [ ] Random selection within priority groups
  - [ ] Respect percentage limits from configuration

- [ ] Implement contact matching logic:
  - [ ] Prefer contacts from same vault
  - [ ] Fallback to random contact selection
  - [ ] Avoid duplicate contact tags on same fragment

### Phase 3: Integration with Enhanced Seeders ⏱️ 1-2h

#### Enhanced Seeder Integration
- [ ] Update `database/seeders/Demo/EnhancedDemoDataSeeder.php`
  - [ ] Add `buildFragmentRelationships(EnhancedDemoSeedContext $context): void` method
  - [ ] Add `collectAllDemoFragments(EnhancedDemoSeedContext $context): Collection` method
  - [ ] Integrate relationship building after all fragments created
  - [ ] Add configuration validation and skip logic
  - [ ] Add progress reporting and logging

#### Fragment Collection Strategy
- [ ] Implement fragment collection from context:
  - [ ] Collect todo fragments from context
  - [ ] Collect contact fragments (including meeting notes)
  - [ ] Collect chat message fragments
  - [ ] Collect reminder fragments (if any)
  - [ ] Filter to only demo fragments (demo_seed = true)
  - [ ] Handle empty collections gracefully

#### Configuration Integration
- [ ] Update scenario configuration parsing:
  - [ ] Read fragment_relationships section from YAML
  - [ ] Parse link_percentage and relationship_types
  - [ ] Parse tagging_strategy configuration
  - [ ] Provide sensible defaults for missing values
  - [ ] Validate configuration values

#### Service Provider Registration
- [ ] Create `app/Providers/FragmentRelationshipServiceProvider.php`
  - [ ] Register `FragmentRelationshipBuilder` as singleton
  - [ ] Register `RelationshipPatternDetector` as singleton
  - [ ] Register `ContactTagger` as singleton
  - [ ] Add to `config/app.php` providers array

### Phase 4: Testing and Validation ⏱️ 1h

#### Unit Tests
- [ ] Create `tests/Unit/Services/Demo/Relationships/FragmentRelationshipBuilderTest.php`
  - [ ] Test `buildRelationships()` with different configurations
  - [ ] Test relationship percentage calculations
  - [ ] Test relationship selection logic
  - [ ] Test fragment relationship creation

- [ ] Create `tests/Unit/Services/Demo/Relationships/RelationshipPatternDetectorTest.php`
  - [ ] Test meeting to todo relationship detection
  - [ ] Test project task sequence detection
  - [ ] Test chat to action relationship detection
  - [ ] Test cross-reference detection

- [ ] Create `tests/Unit/Services/Demo/Relationships/ContactTaggerTest.php`
  - [ ] Test contact tag application logic
  - [ ] Test fragment selection for tagging
  - [ ] Test contact name extraction
  - [ ] Test tagging percentage compliance

- [ ] Create `tests/Unit/Services/Demo/Relationships/Config/RelationshipConfigTest.php`
  - [ ] Test YAML configuration parsing
  - [ ] Test default value handling
  - [ ] Test configuration validation

#### Integration Tests
- [ ] Create `tests/Feature/Demo/Relationships/RelationshipIntegrationTest.php`
  - [ ] Test complete relationship building flow
  - [ ] Test integration with enhanced seeder system
  - [ ] Test scenario configuration integration
  - [ ] Test fragment data integrity during relationship building

#### Quality Validation Tests
- [ ] Create `tests/Feature/Demo/Relationships/RelationshipQualityTest.php`
  - [ ] Test relationship percentages match configuration
  - [ ] Test temporal consistency of relationships
  - [ ] Test logical validity of relationships
  - [ ] Test contact tag distribution

#### Performance Tests
- [ ] Create `tests/Feature/Demo/Relationships/RelationshipPerformanceTest.php`
  - [ ] Benchmark relationship building time
  - [ ] Test memory usage during large dataset processing
  - [ ] Test performance with different relationship percentages
  - [ ] Validate no performance regression in seeding

### Documentation and Commands ⏱️ 30min

#### Console Commands
- [ ] Create `app/Console/Commands/Demo/AnalyzeRelationshipsCommand.php`
  - [ ] Command signature: `demo:analyze-relationships {--scenario=} {--type=all}`
  - [ ] Display relationship statistics
  - [ ] Show relationship type distribution
  - [ ] Report contact tag coverage
  - [ ] Export relationship analysis to file

- [ ] Create `app/Console/Commands/Demo/ValidateRelationshipsCommand.php`
  - [ ] Command signature: `demo:validate-relationships {--scenario=} {--check=all}`
  - [ ] Validate temporal consistency
  - [ ] Check for circular relationships
  - [ ] Verify relationship integrity
  - [ ] Report validation issues

#### Documentation
- [ ] Create relationship system documentation:
  - [ ] Overview of relationship types and detection strategies
  - [ ] Configuration guide for scenario YAML files
  - [ ] Troubleshooting guide for relationship issues
  - [ ] Performance optimization recommendations

## Acceptance Criteria

### Functional Requirements
- [ ] 25% of fragments have meaningful relationships (configurable via YAML)
- [ ] Relationship types correctly implemented (related_to, blocks, follows_up, references)
- [ ] Contact tagging applied to 40% of appropriate fragments (configurable)
- [ ] Natural connections between meeting notes and follow-up todos
- [ ] Project task sequences create logical dependencies

### Quality Requirements
- [ ] Relationships feel natural and enhance demo realism
- [ ] No circular or invalid relationships created
- [ ] Temporal consistency maintained (no future items blocking past items)
- [ ] Performance impact minimal (<30 seconds for relationship building)
- [ ] Configuration rules from YAML respected accurately

### Integration Requirements
- [ ] Clean integration with enhanced seeder system
- [ ] Works with Fragment model relationship structure
- [ ] Respects scenario-specific relationship rules
- [ ] Maintains existing demo data quality and patterns
- [ ] Handles empty or small fragment collections gracefully

## Success Validation Commands

```bash
# Test relationship building with different scenarios
php artisan demo:seed-enhanced --scenario=general
php artisan demo:seed-enhanced --scenario=writer
php artisan demo:seed-enhanced --scenario=developer

# Analyze relationship statistics
php artisan demo:analyze-relationships --scenario=general --type=all
php artisan demo:analyze-relationships --scenario=writer --type=meeting_todo
php artisan demo:analyze-relationships --scenario=developer --type=project_sequence

# Validate relationship quality
php artisan demo:validate-relationships --scenario=general --check=temporal
php artisan demo:validate-relationships --scenario=writer --check=logic
php artisan demo:validate-relationships --scenario=developer --check=all

# Check relationship percentages
php artisan tinker --execute="
\$fragments = App\Models\Fragment::where('metadata->demo_seed', true)->get();
\$withRelationships = \$fragments->filter(fn(\$f) => count(\$f->relationships ?? []) > 0);
echo 'Fragments with relationships: ' . \$withRelationships->count() . '/' . \$fragments->count();
echo 'Percentage: ' . round((\$withRelationships->count() / \$fragments->count()) * 100, 1) . '%';
"

# Analyze contact tag distribution
php artisan tinker --execute="
\$fragments = App\Models\Fragment::where('metadata->demo_seed', true)->get();
\$withContactTags = \$fragments->filter(function(\$f) {
    return collect(\$f->tags ?? [])->some(fn(\$tag) => str_starts_with(\$tag, 'contact:'));
});
echo 'Fragments with contact tags: ' . \$withContactTags->count() . '/' . \$fragments->count();
echo 'Percentage: ' . round((\$withContactTags->count() / \$fragments->count()) * 100, 1) . '%';
"
```

## Relationship Quality Examples

### Meeting → Todo Follow-up Example
**Meeting Fragment:**
```json
{
  "id": 123,
  "type": "contact",
  "title": "Meeting with Sarah Chen",
  "message": "Discussed Q4 infrastructure improvements. Action items: review Kubernetes configs by Friday.",
  "metadata": {"demo_category": "contact", "has_meeting_notes": true}
}
```

**Related Todo Fragment:**
```json
{
  "id": 456,
  "type": "todo",
  "title": "Review Kubernetes configs for Q4 migration", 
  "relationships": [
    {
      "type": "follows_up",
      "target_fragment_id": 123,
      "metadata": {"created_by": "relationship_builder", "context": "Action item from meeting"}
    }
  ],
  "tags": ["contact:Sarah Chen", "infrastructure", "urgent"]
}
```

### Project Task Sequence Example
**Planning Todo:**
```json
{
  "id": 789,
  "type": "todo",
  "title": "Plan home lab network architecture",
  "project_id": 100
}
```

**Implementation Todo:**
```json
{
  "id": 790,
  "type": "todo",
  "title": "Configure router for home lab setup",
  "project_id": 100,
  "relationships": [
    {
      "type": "blocks",
      "target_fragment_id": 789,
      "metadata": {"created_by": "relationship_builder", "context": "Task dependency"}
    }
  ]
}
```

## Notes & Considerations

### Simplicity Focus
- Keep relationship detection algorithms simple and understandable
- Avoid complex NLP or machine learning approaches
- Use keyword matching and heuristics for pattern detection
- Focus on obvious, natural relationships rather than subtle connections

### Performance Optimization
- Group fragments by type for efficient processing
- Limit relationship candidates to prevent explosion
- Use confidence scoring to select best relationships
- Process in chunks for large datasets

### Quality Assurance
- Validate all relationships for temporal consistency
- Ensure no circular relationships are created
- Test with different scenario configurations
- Monitor performance impact on seeding time

### Future Extensibility
- Design pattern detector for easy addition of new relationship types
- Create pluggable architecture for custom relationship strategies
- Support for bidirectional relationships if needed
- Extension points for more sophisticated detection algorithms

This simple but effective fragment relationship builder will add crucial interconnectedness to the demo data, making it feel more authentic and realistic while showcasing the application's relationship capabilities without adding unnecessary complexity.