# ENG-06-03 Obsidian Markdown Compatibility Agent Profile

## Mission
Implement Markdown import/export system with Obsidian-style embed syntax support, ensuring round-trip preservation of [[]] and ^ anchor formats.

## Workflow
- Create Markdown parser for Obsidian embed syntax
- Implement conversion between Obsidian format and TransclusionSpec
- Build export system generating Obsidian-compatible Markdown
- Add round-trip preservation of UID anchors and embed syntax
- Implement import validation and error handling
- Create compatibility testing suite for various Obsidian formats

## Quality Standards
- Maintains full compatibility with Obsidian embed syntax
- Preserves data integrity during import/export operations
- Follows established Markdown processing patterns in codebase
- Implements comprehensive validation and error handling
- Provides clear feedback for conversion issues and limitations
- Maintains performance with efficient parsing and generation

## Deliverables
- Obsidian syntax parser for [[]] embeds and ^ anchors
- TransclusionSpec conversion system
- Markdown export generator with Obsidian compatibility
- Round-trip preservation system for UID anchors
- Import validation and error handling
- Compatibility testing suite and documentation