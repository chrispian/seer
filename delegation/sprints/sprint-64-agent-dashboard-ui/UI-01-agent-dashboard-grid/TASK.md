# UI-01: Agent Dashboard Grid Layout

**Task Code**: `UI-01`  
**Sprint**: Sprint 64 - Agent Management Dashboard UI  
**Priority**: HIGH  
**Status**: `todo`  
**Estimated**: 3-4 hours  
**Dependencies**: None

## Objective

Create the main Agent Dashboard page with a responsive grid layout for displaying agent cards. This serves as the foundation for the entire agent management UI.

## Requirements

### Functional Requirements

1. **Dashboard Page Component**
   - Create `resources/js/pages/AgentDashboard.tsx`
   - Full-page layout with proper spacing and padding
   - Header section with title and action buttons
   - Main content area with grid layout

2. **Grid Layout**
   - Responsive grid:
     - Desktop (1024px+): 4 columns
     - Tablet (768px-1023px): 2 columns
     - Mobile (<768px): 1 column
   - Consistent gap spacing between cards
   - Auto-fit grid with minimum card width

3. **Header Section**
   - Page title: "Agent Management"
   - Subtitle/description: "Manage AI agents and their capabilities"
   - Primary action: "Create New Agent" button (top-right)
   - Agent count badge or stats

4. **Empty State**
   - Display when no agents exist
   - Icon: Bot icon from lucide-react
   - Message: "No agents found. Create your first agent to get started."
   - Prominent "Create Agent" button

5. **Loading State**
   - Skeleton cards while loading
   - Match grid layout (3-4 skeleton cards)
   - Smooth transitions when data loads

6. **Command Integration**
   - Update `AgentListCommand.php` to open dashboard instead of modal
   - OR create new `/agent-dashboard` command
   - Support both routes for backward compatibility

### Technical Requirements

1. **Data Fetching**
   - Fetch agents on mount using API
   - Handle loading and error states
   - Support refresh functionality

2. **Routing**
   - Accessible via route: `/agents` or `/agent-dashboard`
   - Can be opened from slash command
   - Supports deep linking to specific agent (future)

3. **State Management**
   - Track selected agent for editor
   - Track create/edit modal state
   - Track loading/error states

## Implementation Details

### Component Structure

```typescript
// resources/js/pages/AgentDashboard.tsx

interface AgentDashboardProps {
  // Optional: initial data if opened from command
  initialAgents?: Agent[]
}

export function AgentDashboard({ initialAgents }: AgentDashboardProps) {
  const [agents, setAgents] = useState<Agent[]>(initialAgents || [])
  const [isLoading, setIsLoading] = useState(!initialAgents)
  const [selectedAgent, setSelectedAgent] = useState<Agent | null>(null)
  const [isCreating, setIsCreating] = useState(false)

  // Fetch agents
  useEffect(() => {
    if (!initialAgents) {
      fetchAgents()
    }
  }, [])

  // Render logic
  return (
    <div className="container mx-auto p-6">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1>Agent Management</h1>
          <p>Manage AI agents and their capabilities</p>
        </div>
        <Button onClick={() => setIsCreating(true)}>
          Create New Agent
        </Button>
      </div>

      {/* Grid */}
      {isLoading ? (
        <SkeletonGrid />
      ) : agents.length === 0 ? (
        <EmptyState onCreateClick={() => setIsCreating(true)} />
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {agents.map(agent => (
            <AgentMiniCard
              key={agent.id}
              agent={agent}
              onClick={() => setSelectedAgent(agent)}
              onEdit={() => setSelectedAgent(agent)}
              onDelete={() => handleDelete(agent)}
              onDuplicate={() => handleDuplicate(agent)}
            />
          ))}
        </div>
      )}

      {/* Modals */}
      {selectedAgent && (
        <AgentProfileEditor
          agent={selectedAgent}
          onClose={() => setSelectedAgent(null)}
          onSave={handleSave}
        />
      )}

      {isCreating && (
        <AgentProfileEditor
          onClose={() => setIsCreating(false)}
          onSave={handleCreate}
        />
      )}
    </div>
  )
}
```

