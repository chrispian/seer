# UI-03: Agent Profile Editor Modal

**Task Code**: `UI-03`  
**Sprint**: Sprint 64 - Agent Management Dashboard UI  
**Priority**: HIGH  
**Status**: `todo`  
**Estimated**: 4-5 hours  
**Dependencies**: UI-02 (triggered by card click)

## Objective

Create a comprehensive Agent Profile Editor modal for creating and editing agent profiles. Full-featured form with all agent fields, validation, and menu actions.

## Requirements

### Functional Requirements

1. **Modal Component**
   - Large modal (900px width, full height on mobile)
   - Smooth open/close animations
   - Backdrop click to close (with unsaved changes warning)
   - Escape key to close
   - Scrollable content area

2. **Form Fields** (All Required Fields from Schema)
   
   **Basic Info**:
   - **Name** (text input, required)
   - **Slug** (text input, auto-generated from name, editable, validated)
   - **Description** (textarea, optional, 3-5 rows)
   
   **Classification**:
   - **Type** (dropdown/select, required)
     - Load options from backend: `/api/agents/meta/types`
     - Display: label with description tooltip
     - Shows default mode for selected type
   - **Mode** (dropdown/select, required)
     - Load options from backend: `/api/agents/meta/modes`
     - Auto-populate based on type selection
     - Can be manually overridden
   - **Status** (dropdown/select, required)
     - Load options from backend: `/api/agents/meta/statuses`
     - Options: active, inactive, archived
   
   **Capabilities & Constraints**:
   - **Capabilities** (tag input, array, optional)
     - Add/remove tags
     - Press Enter to add
     - Click X to remove
     - Examples: "TypeScript", "React", "Laravel", "API Design"
   - **Constraints** (tag input, array, optional)
     - Same UI as capabilities
     - Examples: "No database access", "Read-only", "Sandboxed"
   - **Tools** (tag input, array, optional)
     - Same UI as capabilities
     - Examples: "git", "npm", "composer", "docker"
   
   **Metadata**:
   - **Metadata** (JSON editor OR key-value pair input, optional)
     - Simple key-value pairs for MVP
     - Option 1: Text input pairs with add/remove
     - Option 2: JSON textarea with syntax highlighting (future)

3. **Form Behavior**
   - **Create Mode**: All fields empty, focus on name field
   - **Edit Mode**: All fields pre-populated
   - **Slug Auto-generation**: Name → slug (kebab-case), can be edited
   - **Type → Mode Mapping**: Selecting type auto-fills recommended mode
   - **Validation**: Client-side validation before submit
   - **Unsaved Changes Warning**: Prompt when closing with unsaved changes

4. **Header Actions**
   - Title: "Create Agent" or "Edit Agent: {name}"
   - Close button (X) in top-right
   - Three-dot menu (⋮) next to close (only in edit mode):
     - "Duplicate" - Create copy
     - "Delete" - Delete agent (requires confirmation)

5. **Footer Actions**
   - Cancel button (secondary)
   - Save button (primary, disabled if invalid)
   - Loading state on save

### Technical Requirements

1. **Component Props**
   ```typescript
   interface AgentProfileEditorProps {
     agent?: Agent | null  // null/undefined = create mode
     isOpen: boolean
     onClose: () => void
     onSave: (agent: Agent) => Promise<void>
     onDelete?: (agent: Agent) => Promise<void>
     onDuplicate?: (agent: Agent) => Promise<void>
   }
   ```

2. **Form State Management**
   - Use React Hook Form or similar
   - Track dirty state
   - Validation on blur and submit
   - Error messages under invalid fields

3. **API Integration** (not this task, but structure for it)
   - Fetch type/mode/status options on mount
   - Submit creates or updates agent
   - Handle loading and error states

## Implementation Details

### Component Structure

