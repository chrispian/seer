# Task: Create Shadcn-Parity Components (Config-Driven)

**Task Code**: T-UIB-SPRINT2-04-COMPONENTS  
**Sprint**: UI Builder v2 Sprint 2  
**Priority**: MEDIUM  
**Assigned To**: FE-Core Agent (with sub-delegation)  
**Status**: TODO  
**Created**: 2025-10-15  
**Depends On**: T-UIB-SPRINT2-03-SCHEMA

## Objective

Create config-driven React components matching Shadcn UI functionality, organized by complexity tiers. Build primitives first, then compose complex components from those primitives using config alone.

## Component List

See: `/Users/chrispian/Projects/seer/delegation/tasks/ui-builder/component_list.md`

Total: 60 components from Shadcn UI

## Implementation Strategy

### Phase 1: Primitives (Building Blocks)

These are foundational components that others depend on. Build these first with **full agent focus**.

**Tier 1A - Core Primitives** (Priority: CRITICAL)
- [ ] Button
- [ ] Input
- [ ] Label
- [ ] Badge
- [ ] Avatar
- [ ] Skeleton
- [ ] Spinner
- [ ] Separator
- [ ] Kbd (keyboard shortcut display)
- [ ] Typography

**Tier 1B - Form Elements**
- [ ] Checkbox
- [ ] Radio Group
- [ ] Switch
- [ ] Slider
- [ ] Textarea
- [ ] Select
- [ ] Field (wrapper with label+error)

**Tier 1C - Feedback**
- [ ] Alert
- [ ] Progress
- [ ] Toast
- [ ] Empty (empty state)

### Phase 2: Layout & Containers

These organize and structure other components.

**Tier 2A - Structural**
- [ ] Card
- [ ] Separator
- [ ] Scroll Area
- [ ] Resizable
- [ ] Aspect Ratio
- [ ] Collapsible

**Tier 2B - Navigation**
- [ ] Tabs
- [ ] Breadcrumb
- [ ] Pagination
- [ ] Sidebar

### Phase 3: Composite Components

Built from primitives; mostly config-based assembly.

**Tier 3A - Interactive Patterns**
- [ ] Accordion
- [ ] Dialog
- [ ] Sheet
- [ ] Drawer
- [ ] Popover
- [ ] Tooltip
- [ ] Hover Card
- [ ] Dropdown Menu
- [ ] Context Menu
- [ ] Menubar
- [ ] Navigation Menu
- [ ] Command (command palette)
- [ ] Combobox

**Tier 3B - Complex Forms**
- [ ] Form (with validation)
- [ ] Input Group (prefix/suffix)
- [ ] Input OTP
- [ ] Date Picker
- [ ] Calendar
- [ ] Button Group
- [ ] Toggle
- [ ] Toggle Group

**Tier 3C - Advanced**
- [ ] Data Table (enhanced)
- [ ] Chart
- [ ] Carousel
- [ ] Item (generic list item)
- [ ] Alert Dialog
- [ ] Sonner (toast alternative)

## Technical Requirements

### 1. Config-Driven Architecture

Each component must accept a JSON config and render accordingly:

```typescript
// Example: Button component
interface ButtonConfig {
  id: string;
  type: 'button.icon' | 'button.text' | 'button.group';
  props: {
    label?: string;
    icon?: string;
    variant?: 'default' | 'destructive' | 'outline' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
    disabled?: boolean;
  };
  actions?: {
    click?: ActionConfig;
    hover?: ActionConfig;
  };
}
```

### 2. Component Registry Integration

Register each component in `ComponentRegistry.ts`:

```typescript
registerComponents() {
  // Primitives
  registry.register('button', ButtonComponent);
  registry.register('button.icon', ButtonIconComponent);
  registry.register('button.text', ButtonTextComponent);
  registry.register('input', InputComponent);
  registry.register('input.text', InputTextComponent);
  // ... etc
}
```

### 3. Database Entries

For each component, create entry in `fe_ui_components`:

```php
FeUiComponent::create([
    'key' => 'component.button.primary',
    'type' => 'button',
    'kind' => 'primitive', // primitive | composite | pattern | layout
    'variant' => 'primary',
    'schema_json' => [
        'props' => ['label', 'icon', 'disabled', 'loading'],
        'actions' => ['click', 'hover'],
        'slots' => [],
    ],
    'defaults_json' => [
        'variant' => 'default',
        'size' => 'md',
        'disabled' => false,
    ],
    'capabilities_json' => ['clickable', 'focusable', 'keyboard_accessible'],
    'version' => '1.0.0',
    'hash' => hash('sha256', 'component.button.primary.1.0.0'),
    'enabled' => true,
]);
```

