# Frontend Componentization & Theming Plan

## Current Architecture Overview

### Route Structure
- **Main Route**: `/` → `AppShellController@index` → `resources/views/app/chat.blade.php`
- **Layout**: 4-column responsive layout (Ribbon, Sidebar, Main Chat, Right Rail)
- **Framework**: React 18 + TypeScript + shadcn/ui + Tailwind CSS v4
- **State Management**: Zustand (context store, layout store)

### Current Component Structure

```
AppShell (resources/js/components/AppShell.tsx)
├── Ribbon (resources/js/islands/shell/Ribbon.tsx) - Far left, hidden on mobile/tablet
├── AppSidebar (resources/js/components/AppSidebar.tsx) - Project/session management
├── Main Content Area
│   ├── ChatHeader (resources/js/islands/shell/ChatHeader.tsx) - Agent header/controls
│   └── ChatIsland (resources/js/islands/chat/ChatIsland.tsx)
│       ├── ChatTranscript (resources/js/islands/chat/ChatTranscript.tsx)
│       └── ChatComposer (resources/js/islands/chat/ChatComposer.tsx)
└── RightRail (resources/js/islands/shell/RightRail.tsx) - Widget system
```

## Issues Identified

### 1. Component Coupling
- **AppSidebar**: Monolithic component (569 lines) mixing concerns:
  - Vault/Project selection UI
  - Pinned chat sessions list
  - Recent chat sessions list
  - User menu
  - Session management logic
  
### 2. Theming Gaps
- **Current State**:
  - CSS has dark mode variables defined (lines 711-743 in app.css)
  - Hard-coded color values throughout components (e.g., `bg-white`, `text-black`)
  - Prose styles are hard-coded for synthwave theme
  - No theme switching mechanism
  
- **Missing**:
  - Theme provider/context
  - Dynamic theme switching
  - Light/dark mode toggle
  - User-customizable themes

### 3. Style Organization
- **Prose Section**: Lines 362-636 in `resources/css/app.css`
  - Hard-coded colors for markdown rendering
  - Mixed synthwave aesthetic with business UI
  - No separation between theme variants

### 4. Documentation Gaps
- No component documentation
- No architecture decision records
- No agent bootstrapping guides

## Migration Plan

### Phase 1: Component Extraction & Separation of Concerns

#### 1.1 Break Down AppSidebar
**Priority**: High | **Effort**: 2-3 hours

Extract into discrete components:

```
AppSidebar (orchestrator)
├── SidebarHeader (collapse toggle)
├── VaultSelector
│   └── VaultCreateDialog (already exists)
├── PinnedChatsList
│   └── ChatSessionItem (reusable)
├── RecentChatsList  
│   └── ChatSessionItem (reusable)
├── ProjectsList
│   └── ProjectCreateDialog (already exists)
└── UserMenu
```

**Files to Create**:
- `resources/js/components/sidebar/SidebarHeader.tsx`
- `resources/js/components/sidebar/VaultSelector.tsx`
- `resources/js/components/sidebar/PinnedChatsList.tsx`
- `resources/js/components/sidebar/RecentChatsList.tsx`
- `resources/js/components/sidebar/ChatSessionItem.tsx`
- `resources/js/components/sidebar/ProjectsList.tsx`
- `resources/js/components/sidebar/UserMenu.tsx`

**Benefits**:
- Each component focused on single responsibility
- Easier testing and maintenance
- Reusable ChatSessionItem across pinned/recent
- Cleaner prop interfaces

#### 1.2 Break Down ChatHeader
**Priority**: High | **Effort**: 1-2 hours

Current location: `resources/js/islands/shell/ChatHeader.tsx`

Extract into:
```
ChatHeader (orchestrator)
├── AgentSelector
├── ModelSelector  
├── SessionControls (new chat, settings, etc.)
└── ContextBreadcrumb (vault > project > session)
```

**Files to Create**:
- `resources/js/components/chat/AgentSelector.tsx`
- `resources/js/components/chat/ModelSelector.tsx`
- `resources/js/components/chat/SessionControls.tsx`
- `resources/js/components/chat/ContextBreadcrumb.tsx`

