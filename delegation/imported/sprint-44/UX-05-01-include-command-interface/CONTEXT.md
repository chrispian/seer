# UX-05-01 Include Command Interface Context

## Technical Architecture

### TipTap Integration Points
- **SlashCommand Extension**: resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx
- **Autocomplete System**: resources/js/islands/chat/tiptap/utils/autocomplete.ts
- **Command Palette**: resources/views/filament/resources/fragment-resource/pages/chat-interface.blade.php

### Component Structure
```
resources/js/islands/chat/tiptap/
├── extensions/
│   ├── SlashCommand.tsx (extend)
│   └── IncludeCommand.tsx (new)
├── components/
│   ├── TargetPicker.tsx (new)
│   ├── ModeSelector.tsx (new)
│   └── LayoutSelector.tsx (new)
└── utils/
    ├── autocomplete.ts (extend)
    └── include-helpers.ts (new)
```

### Dependencies
- ENG-06-01 TransclusionSpec backend foundation
- Existing TipTap SlashCommand system
- Fragment search and autocomplete APIs
- Command palette and modal patterns

### UI/UX Patterns to Follow
- **Modal Pattern**: CommandResultModal styling and behavior
- **Autocomplete**: Existing /todo command autocomplete
- **Search Interface**: Fragment search patterns
- **Command Palette**: Existing command selection UI

### API Integration
- `/api/fragment/search` - Fragment search endpoint
- `/api/commands/execute` - Command execution
- Fragment UID resolution and validation
- Context stack (workspace/project) resolution