```typescript
// resources/js/components/agents/AgentProfileEditor.tsx

import { useForm } from 'react-hook-form'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { TagInput } from '@/components/ui/tag-input' // May need to create
import { MoreVertical, Copy, Trash2 } from 'lucide-react'

interface AgentFormData {
  name: string
  slug: string
  type: string
  mode: string
  status: string
  description?: string
  capabilities?: string[]
  constraints?: string[]
  tools?: string[]
  metadata?: Record<string, any>
}

export function AgentProfileEditor({
  agent,
  isOpen,
  onClose,
  onSave,
  onDelete,
  onDuplicate
}: AgentProfileEditorProps) {
  const isEditMode = !!agent
  
  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors, isDirty, isSubmitting }
  } = useForm<AgentFormData>({
    defaultValues: agent || {
      name: '',
      slug: '',
      type: '',
      mode: '',
      status: 'active',
      capabilities: [],
      constraints: [],
      tools: [],
      metadata: {}
    }
  })

  // Auto-generate slug from name
  const name = watch('name')
  useEffect(() => {
    if (!isEditMode && name) {
      const slug = name.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
      setValue('slug', slug)
    }
  }, [name, isEditMode])

  // Auto-set mode based on type
  const type = watch('type')
  useEffect(() => {
    if (type) {
      const typeOption = types.find(t => t.value === type)
      if (typeOption) {
        setValue('mode', typeOption.default_mode)
      }
    }
  }, [type])

  // Handle close with unsaved changes
  const handleClose = () => {
    if (isDirty) {
      if (confirm('You have unsaved changes. Are you sure you want to close?')) {
        onClose()
      }
    } else {
      onClose()
    }
  }

  const onSubmit = async (data: AgentFormData) => {
    await onSave(data)
    onClose()
  }

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <div className="flex justify-between items-center">
            <DialogTitle>
              {isEditMode ? `Edit Agent: ${agent.name}` : 'Create Agent'}
            </DialogTitle>
            
            {isEditMode && (
              <DropdownMenu>
                <DropdownMenuTrigger>
                  <MoreVertical className="h-5 w-5" />
                </DropdownMenuTrigger>
                <DropdownMenuContent>
                  <DropdownMenuItem onClick={() => onDuplicate?.(agent)}>
                    <Copy className="mr-2 h-4 w-4" />
                    Duplicate
                  </DropdownMenuItem>
                  <DropdownMenuItem 
                    onClick={() => onDelete?.(agent)}
                    className="text-destructive"
                  >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {/* Basic Info Section */}
          <div className="space-y-4">
            <h3 className="font-semibold">Basic Information</h3>
            
            <div>
              <Label htmlFor="name">Name *</Label>
              <Input
                id="name"
                {...register('name', { required: 'Name is required' })}
                placeholder="e.g., Senior Backend Engineer"
              />
              {errors.name && (
                <p className="text-sm text-destructive mt-1">{errors.name.message}</p>
              )}
            </div>

            <div>
              <Label htmlFor="slug">Slug *</Label>
              <Input
                id="slug"
                {...register('slug', {
                  required: 'Slug is required',
                  pattern: {
                    value: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
                    message: 'Slug must be lowercase kebab-case'
                  }
                })}
                placeholder="e.g., senior-backend-engineer"
              />
              {errors.slug && (
                <p className="text-sm text-destructive mt-1">{errors.slug.message}</p>
              )}
              <p className="text-xs text-muted-foreground mt-1">
                Auto-generated from name, can be edited
              </p>
            </div>

            <div>
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                {...register('description')}
                placeholder="Describe this agent's purpose and expertise..."
                rows={3}
              />
            </div>
          </div>

          {/* Classification Section */}
          <div className="space-y-4">
            <h3 className="font-semibold">Classification</h3>
            
            <div>
              <Label htmlFor="type">Type *</Label>
              <Select {...register('type', { required: 'Type is required' })}>
                <SelectTrigger>
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  {types.map(type => (
                    <SelectItem key={type.value} value={type.value}>
                      {type.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="mode">Mode *</Label>
              <Select {...register('mode', { required: 'Mode is required' })}>
                <SelectTrigger>
                  <SelectValue placeholder="Select mode" />
                </SelectTrigger>
                <SelectContent>
                  {modes.map(mode => (
                    <SelectItem key={mode.value} value={mode.value}>
                      {mode.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="status">Status *</Label>
              <Select {...register('status', { required: 'Status is required' })}>
                <SelectTrigger>
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  {statuses.map(status => (
                    <SelectItem key={status.value} value={status.value}>
                      {status.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Capabilities Section */}
          <div className="space-y-4">
            <h3 className="font-semibold">Capabilities & Constraints</h3>
            
            <div>
              <Label>Capabilities</Label>
              <TagInput
                value={watch('capabilities') || []}
                onChange={(tags) => setValue('capabilities', tags)}
                placeholder="Add capability (press Enter)"
              />
            </div>

            <div>
              <Label>Constraints</Label>
              <TagInput
                value={watch('constraints') || []}
                onChange={(tags) => setValue('constraints', tags)}
                placeholder="Add constraint (press Enter)"
              />
            </div>

            <div>
              <Label>Tools</Label>
              <TagInput
                value={watch('tools') || []}
                onChange={(tags) => setValue('tools', tags)}
                placeholder="Add tool (press Enter)"
              />
            </div>
          </div>

          {/* Footer */}
          <DialogFooter>
            <Button type="button" variant="outline" onClick={handleClose}>
              Cancel
            </Button>
            <Button 
              type="submit" 
              disabled={isSubmitting || !isDirty}
              loading={isSubmitting}
            >
              {isEditMode ? 'Save Changes' : 'Create Agent'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
```