#### 1.3 Refine ChatIsland Structure
**Priority**: Medium | **Effort**: 1 hour

Already well-structured, but ensure clear separation:

```
ChatIsland
├── ChatTranscript (message display)
│   └── MessageItem (individual message)
│       ├── MessageActions (copy, bookmark, etc.)
│       └── MarkdownRenderer (prose styling)
└── ChatComposer (input area)
    ├── TipTap editor extensions
    └── CommandPalette
```

**Files to Create**:
- `resources/js/components/chat/MessageItem.tsx` (extract from ChatTranscript)
- `resources/js/components/chat/MarkdownRenderer.tsx` (extract prose rendering)

#### 1.4 Organize RightRail Widgets
**Priority**: Low | **Effort**: 1 hour

Already well-structured with widget system. Ensure each widget is self-contained.

Current structure is good:
```
RightRail
├── CustomizationPanel
└── Widgets (each in resources/js/widgets/)
    ├── inbox/
    ├── scheduler/
    ├── todos/
    └── etc.
```

### Phase 2: Theme System Implementation

#### 2.1 Create Theme Infrastructure
**Priority**: High | **Effort**: 3-4 hours

**Files to Create**:
```
resources/js/contexts/ThemeContext.tsx
resources/js/hooks/useTheme.ts
resources/js/lib/themes/
├── index.ts
├── base.ts
├── light.ts
├── dark.ts
└── synthwave.ts (current style)
```

**Theme Structure**:
```typescript
// resources/js/lib/themes/base.ts
export interface Theme {
  name: string
  colors: {
    // Base colors
    background: string
    foreground: string
    
    // UI elements
    card: string
    cardForeground: string
    border: string
    input: string
    
    // Semantic colors
    primary: string
    primaryForeground: string
    secondary: string
    secondaryForeground: string
    
    // Prose/markdown
    prose: {
      base: string
      headings: {
        h1: string
        h2: string
        h3: string
        h4: string
      }
      code: string
      codeBackground: string
      link: string
      blockquote: string
      emphasis: string
      strong: string
    }
  }
  
  // Spacing, radius, etc.
  spacing: {
    tight: string
    normal: string
    comfortable: string
  }
  
  radius: {
    sm: string
    md: string
    lg: string
  }
}
```

#### 2.2 Update CSS Variable System
**Priority**: High | **Effort**: 2 hours

**Action Items**:
1. Convert hard-coded colors to CSS variables
2. Create theme-specific variable sets
3. Update `resources/css/app.css` to use variables

**Example**:
```css
/* Current (hard-coded) */
.prose h1 {
  color: rgb(255 20 147); /* hot-pink */
}

/* After (variable-based) */
.prose h1 {
  color: var(--prose-heading-h1);
}

/* Theme definitions */
:root[data-theme="light"] {
  --prose-heading-h1: #1a1a1a;
}

:root[data-theme="dark"] {
  --prose-heading-h1: #e5e5e5;
}

:root[data-theme="synthwave"] {
  --prose-heading-h1: rgb(255 20 147);
}
```

#### 2.3 Create Theme Switcher Component
**Priority**: Medium | **Effort**: 1 hour

**File**: `resources/js/components/ThemeSwitcher.tsx`

Add to:
- User menu in AppSidebar
- Settings page

**Features**:
- Preview themes before applying
- Save preference to backend
- Persist in localStorage for instant load

#### 2.4 Extract Prose Styling
**Priority**: High | **Effort**: 2 hours

**Action Items**:
1. Create separate CSS files for each theme's prose styles
2. Import dynamically based on active theme
3. Ensure MarkdownRenderer component respects theme

**Files to Create**:
```
resources/css/themes/
├── prose-light.css
├── prose-dark.css
└── prose-synthwave.css
```

### Phase 3: Documentation

#### 3.1 Component Documentation
**Priority**: High | **Effort**: 4-5 hours

**Create documentation for each component**:

