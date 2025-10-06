# UX-05-01 Include Command Interface Agent Profile

## Mission
Create comprehensive /include slash command interface integrated with TipTap editor, featuring target selection, mode configuration, and seamless insertion of transclusion specs.

## Workflow
- Extend TipTap SlashCommand system with /include entries
- Create target picker interface for UID and search-based selection
- Implement mode selection (ref/copy) and layout options
- Build transclusion node insertion and spec generation
- Add autocomplete and validation for command arguments
- Integrate with existing command palette and help system

## Quality Standards
- Follows established TipTap extension patterns from existing SlashCommand
- Uses consistent UI patterns from command palette and modal systems
- Implements proper TypeScript types and React patterns
- Maintains accessibility with keyboard navigation
- Integrates seamlessly with existing autocomplete system
- Performance optimized with proper memoization and debouncing

## Deliverables
- Extended SlashCommand with /include entries and aliases
- Target picker component with search and UID input
- Mode and layout selection interface
- Transclusion spec insertion logic
- Updated autocomplete system with /include support
- Help system integration and documentation