### TagInput Component (New Utility Component)

```typescript
// resources/js/components/ui/tag-input.tsx

import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { X } from 'lucide-react'

interface TagInputProps {
  value: string[]
  onChange: (tags: string[]) => void
  placeholder?: string
}

export function TagInput({ value, onChange, placeholder }: TagInputProps) {
  const [input, setInput] = useState('')

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && input.trim()) {
      e.preventDefault()
      if (!value.includes(input.trim())) {
        onChange([...value, input.trim()])
      }
      setInput('')
    } else if (e.key === 'Backspace' && !input && value.length > 0) {
      onChange(value.slice(0, -1))
    }
  }

  const removeTag = (index: number) => {
    onChange(value.filter((_, i) => i !== index))
  }

  return (
    <div className="flex flex-wrap gap-2 p-2 border rounded-md min-h-[40px]">
      {value.map((tag, index) => (
        <Badge key={index} variant="secondary" className="gap-1">
          {tag}
          <button
            type="button"
            onClick={() => removeTag(index)}
            className="hover:bg-destructive/20 rounded-full p-0.5"
          >
            <X className="h-3 w-3" />
          </button>
        </Badge>
      ))}
      <Input
        value={input}
        onChange={(e) => setInput(e.target.value)}
        onKeyDown={handleKeyDown}
        placeholder={placeholder}
        className="border-0 focus-visible:ring-0 flex-1 min-w-[120px] p-0"
      />
    </div>
  )
}
```

## Acceptance Criteria

- [ ] Modal opens and closes smoothly
- [ ] Create mode: empty form with focus on name
- [ ] Edit mode: form pre-populated with agent data
- [ ] Name field generates slug automatically
- [ ] Slug field is editable and validates kebab-case
- [ ] Type dropdown populated from backend
- [ ] Mode auto-fills based on type selection
- [ ] Status dropdown works
- [ ] Tag inputs work for capabilities/constraints/tools
- [ ] Press Enter to add tag
- [ ] Click X to remove tag
- [ ] Form validation shows errors
- [ ] Save button disabled when invalid or not dirty
- [ ] Unsaved changes prompt on close
- [ ] Three-dot menu shows in edit mode (Edit/Duplicate/Delete)
- [ ] Cancel button works
- [ ] Save button triggers onSave callback
- [ ] Modal is responsive (full screen on mobile)
- [ ] No TypeScript errors

## Files to Create/Modify

### New Files
- `resources/js/components/agents/AgentProfileEditor.tsx` - Main editor modal
- `resources/js/components/ui/tag-input.tsx` - Tag input utility component

### Files to Modify
- `resources/js/pages/AgentDashboard.tsx` - Wire up editor modal

### Files to Reference
- `resources/js/components/ui/dialog.tsx` - Shadcn Dialog
- `resources/js/components/ui/form.tsx` - Shadcn Form components
- Other modal forms in app for patterns

## Testing Checklist

- [ ] Open editor in create mode
- [ ] Open editor in edit mode
- [ ] Type in name field, slug auto-generates
- [ ] Manually edit slug field
- [ ] Select type, mode auto-populates
- [ ] Manually change mode
- [ ] Add tags to capabilities
- [ ] Remove tags from capabilities
- [ ] Submit valid form
- [ ] Submit invalid form (shows errors)
- [ ] Close with unsaved changes (shows prompt)
- [ ] Close without changes (no prompt)
- [ ] Three-dot menu works (edit mode only)
- [ ] Responsive on mobile

## Notes

- **React Hook Form**: Preferred for form state management
- **Validation**: Client-side first, server-side validation will be in UI-04
- **TagInput**: Simple implementation, can be enhanced later
- **Metadata**: Keep simple (skip for MVP if complex), or use key-value pairs
- **Accessibility**: Ensure keyboard navigation works for all fields
- **Loading States**: Button shows spinner while submitting

## Dependencies

**Before Starting**:
- ✅ UI-02 completed (cards trigger editor)
- ✅ Shadcn/ui Dialog component available
- ✅ Shadcn/ui Form components available
- ✅ React Hook Form installed (or alternative)

**Blocked By**: UI-02

**Blocks**: UI-04 (needs editor to wire up save/delete)
