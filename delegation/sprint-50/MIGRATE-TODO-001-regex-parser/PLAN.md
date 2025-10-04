# MIGRATE-TODO-001: Implementation Plan

## Overview
Replace AI-dependent todo parsing with deterministic regex and rule-based system.

## Phase 1: Parser Service Development (4-6 hours)

### **Step 1.1: Create TodoTextParser Service**
**Location**: `app/Services/TodoTextParser.php`

```php
<?php

namespace App\Services;

use Carbon\Carbon;

class TodoTextParser
{
    private array $dueDatePatterns = [
        '/\b(today)\b/i' => 'today',
        '/\b(tomorrow)\b/i' => 'tomorrow', 
        '/\bnext (week|month)\b/i' => 'next_period',
        '/\bin (\d+) (days?|weeks?)\b/i' => 'relative_duration',
        '/\b(\d{4}-\d{2}-\d{2})\b/' => 'iso_date',
        '/\b(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)\b/i' => 'weekday',
    ];

    private array $priorityPatterns = [
        '/\b(urgent|critical|asap|!!!)\b/i' => 'urgent',
        '/\b(important|high|!!)\b/i' => 'high', 
        '/\b(low|later|someday|!)\b/i' => 'low',
        '/\bP([1-4])\b/i' => 'priority_number',
    ];

    private array $tagPatterns = [
        '/#([a-zA-Z0-9_]+)/' => 'hashtag',
        '/@([a-zA-Z0-9_]+)/' => 'context',
    ];

    public function parse(string $text): array
    {
        $result = [
            'title' => '',
            'priority' => 'medium',
            'due_date' => null,
            'tags' => [],
            'raw_text' => $text,
        ];

        // Extract metadata first
        $result['due_date'] = $this->extractDueDate($text);
        $result['priority'] = $this->extractPriority($text);
        $result['tags'] = $this->extractTags($text);
        
        // Extract title after removing metadata
        $result['title'] = $this->extractTitle($text);

        return $result;
    }
    
    // Implementation methods...
}
```

### **Step 1.2: Implement Pattern Matching Methods**
- `extractDueDate()`: Process date patterns and return Carbon instance
- `extractPriority()`: Match priority indicators
- `extractTags()`: Find hashtags and context markers
- `extractTitle()`: Clean text and extract meaningful title

### **Step 1.3: Add Business Rule Processing**
- Default value assignment
- Title cleaning and capitalization
- Date validation and normalization
- Tag deduplication and formatting

## Phase 2: DSL Step Integration (2-3 hours)

### **Step 2.1: Create TextParseStep Class**
**Location**: `app/Services/Commands/DSL/Steps/TextParseStep.php`

```php
<?php

namespace App\Services\Commands\DSL\Steps;

use App\Services\TodoTextParser;

class TextParseStep extends Step
{
    public function __construct(private TodoTextParser $parser) {}

    public function execute(array $config, array $context, bool $dryRun = false): array
    {
        $input = $config['with']['input'] ?? '';
        $parserType = $config['with']['parser'] ?? 'todo';
        
        if ($dryRun) {
            return ['parsed' => 'DRY_RUN_RESULT'];
        }

        switch ($parserType) {
            case 'todo':
                return $this->parser->parse($input);
            default:
                throw new \InvalidArgumentException("Unknown parser type: {$parserType}");
        }
    }
}
```

### **Step 2.2: Register in StepFactory**
Add `'text.parse' => TextParseStep::class` to `$stepClasses` array.

### **Step 2.3: Add Configuration Validation**
- Validate required `input` parameter
- Validate `parser` type selection
- Add optional `rules` configuration

## Phase 3: Validation Step Enhancement (1-2 hours)

### **Step 3.1: Enhance ValidateStep**
Ensure existing `ValidateStep` can handle todo data validation:
- Required field validation
- Data type checking
- Enum value validation (priority levels)
- Date format validation

### **Step 3.2: Create Todo Validation Rules**
```php
private array $todoValidationRules = [
    'title' => 'required|string|max:255|min:1',
    'priority' => 'required|in:low,medium,high,urgent',
    'due_date' => 'nullable|date',
    'tags' => 'array',
    'tags.*' => 'string|max:50',
];
```

