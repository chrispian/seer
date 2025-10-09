# T-OBS-18: Enhance ObsidianMarkdownParser for link extraction

**Task Code**: `T-OBS-18`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P1-HIGH  
**Status**: `pending`  
**Estimated**: 2-3 hours  
**Dependencies**: T-OBS-16

## Objective

Enhance existing parser to extract wikilinks instead of stripping them.

## Acceptance Criteria

- [ ] Inject `WikilinkParser` into `ObsidianMarkdownParser`
- [ ] Extract wikilinks during parsing (before body processing)
- [ ] Add `links` array to `ParsedObsidianNote` DTO
- [ ] Preserve original wikilink syntax in body (don't strip)
- [ ] Handle edge cases: links in code blocks, links in front matter
- [ ] Backwards compatible: existing imports still work

## Files

- `app/Services/Obsidian/ObsidianMarkdownParser.php` (update)
- `app/DTOs/ParsedObsidianNote.php` (update DTO)

## DTO Enhancement

```php
class ParsedObsidianNote extends DataTransferObject
{
    public function __construct(
        public string $title,
        public string $body,
        public array $tags,
        public array $frontMatter,
        public array $links = [], // NEW
    ) {}
}
```

## Testing Requirements

- [ ] Test wikilink extraction during parsing
- [ ] Test links preserved in body
- [ ] Test edge cases (code blocks, front matter)
- [ ] Test backwards compatibility