### 4. Documentation Template

For each component, create docs in `delegation/tasks/ui-builder/components/`:

```markdown
# Button Component

**Type**: `button`  
**Kind**: `primitive`  
**File**: `resources/js/components/v2/primitives/ButtonComponent.tsx`

## Config Schema

\`\`\`json
{
  "id": "component.button.save",
  "type": "button",
  "props": {
    "label": "Save",
    "icon": "check",
    "variant": "default",
    "size": "md"
  },
  "actions": {
    "click": {
      "type": "command",
      "command": "agent:save"
    }
  }
}
\`\`\`

## Variants

- `default` - Primary button
- `destructive` - Danger actions
- `outline` - Secondary actions
- `ghost` - Tertiary actions

## Database Entry

\`\`\`bash
php artisan db:seed --class=ButtonComponentSeeder
\`\`\`
```

## File Structure

```
resources/js/components/v2/
├── primitives/
│   ├── ButtonComponent.tsx
│   ├── InputComponent.tsx
│   ├── BadgeComponent.tsx
│   ├── AvatarComponent.tsx
│   └── ... (all Tier 1 components)
├── layouts/
│   ├── CardComponent.tsx
│   ├── TabsComponent.tsx
│   └── ... (all Tier 2 components)
├── composites/
│   ├── FormComponent.tsx
│   ├── DataTableComponent.tsx
│   └── ... (all Tier 3 components)
└── patterns/
    ├── ResourceListPattern.tsx
    ├── DetailViewPattern.tsx
    └── ... (reusable patterns)
```

## Delegation Strategy

### Parallel Work After Phase 1

Once **Tier 1A primitives** are complete (Button, Input, Label, Badge, Avatar), multiple FE agents can work in parallel:

- **Agent 1**: Tier 1B (Form Elements)
- **Agent 2**: Tier 1C (Feedback)
- **Agent 3**: Tier 2A (Structural)
- **Agent 4**: Tier 2B (Navigation)

Then for Phase 3, with all primitives available:

- **Agent 1**: Tier 3A (Interactive Patterns)
- **Agent 2**: Tier 3B (Complex Forms)
- **Agent 3**: Tier 3C (Advanced)

## Acceptance Criteria

### Per Component
- [ ] TypeScript component file created
- [ ] Config schema defined in types.ts
- [ ] Registered in ComponentRegistry
- [ ] Database entry created (model + seeder)
- [ ] Documentation page created
- [ ] Variants implemented (if applicable)
- [ ] Actions supported (click, hover, etc.)
- [ ] Accessibility attributes (ARIA)
- [ ] Responsive design (mobile-friendly)
- [ ] Works in modal and page contexts

### Overall
- [ ] All 60 components implemented
- [ ] Component registry fully populated
- [ ] Database seeder creates all entries
- [ ] Documentation index created
- [ ] Example page showcasing each component
- [ ] Build succeeds with no TypeScript errors
- [ ] Components reusable via config alone
- [ ] Performance tested (lazy loading works)

## Dependencies

- T-UIB-SPRINT2-03-SCHEMA (for `kind`, `variant`, schema fields)
- Existing Shadcn UI components (can reference but don't copy)
- ComponentRegistry system (exists from v2 MVP)

## Estimated Time

- **Phase 1** (Primitives): 12-16 hours (can parallelize Tier 1B-C)
- **Phase 2** (Layouts): 8-10 hours (parallelize all tiers)
- **Phase 3** (Composites): 16-20 hours (parallelize all tiers)

**Total**: 36-46 hours (with 3-4 agents working in parallel)

**With delegation**: 12-15 hours real-time

## Notes

- Reuse existing Shadcn components as inspiration, but make them config-driven
- Focus on common use cases first; edge cases can be added later
- Ensure consistent naming: `ComponentName` + `Component` suffix
- Use TypeScript generics for better type safety
- Leverage SlotBinder for component communication
- Make components controlled where possible (stateless)
- Document props clearly with JSDoc comments

## Related Tasks

- T-UIB-SPRINT2-03-SCHEMA (database schema)
- T-UIB-SPRINT2-05-DATASOURCES (will use form components)
- Create sub-tasks for each tier/phase delegation

## Deliverables

1. **Components** - 60 TypeScript files in organized folders
2. **Registry entries** - All components registered programmatically
3. **Database seeder** - Populates `fe_ui_components` table
4. **Documentation** - 60 markdown files + index
5. **Demo page** - Showcase all components (`/v2/pages/component.showcase`)
6. **Test coverage** - Basic smoke tests for each component