### Grid Styling (Tailwind)

```typescript
// Responsive grid classes
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 auto-rows-fr">
  {/* Cards */}
</div>

// Alternative with custom breakpoints
<div className="grid gap-4" style={{
  gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))'
}}>
```

### Empty State Component

```typescript
function EmptyState({ onCreateClick }: { onCreateClick: () => void }) {
  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      <Bot className="h-16 w-16 text-muted-foreground mb-4" />
      <h3 className="text-lg font-semibold mb-2">No agents found</h3>
      <p className="text-muted-foreground mb-6">
        Create your first agent to get started.
      </p>
      <Button onClick={onCreateClick} size="lg">
        <Plus className="mr-2 h-4 w-4" />
        Create Agent
      </Button>
    </div>
  )
}
```

### Skeleton Loader

```typescript
function SkeletonGrid() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      {[1, 2, 3, 4].map(i => (
        <div key={i} className="border rounded-lg p-4 space-y-3 animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-3/4" />
          <div className="h-3 bg-gray-200 rounded w-1/2" />
          <div className="h-20 bg-gray-200 rounded" />
          <div className="flex gap-2">
            <div className="h-6 bg-gray-200 rounded w-16" />
            <div className="h-6 bg-gray-200 rounded w-16" />
          </div>
        </div>
      ))}
    </div>
  )
}
```

## Command Integration

### Option 1: Update Existing Command

```php
// app/Commands/AgentListCommand.php

public function handle(): array
{
    $agents = $this->getAgents();
    
    return [
        'type' => 'page',
        'component' => 'AgentDashboard',
        'data' => [
            'agents' => $agents
        ]
    ];
}
```

### Option 2: Create New Command

Keep `/agents` as modal, create `/agent-dashboard` for new UI.

## Acceptance Criteria

- [ ] Dashboard page renders with proper layout
- [ ] Grid is responsive (4/2/1 columns at breakpoints)
- [ ] Header displays title and "Create" button
- [ ] Empty state shows when no agents
- [ ] Skeleton loaders display while loading
- [ ] Can be accessed via `/agents` command
- [ ] Create button triggers modal/editor (placeholder for now)
- [ ] Component follows existing app patterns
- [ ] No TypeScript errors
- [ ] Proper spacing and visual polish

## Files to Create/Modify

### New Files
- `resources/js/pages/AgentDashboard.tsx` - Main dashboard page

### Files to Modify
- `app/Commands/AgentListCommand.php` - Route to dashboard instead of modal
- `resources/js/islands/chat/CommandResultModal.tsx` - Add AgentDashboard routing (if needed)

### Files to Reference
- `resources/js/components/orchestration/AgentListModal.tsx` - Existing modal for comparison
- `app/Services/AgentProfileService.php` - Backend service
- Other dashboard pages for layout patterns

## Testing Checklist

- [ ] Dashboard renders without errors
- [ ] Grid adjusts on window resize
- [ ] Empty state displays correctly
- [ ] Loading state displays correctly
- [ ] Create button is clickable (even if no-op for now)
- [ ] `/agents` command opens dashboard
- [ ] No console errors or warnings
- [ ] Responsive across devices (mobile, tablet, desktop)

## Notes

- Keep it simple - this is foundation only
- Focus on layout and structure, not interactions yet
- Interactions (click handlers) will be wired up in UI-03 and UI-04
- Use existing app design patterns and components
- Ensure compatibility with shadcn/ui components

## Dependencies

**Before Starting**:
- ✅ Shadcn/ui installed
- ✅ Tailwind configured
- ✅ React Router (or equivalent) available
- ✅ AgentProfileService exists in backend

**Blocked By**: None

**Blocks**: UI-02 (needs grid to render cards in)
