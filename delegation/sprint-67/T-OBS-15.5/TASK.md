# T-OBS-15.5: Create ObsidianFragmentPipeline service

**Task Code**: `T-OBS-15.5`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P0-CRITICAL  
**Status**: `pending`  
**Estimated**: 4-5 hours  
**Dependencies**: None

## Objective

Create deterministic pipeline for intelligent type inference, tagging, and metadata extraction based on paths, front matter, and content patterns WITHOUT AI.

## Acceptance Criteria

- [ ] Class created: `app/Services/Obsidian/ObsidianFragmentPipeline.php`
- [ ] Path-based type inference (e.g., `Contacts/` → `contact`, `Meetings/` → `meeting`)
- [ ] Front matter `type:` field extraction (highest priority)
- [ ] Content pattern matching for types (checkbox lists → task, meeting headers → meeting)
- [ ] Folder-based tag generation (all parent folders become tags)
- [ ] Front matter tag extraction (array or comma-separated)
- [ ] Content hashtag extraction (#tag syntax)
- [ ] Custom front matter field extraction (author, date, project, priority, status, etc.)
- [ ] Default fallback to 'note' type
- [ ] Returns enriched fragment data DTO

## Files

- `app/Services/Obsidian/ObsidianFragmentPipeline.php` (new)
- `app/DTOs/EnrichedObsidianFragment.php` (new)

## Technical Notes

```php
// Type inference priority:
// 1. Front matter 'type:' field (explicit)
// 2. Path-based rules (Contacts → contact, Meetings → meeting)
// 3. Content patterns (checkbox lists, meeting headers)
// 4. Default: 'note'

// Path rules to implement:
$pathRules = [
    'Contacts' => 'contact',
    'People' => 'contact',
    'Meetings' => 'meeting',
    'Meeting Notes' => 'meeting',
    'Tasks' => 'task',
    'TODO' => 'task',
    'Projects' => 'project',
    'Ideas' => 'idea',
    'References' => 'reference',
    'Clippings' => 'clip',
    'Bookmarks' => 'bookmark',
    'Daily Notes' => 'log',
    'Journal' => 'log',
];

// Content patterns:
$contentPatterns = [
    '/^#+ Meeting:/' => 'meeting',
    '/^- \[[ x]\]/' => 'task', // Checkbox syntax
    '/^## Action Items/' => 'meeting',
    '/^Project:/' => 'project',
];

// Custom metadata fields to extract from front matter:
$customFields = [
    'author', 'date', 'project', 'priority', 
    'status', 'category', 'url', 'source_url'
];
```

## Example Input/Output

```php
// Input file: Contacts/John Doe.md
---
type: contact
tags: [work, sales]
email: john@example.com
company: Acme Corp
---
# John Doe
Sales contact from Acme...

// Output:
[
    'type' => 'contact', // from front matter
    'tags' => ['contacts', 'work', 'sales', 'obsidian'], // folder + front matter + source
    'custom_metadata' => [
        'email' => 'john@example.com',
        'company' => 'Acme Corp',
    ],
]
```

## Testing Requirements

- [ ] Test path-based inference for all folder types
- [ ] Test front matter type override
- [ ] Test content pattern matching
- [ ] Test tag generation from multiple sources
- [ ] Test custom field extraction
