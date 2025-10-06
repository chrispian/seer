# UX-05-02 Transclusion Renderer System Context

## Technical Architecture

### TipTap Node Structure
```typescript
// TransclusionNode attributes
interface TransclusionNodeAttrs {
  kind: 'single' | 'list'
  mode: 'ref' | 'copy' | 'live' | 'snapshot'
  uid?: string
  query?: string
  context?: { ws?: string; proj?: string }
  layout?: 'block' | 'inline' | 'checklist' | 'table' | 'cards'
  columns?: string[]
  options?: Record<string, any>
  createdAt?: number
  updatedAt?: number
}
```

### Component Architecture
```
resources/js/islands/chat/tiptap/
├── nodes/
│   └── TransclusionNode.tsx (new)
├── components/transclusion/
│   ├── TransclusionRenderer.tsx (new)
│   ├── TransclusionChecklist.tsx (new)
│   ├── TransclusionTable.tsx (new)
│   ├── TransclusionCards.tsx (new)
│   ├── TransclusionError.tsx (new)
│   └── TransclusionLoading.tsx (new)
└── hooks/
    ├── useTransclusionData.ts (new)
    └── useTransclusionState.ts (new)
```

### Integration Points
- **Fragment API**: Real-time data fetching and updates
- **State Management**: React Query for caching and synchronization
- **Todo System**: Checkbox state synchronization with backend
- **Markdown Rendering**: ReactMarkdown for content display
- **UI Components**: Shadcn components for consistent styling

### Dependencies
- ENG-06-01 TransclusionSpec backend
- UX-05-01 Include command interface
- ENG-06-02 Fragment query engine
- Existing Fragment and Todo systems
- TipTap node development patterns

### Performance Considerations
- Virtual scrolling for large lists
- Memoization for expensive renders
- Debounced state updates
- Optimistic UI updates