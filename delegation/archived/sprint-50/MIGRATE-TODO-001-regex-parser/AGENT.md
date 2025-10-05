# MIGRATE-TODO-001: Regex/Rule-Based Todo Parser

## Agent Profile
**Type**: Senior Engineer/Code Reviewer  
**Specialization**: DSL Architecture, Text Parsing, Laravel Development

## Task Overview
Create a deterministic regex and rule-based parser to replace AI-dependent todo text parsing in the DSL framework.

## Context
The current `/todo` command uses `ai.generate` to parse user input like "buy groceries tomorrow" into structured todo data. This creates non-deterministic behavior and dependencies on AI services. We need a structured approach using regex patterns and business rules.

## Technical Requirements

### **Parser Design**
- **Input**: Raw text from todo command
- **Output**: Structured data (title, description, due_date, priority, tags)
- **Method**: Regex patterns + business rule engine
- **Fallback**: Graceful degradation for unparseable input

### **Pattern Recognition**
- **Due Dates**: "tomorrow", "next week", "Friday", "2024-01-15"
- **Priority**: "urgent", "important", "low priority", "!!!", "P1/P2/P3"
- **Tags**: "#work", "#personal", "@context"
- **Time**: "9am", "14:30", "in 2 hours"

### **Business Rules**
- Default priority: "medium" if unspecified
- Default due_date: null if unspecified
- Title extraction: First meaningful phrase
- Description: Remaining text after title/metadata extraction

## Implementation Plan

### **Phase 1: Parser Service**
Create `TodoTextParser` service with regex pattern library:
```php
class TodoTextParser {
    public function parse(string $text): array
    public function extractDueDate(string $text): ?Carbon
    public function extractPriority(string $text): string
    public function extractTags(string $text): array
    public function extractTitle(string $text): string
}
```

### **Phase 2: DSL Step Integration**
Create `text.parse` step type for general text parsing:
```yaml
steps:
  - type: text.parse
    with:
      input: "{{ ctx.body }}"
      parser: "todo"
      rules:
        - extract_due_date: true
        - extract_priority: true
        - extract_tags: true
    output: parsed_todo
```

### **Phase 3: Todo Command Migration**
Update `/todo` command to use deterministic parsing instead of `ai.generate`.

## Success Criteria
- [ ] Parser handles 90%+ of common todo patterns correctly
- [ ] Zero AI dependencies in todo parsing flow
- [ ] Graceful fallback for complex/unparseable input
- [ ] Performance: <10ms parsing time
- [ ] Backward compatibility with existing todo command interface

## Files to Modify
- `app/Services/Commands/DSL/Steps/TextParseStep.php` (new)
- `app/Services/TodoTextParser.php` (new)
- `fragments/commands/todo/command.yaml`
- Tests for parser service and DSL step

## Testing Strategy
- Unit tests for regex patterns and edge cases
- Integration tests with actual todo command
- Performance benchmarks vs AI parsing
- User acceptance testing with common todo patterns