**Template**:
```markdown
# ComponentName

## Purpose
Brief description of what this component does.

## Props Interface
\`\`\`typescript
interface ComponentNameProps {
  propName: type // description
}
\`\`\`

## Usage Example
\`\`\`tsx
<ComponentName prop={value} />
\`\`\`

## State Management
- What state does it manage?
- What contexts does it consume?
- What stores does it use?

## Side Effects
- API calls
- Local storage
- Event listeners

## Styling
- Theme variables used
- Custom classes
- Responsive behavior

## Testing Notes
- Key user interactions to test
- Edge cases
- Mock data requirements

## Related Components
- Parent components
- Child components
- Sibling components
```

**Documentation Files to Create**:
```
docs/frontend/
├── README.md (overview + getting started)
├── architecture/
│   ├── component-structure.md
│   ├── state-management.md
│   └── routing.md
├── components/
│   ├── shell/
│   │   ├── AppShell.md
│   │   ├── Ribbon.md
│   │   ├── ChatHeader.md
│   │   └── RightRail.md
│   ├── sidebar/
│   │   ├── AppSidebar.md
│   │   ├── VaultSelector.md
│   │   ├── PinnedChatsList.md
│   │   ├── RecentChatsList.md
│   │   └── UserMenu.md
│   ├── chat/
│   │   ├── ChatIsland.md
│   │   ├── ChatTranscript.md
│   │   ├── ChatComposer.md
│   │   ├── MessageItem.md
│   │   └── MarkdownRenderer.md
│   └── widgets/
│       └── [widget-name].md
├── themes/
│   ├── overview.md
│   ├── creating-themes.md
│   └── prose-styling.md
└── guides/
    ├── adding-new-component.md
    ├── adding-new-widget.md
    └── agent-bootstrap.md
```

#### 3.2 Agent Bootstrap Guide
**Priority**: High | **Effort**: 2 hours

**File**: `docs/frontend/guides/agent-bootstrap.md`

**Content**:
```markdown
# Agent Bootstrap Guide for Frontend Tasks

## Quick Start Context

### Tech Stack
- **Framework**: React 18 + TypeScript
- **UI Library**: shadcn/ui (Radix UI primitives + Tailwind)
- **Styling**: Tailwind CSS v4
- **State**: Zustand
- **Data Fetching**: TanStack Query (React Query)
- **Build**: Vite

### Key Files to Read First
1. `/resources/js/components/AppShell.tsx` - Application entry point
2. `/resources/js/contexts/ChatSessionContext.tsx` - Chat state
3. `/resources/js/stores/useAppStore.ts` - Global app state
4. `/resources/js/stores/useLayoutStore.ts` - Layout/UI preferences

### Component Locations
- **Shell**: `/resources/js/islands/shell/`
- **Chat**: `/resources/js/islands/chat/`
- **Sidebar**: `/resources/js/components/sidebar/`
- **Widgets**: `/resources/js/widgets/`
- **UI Primitives**: `/resources/js/components/ui/`

### Styling Conventions
- Use Tailwind utility classes
- Theme variables: `var(--variable-name)`
- Responsive: `mobile-first` approach
- Dark mode: `.dark` class prefix

### Common Patterns

#### Creating a New Component
\`\`\`tsx
import React from 'react'
import { useTheme } from '@/hooks/useTheme'

interface MyComponentProps {
  title: string
}

export function MyComponent({ title }: MyComponentProps) {
  const { theme } = useTheme()
  
  return (
    <div className="bg-background text-foreground">
      <h2 className="text-prose-heading-h2">{title}</h2>
    </div>
  )
}
\`\`\`

#### Fetching Data
\`\`\`tsx
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'

function MyComponent() {
  const { data, isLoading } = useQuery({
    queryKey: ['myData'],
    queryFn: () => api.fetchMyData(),
  })
  
  if (isLoading) return <LoadingSpinner />
  
  return <div>{data}</div>
}
\`\`\`

### Testing
Run: `npm test`

### Common Tasks
- **Add new widget**: See `/docs/frontend/guides/adding-new-widget.md`
- **Modify theme**: See `/docs/frontend/themes/creating-themes.md`
- **Add new route**: Update `/resources/js/boot.tsx`

### Troubleshooting
- **Build errors**: Check TypeScript types in `/resources/js/types/`
- **Style not applying**: Verify Tailwind config and theme variables
- **State not updating**: Check Zustand store subscriptions
```