## Phase 4: Command Migration (2-3 hours)

### **Step 4.1: Update Todo Command YAML**
**Location**: `fragments/commands/todo/command.yaml`

Replace AI-based parsing:
```yaml
name: "Create Todo"
description: "Create a new todo item with smart parsing"
requires:
  capabilities: ["model.create", "text.parse", "validate"]

steps:
  - type: text.parse
    id: parse_input
    with:
      input: "{{ ctx.body }}"
      parser: "todo"
    
  - type: validate
    id: validate_todo
    with:
      data: "{{ steps.parse_input.output }}"
      rules:
        title: "required|string|max:255"
        priority: "in:low,medium,high,urgent"
        due_date: "nullable|date"
    
  - type: model.create
    id: create_fragment
    with:
      model: fragment
      data:
        message: "{{ steps.parse_input.output.title }}"
        type: "todo"
        state:
          status: "pending"
          priority: "{{ steps.parse_input.output.priority }}"
          due_date: "{{ steps.parse_input.output.due_date }}"
          tags: "{{ steps.parse_input.output.tags }}"
        importance: "{{ steps.parse_input.output.priority == 'urgent' ? 5 : (steps.parse_input.output.priority == 'high' ? 4 : 3) }}"
    
  - type: notify
    with:
      message: "Todo created: {{ steps.parse_input.output.title }}"
      type: "success"
```

### **Step 4.2: Remove AI Dependencies**
- Remove `ai.generate` capability requirement
- Delete or archive AI prompts
- Update command documentation

### **Step 4.3: Maintain Backward Compatibility**
- Preserve command interface (`/todo "text"`)
- Ensure same output structure
- Keep error handling patterns

## Phase 5: Testing & Validation (3-4 hours)

### **Step 5.1: Unit Testing**
**Location**: `tests/Unit/Services/TodoTextParserTest.php`

Test cases:
- Simple todos: "buy groceries"
- Complex todos: "finish report tomorrow #work urgent"
- Edge cases: empty input, special characters
- Date parsing: various date formats
- Priority extraction: different priority indicators

### **Step 5.2: Integration Testing**
**Location**: `tests/Feature/Commands/TodoCommandTest.php`

Test scenarios:
- End-to-end todo creation
- DSL step integration
- Validation error handling
- Performance benchmarks

### **Step 5.3: Performance Testing**
Compare parsing times:
- Old AI approach: ~500-2000ms
- New regex approach: Target <10ms
- Memory usage comparison
- Concurrency testing

## Phase 6: Documentation & Rollout (1-2 hours)

### **Step 6.1: Update Documentation**
- Command usage examples
- Parser configuration options
- Migration notes for developers
- Performance metrics

### **Step 6.2: Gradual Rollout**
- Feature flag for parser selection
- A/B testing with user subset
- Monitoring and metrics collection
- Rollback plan preparation

## Success Criteria Validation

### **Functional Requirements**
- [ ] Parses 90%+ of common todo patterns correctly
- [ ] Zero AI dependencies in parsing flow
- [ ] Graceful fallback for unparseable input
- [ ] Performance <10ms parsing time
- [ ] Backward compatibility maintained

### **Quality Requirements**
- [ ] Comprehensive test coverage (>90%)
- [ ] No performance regression
- [ ] Error handling for edge cases
- [ ] Documentation complete and accurate

### **Deployment Requirements**
- [ ] Safe rollout strategy
- [ ] Monitoring in place
- [ ] Rollback plan tested
- [ ] User communication prepared

## Risk Mitigation

### **Technical Risks**
- **Pattern Coverage**: Extensive testing with user examples
- **Performance**: Benchmark against current implementation
- **Accuracy**: Manual validation of parsing results

### **Business Risks**
- **User Experience**: Gradual rollout with feedback collection
- **Compatibility**: Comprehensive regression testing
- **Adoption**: Clear communication of benefits

## Timeline
**Total Estimated Time**: 13-20 hours  
**Dependencies**: None (can start immediately)  
**Parallel Work**: Can develop alongside other MIGRATE-TODO tasks  
**Delivery**: Phase-by-phase with incremental testing