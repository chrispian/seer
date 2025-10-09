# UI-02: Agent Mini-Card Component

**Task Code**: `UI-02`  
**Sprint**: Sprint 64 - Agent Management Dashboard UI  
**Priority**: HIGH  
**Status**: `todo`  
**Estimated**: 2-3 hours  
**Dependencies**: UI-01 (needs grid layout to render in)

## Objective

Create the `AgentMiniCard` component that displays agent information in a compact, visually appealing card format within the dashboard grid. Includes status indicator, badges, and action menu.

## Requirements

### Functional Requirements

1. **Card Display**
   - Agent name (prominent, truncated if too long)
   - Agent slug (smaller, muted text)
   - Type badge (colored, from enum)
   - Mode badge (colored, from enum)
   - Description preview (2 lines max, truncated with ellipsis)
   - Status indicator (colored dot, top-right corner)

2. **Status Indicator**
   - **Temporary Implementation**: Random color on each load
   - 3 colors: Green (#10b981), Yellow (#f59e0b), Red (#ef4444)
   - Position: Top-right corner of card
   - Size: 8px diameter circle
   - Subtle shadow/glow for visibility
   - Note: Will be replaced with real status logic in future sprint

3. **Three-Dot Menu (⋮)**
   - Position: Top-right corner (near status indicator)
   - Opens dropdown menu with options:
     - "Edit" - Opens profile editor
     - "Duplicate" - Creates copy of agent
     - "Delete" - Deletes agent (with confirmation)
   - Dropdown menu from shadcn/ui
   - Only show menu on hover or click

4. **Interactions**
   - Entire card is clickable → opens profile editor
   - Hover state: subtle elevation/shadow
   - Click feedback: scale down slightly
   - Menu doesn't trigger card click
   - Smooth transitions for all interactions

5. **Visual Design**
   - Border with subtle shadow
   - Rounded corners (8px)
   - Padding: 16px
   - Background: white (dark mode: dark gray)
   - Max height for consistency across grid
   - Badges with appropriate colors

### Technical Requirements

1. **Component Props**
   ```typescript
   interface AgentMiniCardProps {
     agent: Agent
     onClick: (agent: Agent) => void
     onEdit: (agent: Agent) => void
     onDelete: (agent: Agent) => void
     onDuplicate: (agent: Agent) => void
   }
   ```

2. **Type Safety**
   - Full TypeScript types for Agent interface
   - Enum types for status, type, mode

3. **Performance**
   - Memoize card component (React.memo)
   - Optimize re-renders
   - Lazy load menu component if needed

## Implementation Details

### Component Structure

```typescript
// resources/js/components/agents/AgentMiniCard.tsx

import { Badge } from '@/components/ui/badge'
import { 
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { MoreVertical, Edit, Copy, Trash2 } from 'lucide-react'

interface Agent {
  id: string
  name: string
  slug: string
  type: string
  mode: string
  status: string
  description?: string
  capabilities?: string[]
  constraints?: string[]
  tools?: string[]
}

interface AgentMiniCardProps {
  agent: Agent
  onClick: (agent: Agent) => void
  onEdit: (agent: Agent) => void
  onDelete: (agent: Agent) => void
  onDuplicate: (agent: Agent) => void
}

export const AgentMiniCard = React.memo(({ 
  agent, 
  onClick, 
  onEdit, 
  onDelete, 
  onDuplicate 
}: AgentMiniCardProps) => {
  // Random status color (temporary)
  const statusColor = useMemo(() => {
    const colors = ['bg-green-500', 'bg-yellow-500', 'bg-red-500']
    return colors[Math.floor(Math.random() * colors.length)]
  }, [agent.id]) // Stable per agent

  const handleMenuAction = (e: React.MouseEvent, action: () => void) => {
    e.stopPropagation() // Prevent card click
    action()
  }

  return (
    <div
      onClick={() => onClick(agent)}
      className="relative border rounded-lg p-4 bg-card hover:shadow-lg 
                 transition-all duration-200 cursor-pointer hover:-translate-y-1
                 active:scale-98"
    >
      {/* Status Indicator */}
      <div className={`absolute top-3 right-3 w-2 h-2 rounded-full ${statusColor}`} />

      {/* Three-Dot Menu */}
      <div className="absolute top-3 right-8">
        <DropdownMenu>
          <DropdownMenuTrigger 
            onClick={(e) => e.stopPropagation()}
            className="hover:bg-accent rounded p-1"
          >
            <MoreVertical className="h-4 w-4 text-muted-foreground" />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={(e) => handleMenuAction(e, () => onEdit(agent))}>
              <Edit className="mr-2 h-4 w-4" />
              Edit
            </DropdownMenuItem>
            <DropdownMenuItem onClick={(e) => handleMenuAction(e, () => onDuplicate(agent))}>
              <Copy className="mr-2 h-4 w-4" />
              Duplicate
            </DropdownMenuItem>
            <DropdownMenuItem 
              onClick={(e) => handleMenuAction(e, () => onDelete(agent))}
              className="text-destructive"
            >
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      {/* Card Content */}
      <div className="space-y-3 mt-2">
        {/* Name & Slug */}
        <div>
          <h3 className="font-semibold text-lg truncate pr-12">{agent.name}</h3>
          <p className="text-xs text-muted-foreground truncate">{agent.slug}</p>
        </div>

        {/* Type & Mode Badges */}
        <div className="flex gap-2 flex-wrap">
          <Badge variant="secondary" className="text-xs">
            {agent.type}
          </Badge>
          <Badge variant="outline" className="text-xs">
            {agent.mode}
          </Badge>
        </div>

        {/* Description Preview */}
        {agent.description && (
          <p className="text-sm text-muted-foreground line-clamp-2">
            {agent.description}
          </p>
        )}

        {/* Capabilities Count (if exists) */}
        {agent.capabilities && agent.capabilities.length > 0 && (
          <div className="flex gap-1 items-center text-xs text-muted-foreground">
            <span>{agent.capabilities.length} capabilities</span>
          </div>
        )}
      </div>
    </div>
  )
})

AgentMiniCard.displayName = 'AgentMiniCard'
```

### Styling Details

```css
/* Tailwind classes breakdown */

/* Card Base */
.card {
  @apply border rounded-lg p-4 bg-card;
  @apply hover:shadow-lg transition-all duration-200;
  @apply cursor-pointer hover:-translate-y-1;
  @apply active:scale-98;
}

/* Status Indicator */
.status-dot {
  @apply absolute top-3 right-3;
  @apply w-2 h-2 rounded-full;
  /* Color applied dynamically: bg-green-500, bg-yellow-500, bg-red-500 */
}

/* Description Truncation */
.description {
  @apply line-clamp-2; /* Requires @tailwindcss/line-clamp plugin */
  /* Or custom CSS: */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
```

### Badge Color Mapping

```typescript
// Type badge colors
const getTypeBadgeColor = (type: string) => {
  const colors: Record<string, string> = {
    'backend-engineer': 'bg-blue-100 text-blue-800',
    'frontend-engineer': 'bg-purple-100 text-purple-800',
    'full-stack-engineer': 'bg-green-100 text-green-800',
    'devops-engineer': 'bg-orange-100 text-orange-800',
    'data-engineer': 'bg-pink-100 text-pink-800',
    'qa-engineer': 'bg-yellow-100 text-yellow-800',
  }
  return colors[type] || 'bg-gray-100 text-gray-800'
}

// Mode badge colors
const getModeBadgeColor = (mode: string) => {
  const colors: Record<string, string> = {
    'implementation': 'bg-green-100 text-green-800',
    'planning': 'bg-blue-100 text-blue-800',
    'review': 'bg-purple-100 text-purple-800',
    'research': 'bg-yellow-100 text-yellow-800',
  }
  return colors[mode] || 'bg-gray-100 text-gray-800'
}
```

## Acceptance Criteria

- [ ] Card displays all required information
- [ ] Status indicator shows random color (changes per agent, stable per render)
- [ ] Three-dot menu appears and works
- [ ] Menu options: Edit, Duplicate, Delete
- [ ] Clicking card triggers onClick handler
- [ ] Menu clicks don't trigger card click
- [ ] Hover effects work smoothly
- [ ] Description truncates at 2 lines with ellipsis
- [ ] Badges display with appropriate colors
- [ ] Card is responsive (works on mobile)
- [ ] No TypeScript errors
- [ ] Component is memoized for performance

## Files to Create/Modify

### New Files
- `resources/js/components/agents/AgentMiniCard.tsx` - Main card component
- `resources/js/components/agents/AgentStatusIndicator.tsx` - Status dot component (optional extract)

### Files to Modify
- `resources/js/pages/AgentDashboard.tsx` - Import and use AgentMiniCard

### Files to Reference
- `resources/js/components/ui/badge.tsx` - Shadcn Badge component
- `resources/js/components/ui/dropdown-menu.tsx` - Shadcn Dropdown
- Other card components in the app for styling consistency

## Testing Checklist

- [ ] Card renders without errors
- [ ] Name and slug display correctly
- [ ] Type and mode badges show
- [ ] Description truncates properly
- [ ] Status dot renders in correct position
- [ ] Status color is random but stable
- [ ] Menu opens on click
- [ ] Edit option triggers onEdit
- [ ] Duplicate option triggers onDuplicate
- [ ] Delete option triggers onDelete
- [ ] Card click triggers onClick
- [ ] Menu click doesn't trigger card click
- [ ] Hover effects work
- [ ] Responsive on mobile
- [ ] No console errors

## Notes

- **Status Color**: Currently random. In future sprint, will be based on real agent status logic (active/idle/busy/offline)
- **Performance**: Use React.memo to prevent unnecessary re-renders in grid
- **Accessibility**: Ensure keyboard navigation works for menu
- **Design**: Follow existing card patterns in app (check other dashboards)
- **Menu Position**: Ensure dropdown doesn't get cut off at grid edges

## Dependencies

**Before Starting**:
- ✅ UI-01 completed (dashboard grid exists)
- ✅ Agent TypeScript interface defined
- ✅ Shadcn/ui Badge component available
- ✅ Shadcn/ui Dropdown Menu component available

**Blocked By**: UI-01

**Blocks**: UI-03 (editor needs card click to trigger it)
