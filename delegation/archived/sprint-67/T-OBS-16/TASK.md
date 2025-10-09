# T-OBS-16: Create WikilinkParser service

**Task Code**: `T-OBS-16`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P1-HIGH  
**Status**: `pending`  
**Estimated**: 3-4 hours  
**Dependencies**: None

## Objective

Create service to extract and parse Obsidian wikilink syntax from markdown content.

## Acceptance Criteria

- [ ] Class created: `app/Services/Obsidian/WikilinkParser.php`
- [ ] Extracts `[[target]]` basic links
- [ ] Parses `[[target#heading]]` anchor syntax
- [ ] Parses `[[target|alias]]` display text syntax
- [ ] Returns structured array: `[target, heading, alias, position]`
- [ ] Handles nested brackets gracefully
- [ ] Handles multiple links per document
- [ ] Returns empty array if no links found

## Files

- `app/Services/Obsidian/WikilinkParser.php` (new)

## Technical Notes

```php
// Link patterns
// Basic: [[Project Plan]]
// Anchor: [[Project Plan#Goals]]
// Alias: [[Project Plan|The Plan]]
// Combined: [[Project Plan#Goals|See Goals]]

// Regex pattern (simplified):
// /\[\[([^\]]+)\]\]/g

// Parse result structure:
[
    [
        'raw' => '[[Project Plan#Goals|See Goals]]',
        'target' => 'Project Plan',
        'heading' => 'Goals',
        'alias' => 'See Goals',
        'position' => 42,
    ],
]
```

## Testing Requirements

- [ ] Test basic wikilink extraction
- [ ] Test anchor syntax parsing
- [ ] Test alias syntax parsing
- [ ] Test combined anchor + alias
- [ ] Test multiple links in document
- [ ] Test edge cases (nested brackets, etc.)
