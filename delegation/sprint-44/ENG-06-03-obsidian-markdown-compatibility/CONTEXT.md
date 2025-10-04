# ENG-06-03 Obsidian Markdown Compatibility Context

## Technical Architecture

### Obsidian Syntax Patterns
```markdown
# Single embeds
![[fe:note/7Q2M9K]]
![[fe:note/7Q2M9K|Custom Title]]

# Todo anchors
- [ ] Task description ^fe:todo/AB12CD
- [x] Completed task ^fe:todo/XY789Z

# List embeds (fenced blocks)
```fragments
context: proj:Fragments
source: type:todo where:done=false sort:due limit:10
layout: checklist
mode: live
```

# Inline links
[[fe:note/7Q2M9K|Link Text]]
```

### Conversion Architecture
```
app/Services/Markdown/
├── ObsidianParser.php (parse Obsidian syntax)
├── ObsidianExporter.php (generate Obsidian markdown)
├── TransclusionConverter.php (bidirectional conversion)
└── MarkdownValidator.php (validation and error handling)
```

### Integration Points
- **TransclusionSpec Model**: Core data structure for conversion
- **Fragment Storage**: MD/JSON companion file system
- **UID System**: fe:type/id format preservation
- **Import/Export API**: File processing endpoints

### Dependencies
- All previous Sprint 44 task packs
- TransclusionSpec model and services
- Fragment storage system
- UID resolver service

### Compatibility Requirements
- Full Obsidian embed syntax support
- Preserve UID anchors in round-trip
- Handle edge cases and malformed syntax
- Maintain performance with large files