### Phase 4: Implementation Steps

#### Step-by-Step Execution Order

1. **Week 1: Component Extraction**
   - Day 1-2: Break down AppSidebar
   - Day 3: Break down ChatHeader
   - Day 4: Refine ChatIsland
   - Day 5: Testing and fixes

2. **Week 2: Theme System**
   - Day 1-2: Create theme infrastructure
   - Day 3: Update CSS variables
   - Day 4: Create theme switcher
   - Day 5: Extract prose styling

3. **Week 3: Documentation**
   - Day 1-3: Component documentation
   - Day 4: Architecture docs
   - Day 5: Agent bootstrap guide

4. **Week 4: Polish & Testing**
   - Day 1-2: End-to-end testing
   - Day 3: Performance optimization
   - Day 4: Accessibility audit
   - Day 5: Final review

## Recommendations & Best Practices

### 1. Component Design
- **Single Responsibility**: Each component should do one thing well
- **Composition over Inheritance**: Build complex UIs from simple components
- **Props Interface**: Explicit TypeScript interfaces for all props
- **Error Boundaries**: Wrap major sections in error boundaries

### 2. State Management
- **Local State**: Use `useState` for component-specific state
- **Shared State**: Use Zustand stores for cross-component state
- **Server State**: Use React Query for API data
- **Form State**: Consider React Hook Form for complex forms

### 3. Styling Strategy
- **Utility-First**: Prefer Tailwind utilities
- **Custom Components**: Use shadcn/ui patterns
- **Theme Variables**: Always use CSS variables for colors
- **Responsive**: Mobile-first, test on real devices

### 4. Performance
- **Code Splitting**: Lazy load routes and heavy components
- **Memoization**: Use React.memo for expensive renders
- **Query Optimization**: Set appropriate staleTime in React Query
- **Bundle Size**: Monitor with `npm run build`

### 5. Accessibility
- **Semantic HTML**: Use proper heading hierarchy
- **ARIA Labels**: Add labels to interactive elements
- **Keyboard Navigation**: Test without mouse
- **Screen Reader**: Test with VoiceOver/NVDA

### 6. Testing
- **Unit Tests**: Test individual component logic
- **Integration Tests**: Test component interactions
- **E2E Tests**: Test critical user flows
- **Visual Regression**: Screenshot testing for UI changes

## Migration Checklist

- [ ] Phase 1.1: Extract AppSidebar components
- [ ] Phase 1.2: Extract ChatHeader components
- [ ] Phase 1.3: Refine ChatIsland structure
- [ ] Phase 1.4: Organize RightRail widgets
- [ ] Phase 2.1: Create theme infrastructure
- [ ] Phase 2.2: Update CSS variable system
- [ ] Phase 2.3: Create theme switcher
- [ ] Phase 2.4: Extract prose styling
- [ ] Phase 3.1: Write component documentation
- [ ] Phase 3.2: Write agent bootstrap guide
- [ ] Phase 4: End-to-end testing and polish

## Success Metrics

### Code Quality
- [ ] All components < 200 lines
- [ ] 100% TypeScript coverage
- [ ] No console warnings
- [ ] Lighthouse score > 90

### User Experience
- [ ] Theme switching < 100ms
- [ ] First paint < 1s
- [ ] Fully interactive < 2s
- [ ] Smooth 60fps animations

### Developer Experience
- [ ] Component docs 100% complete
- [ ] Agent can bootstrap in < 5 minutes
- [ ] New feature implementation < 1 day
- [ ] Zero onboarding questions

## Future Enhancements

1. **Custom Theme Builder UI**
   - Visual color picker
   - Live preview
   - Export/import themes
   - Share community themes

2. **Component Playground**
   - Storybook integration
   - Interactive prop controls
   - Visual testing
   - Code snippets

3. **Advanced Widgets**
   - Widget marketplace
   - Third-party widget support
   - Widget templates
   - Custom widget SDK

4. **Performance Monitoring**
   - Real-user metrics
   - Bundle analysis dashboard
   - Render profiling
   - Query performance tracking
