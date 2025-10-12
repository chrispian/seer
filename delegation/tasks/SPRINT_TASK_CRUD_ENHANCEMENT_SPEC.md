# Sprint & Task CRUD Enhancement Specification

**Created**: 2025-10-12  
**Priority**: High  
**Status**: Planning  
**Estimated Effort**: 3-4 days

---

## Overview

Transform Sprint and Task modules into fully-featured CRUD systems with:
- Click-to-edit inline fields with autosave
- Rich markdown editors for content fields
- Tag management
- Assignment workflows
- Navigation/pagination
- Unified content structure across both models
- Full audit logging

---

## Completed ✅

1. **Sprint Create** - Form modal with navigation stack
2. **Sprint Edit** - Edit command and form pre-population
3. **Task Code Generator** - LLM-based auto-generation (PREFIX-###)
4. **Modal Consistency** - Fixed heights and overflow issues
5. **Status Filtering** - Normalized "done"/"completed" synonyms

---

## Architecture Principles

### 1. Inline Editing Pattern
```tsx
// Click-to-edit component
<InlineEdit
  value={field}
  onSave={async (newValue) => {
    await api.update(id, { field: newValue })
    // Auto-audit logged
  }}
  type="text|textarea|select|tags"
  placeholder="Click to edit..."
/>
```

### 2. Content Fields Structure
Both Sprints and Tasks should have identical content tabs:
- **Activity** - Auto-generated timeline (read-only)
- **Agent** - Agent assignment notes
- **Plan** - Execution plan
- **Context** - Background information
- **Todo** - Checklist items
- **Summary** - Overview/conclusion

### 3. Audit Logging
Every edit triggers:
```php
activity()
    ->performedOn($model)
    ->withProperties(['old' => $old, 'new' => $new])
    ->log('updated_field')
```

### 4. API Design
```php
// Single field update endpoint
PATCH /api/sprints/{id}/field
{
    "field": "title",
    "value": "New Title"
}

// Content field update
PATCH /api/sprints/{id}/content
{
    "field": "plan",
    "content": "# Plan\n..."
}
```

---

## Feature Breakdown

## TASK 1: Inline Field Editing

### 1.1 Task Detail Modal - Basic Fields

**Fields to Make Editable:**
- ✅ Task Code (auto-generated or manual)
- Title/Name
- Description
- Status (dropdown)
- Priority (dropdown)
- Estimate Text
- Sprint Assignment (dropdown with search)

**Implementation:**

#### Frontend Component
```tsx
// components/ui/InlineEditText.tsx
interface InlineEditTextProps {
  value: string
  onSave: (value: string) => Promise<void>
  placeholder?: string
  multiline?: boolean
  className?: string
}

export function InlineEditText({ value, onSave, multiline, placeholder }: InlineEditTextProps) {
  const [isEditing, setIsEditing] = useState(false)
  const [editValue, setEditValue] = useState(value)
  const [isSaving, setIsSaving] = useState(false)
  
  const handleSave = async () => {
    if (editValue === value) {
      setIsEditing(false)
      return
    }
    
    setIsSaving(true)
    try {
      await onSave(editValue)
      setIsEditing(false)
    } catch (error) {
      console.error('Save failed:', error)
      setEditValue(value) // Revert
    } finally {
      setIsSaving(false)
    }
  }
  
  if (isEditing) {
    return multiline ? (
      <Textarea
        value={editValue}
        onChange={(e) => setEditValue(e.target.value)}
        onBlur={handleSave}
        onKeyDown={(e) => {
          if (e.key === 'Escape') {
            setEditValue(value)
            setIsEditing(false)
          }
        }}
        autoFocus
        disabled={isSaving}
      />
    ) : (
      <Input
        value={editValue}
        onChange={(e) => setEditValue(e.target.value)}
        onBlur={handleSave}
        onKeyDown={(e) => {
          if (e.key === 'Enter') handleSave()
          if (e.key === 'Escape') {
            setEditValue(value)
            setIsEditing(false)
          }
        }}
        autoFocus
        disabled={isSaving}
      />
    )
  }
  
  return (
    <div 
      onClick={() => setIsEditing(true)}
      className="cursor-pointer hover:bg-muted/50 rounded px-2 py-1 transition-colors"
    >
      {value || <span className="text-muted-foreground">{placeholder || 'Click to edit...'}</span>}
    </div>
  )
}
```

#### Backend API
```php
// app/Http/Controllers/Api/TaskController.php
public function updateField(Request $request, WorkItem $task)
{
    $validated = $request->validate([
        'field' => 'required|string|in:task_name,description,status,priority,estimate_text',
        'value' => 'nullable|string',
    ]);
    
    $field = $validated['field'];
    $value = $validated['value'];
    
    // Update metadata
    $metadata = $task->metadata ?? [];
    $metadata[$field] = $value;
    $task->metadata = $metadata;
    $task->save();
    
    // Audit log
    activity()
        ->performedOn($task)
        ->withProperties(['field' => $field, 'old' => $task->getOriginal('metadata')[$field] ?? null, 'new' => $value])
        ->log('updated_field');
    
    return response()->json(['success' => true, 'task' => $task]);
}
```

### 1.2 Inline Dropdown (Status, Priority)

```tsx
// components/ui/InlineEditSelect.tsx
export function InlineEditSelect({ value, options, onSave }: Props) {
  const [isEditing, setIsEditing] = useState(false)
  
  if (isEditing) {
    return (
      <Select
        value={value}
        onValueChange={async (newValue) => {
          await onSave(newValue)
          setIsEditing(false)
        }}
        onOpenChange={(open) => !open && setIsEditing(false)}
        autoFocus
      >
        <SelectTrigger>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          {options.map(opt => (
            <SelectItem key={opt.value} value={opt.value}>
              {opt.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    )
  }
  
  return (
    <Badge 
      onClick={() => setIsEditing(true)}
      className="cursor-pointer hover:opacity-80"
    >
      {value}
    </Badge>
  )
}
```

### 1.3 Sprint Detail Modal - Inline Edits

Same pattern, apply to:
- Sprint Title
- Sprint Status
- Start Date (date picker)
- End Date (date picker)
- Priority

---

## TASK 2: Tag Management

### 2.1 Tag Editor Component

```tsx
// components/ui/TagEditor.tsx
export function TagEditor({ tags, onSave }: Props) {
  const [localTags, setLocalTags] = useState(tags)
  const [inputValue, setInputValue] = useState('')
  
  const addTag = async (tag: string) => {
    const newTags = [...localTags, tag]
    setLocalTags(newTags)
    await onSave(newTags)
  }
  
  const removeTag = async (index: number) => {
    const newTags = localTags.filter((_, i) => i !== index)
    setLocalTags(newTags)
    await onSave(newTags)
  }
  
  return (
    <div className="flex flex-wrap gap-2 items-center">
      {localTags.map((tag, i) => (
        <Badge key={i} variant="secondary" className="group">
          {tag}
          <X 
            className="ml-1 h-3 w-3 cursor-pointer opacity-0 group-hover:opacity-100"
            onClick={() => removeTag(i)}
          />
        </Badge>
      ))}
      <Input
        value={inputValue}
        onChange={(e) => setInputValue(e.target.value)}
        onKeyDown={(e) => {
          if (e.key === 'Enter' && inputValue.trim()) {
            addTag(inputValue.trim())
            setInputValue('')
          }
        }}
        placeholder="Add tag..."
        className="w-32 h-6 text-sm"
      />
    </div>
  )
}
```

### 2.2 Backend Integration

```php
// Task/Sprint models both support tags in metadata
$task->metadata = [
    ...$task->metadata,
    'tags' => ['bug', 'urgent', 'frontend']
];
```

---

## TASK 3: TipTap Markdown Editors

### 3.1 Rich Text Editor Component

```tsx
// components/ui/MarkdownEditor.tsx
import { useEditor, EditorContent } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import Markdown from '@tiptap/extension-markdown'

export function MarkdownEditor({ content, onSave, readOnly }: Props) {
  const [isEditing, setIsEditing] = useState(false)
  
  const editor = useEditor({
    extensions: [StarterKit, Markdown],
    content,
    editable: isEditing,
    onUpdate: ({ editor }) => {
      // Debounced autosave
      debouncedSave(editor.storage.markdown.getMarkdown())
    },
  })
  
  const debouncedSave = useDebouncedCallback(async (markdown: string) => {
    await onSave(markdown)
  }, 1000)
  
  if (readOnly) {
    return (
      <div className="prose prose-sm max-w-none">
        <ReactMarkdown>{content}</ReactMarkdown>
      </div>
    )
  }
  
  return (
    <div className="space-y-2">
      {isEditing && (
        <div className="flex gap-1 p-2 border-b bg-muted/30">
          <Button size="sm" onClick={() => editor?.chain().focus().toggleBold().run()}>
            <Bold className="h-4 w-4" />
          </Button>
          <Button size="sm" onClick={() => editor?.chain().focus().toggleItalic().run()}>
            <Italic className="h-4 w-4" />
          </Button>
          <Button size="sm" onClick={() => editor?.chain().focus().toggleHeading({ level: 2 }).run()}>
            <Heading className="h-4 w-4" />
          </Button>
          <Button size="sm" onClick={() => editor?.chain().focus().toggleBulletList().run()}>
            <List className="h-4 w-4" />
          </Button>
          <Button size="sm" onClick={() => editor?.chain().focus().toggleCodeBlock().run()}>
            <Code className="h-4 w-4" />
          </Button>
        </div>
      )}
      
      <EditorContent 
        editor={editor} 
        onClick={() => !isEditing && setIsEditing(true)}
        className={isEditing ? 'border rounded p-2' : 'cursor-pointer hover:bg-muted/30 rounded p-2'}
      />
    </div>
  )
}
```

### 3.2 Content Field Storage

Add to both Sprint and WorkItem models:

```php
// Migration
Schema::table('sprints', function (Blueprint $table) {
    $table->jsonb('content')->nullable();
});

Schema::table('work_items', function (Blueprint $table) {
    $table->jsonb('content')->nullable();
});

// Model structure
'content' => [
    'agent' => 'Markdown text...',
    'plan' => 'Markdown text...',
    'context' => 'Markdown text...',
    'todo' => 'Markdown text...',
    'summary' => 'Markdown text...',
]
```

### 3.3 Content Update API

```php
// app/Http/Controllers/Api/TaskController.php
public function updateContent(Request $request, WorkItem $task)
{
    $validated = $request->validate([
        'field' => 'required|string|in:agent,plan,context,todo,summary',
        'content' => 'required|string',
    ]);
    
    $content = $task->content ?? [];
    $old = $content[$validated['field']] ?? null;
    $content[$validated['field']] = $validated['content'];
    $task->content = $content;
    $task->save();
    
    // Audit
    activity()
        ->performedOn($task)
        ->withProperties([
            'field' => $validated['field'],
            'old_length' => strlen($old ?? ''),
            'new_length' => strlen($validated['content']),
        ])
        ->log('updated_content');
    
    return response()->json(['success' => true]);
}
```

---

## TASK 4: Agent Assignment

### 4.1 Agent Selector Component

```tsx
// components/ui/AgentSelector.tsx
export function AgentSelector({ currentAgent, onAssign }: Props) {
  const { data: agents } = useQuery({
    queryKey: ['agents'],
    queryFn: () => fetch('/api/agents').then(r => r.json())
  })
  
  return (
    <Select
      value={currentAgent?.id}
      onValueChange={async (agentId) => {
        await onAssign(agentId)
      }}
    >
      <SelectTrigger>
        <SelectValue placeholder="Assign agent..." />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="unassigned">Unassigned</SelectItem>
        {agents?.map(agent => (
          <SelectItem key={agent.id} value={agent.id}>
            <div className="flex items-center gap-2">
              <Badge variant="outline">{agent.designation}</Badge>
              {agent.name}
            </div>
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
```

### 4.2 Assignment API

```php
// POST /api/tasks/{id}/assign
public function assign(Request $request, WorkItem $task)
{
    $validated = $request->validate([
        'agent_id' => 'nullable|exists:agents,id',
    ]);
    
    $oldAgent = $task->delegation_context['current_agent'] ?? null;
    $newAgent = $validated['agent_id'] 
        ? Agent::find($validated['agent_id'])->name 
        : null;
    
    $task->delegation_context = [
        ...$task->delegation_context ?? [],
        'current_agent' => $newAgent,
        'assigned_at' => now(),
        'assigned_by' => auth()->user()->name,
    ];
    
    $task->delegation_status = $newAgent ? 'assigned' : 'unassigned';
    $task->save();
    
    // Create assignment record
    TaskAssignment::create([
        'work_item_id' => $task->id,
        'agent_id' => $validated['agent_id'],
        'assigned_at' => now(),
        'status' => 'active',
    ]);
    
    // Audit
    activity()
        ->performedOn($task)
        ->withProperties(['old_agent' => $oldAgent, 'new_agent' => $newAgent])
        ->log('assigned_agent');
    
    return response()->json(['success' => true, 'task' => $task]);
}
```

---

## TASK 5: Sprint Assignment

### 5.1 Sprint Selector in Task Detail

```tsx
// In TaskDetailModal
<InlineEditSelect
  value={task.sprint_code}
  options={sprints.map(s => ({ value: s.code, label: s.code }))}
  onSave={async (sprintCode) => {
    await fetch(`/api/tasks/${task.id}/sprint`, {
      method: 'PATCH',
      body: JSON.stringify({ sprint_code: sprintCode })
    })
  }}
  placeholder="No sprint"
/>
```

### 5.2 Sprint Assignment API

```php
// PATCH /api/tasks/{id}/sprint
public function assignToSprint(Request $request, WorkItem $task)
{
    $validated = $request->validate([
        'sprint_code' => 'nullable|exists:sprints,code',
    ]);
    
    $sprint = Sprint::where('code', $validated['sprint_code'])->first();
    $oldSprint = $task->metadata['sprint_code'] ?? null;
    
    // Remove from old sprint
    if ($oldSprint) {
        SprintItem::where('work_item_id', $task->id)->delete();
    }
    
    // Add to new sprint
    if ($sprint) {
        SprintItem::create([
            'sprint_id' => $sprint->id,
            'work_item_id' => $task->id,
            'added_at' => now(),
        ]);
        
        $task->metadata = [
            ...$task->metadata,
            'sprint_code' => $sprint->code,
        ];
    } else {
        $metadata = $task->metadata;
        unset($metadata['sprint_code']);
        $task->metadata = $metadata;
    }
    
    $task->save();
    
    // Audit
    activity()
        ->performedOn($task)
        ->withProperties(['old_sprint' => $oldSprint, 'new_sprint' => $validated['sprint_code']])
        ->log('moved_sprint');
    
    return response()->json(['success' => true]);
}
```

---

## TASK 6: Pagination/Navigation

### 6.1 Next/Previous Navigation

```tsx
// In TaskDetailModal
interface TaskDetailModalProps {
  // ... existing props
  navigationContext?: {
    currentIndex: number
    totalCount: number
    onNext: () => void
    onPrevious: () => void
  }
}

// In modal header
{navigationContext && (
  <div className="flex items-center gap-2 ml-auto">
    <span className="text-sm text-muted-foreground">
      {navigationContext.currentIndex + 1} of {navigationContext.totalCount}
    </span>
    <Button
      size="sm"
      variant="ghost"
      onClick={navigationContext.onPrevious}
      disabled={navigationContext.currentIndex === 0}
    >
      <ChevronLeft className="h-4 w-4" />
    </Button>
    <Button
      size="sm"
      variant="ghost"
      onClick={navigationContext.onNext}
      disabled={navigationContext.currentIndex === navigationContext.totalCount - 1}
    >
      <ChevronRight className="h-4 w-4" />
    </Button>
  </div>
)}
```

### 6.2 Sprint Context Navigation

When viewing task from sprint detail:
- Next/Previous only within current sprint's tasks
- Preserve filter state
- Update URL/history

```tsx
// In SprintDetailModal
const handleTaskSelect = (task: Task, index: number) => {
  const navigationContext = {
    currentIndex: index,
    totalCount: filteredTasks.length,
    onNext: () => {
      const nextIndex = index + 1
      if (nextIndex < filteredTasks.length) {
        handleTaskSelect(filteredTasks[nextIndex], nextIndex)
      }
    },
    onPrevious: () => {
      const prevIndex = index - 1
      if (prevIndex >= 0) {
        handleTaskSelect(filteredTasks[prevIndex], prevIndex)
      }
    },
  }
  
  onTaskSelect?.(task, navigationContext)
}
```

---

## TASK 7: Unified Content Structure

### 7.1 Migration - Add Content to Sprints

```php
// database/migrations/add_content_to_sprints.php
Schema::table('sprints', function (Blueprint $table) {
    $table->jsonb('content')->nullable()->after('meta');
});

Schema::table('work_items', function (Blueprint $table) {
    // Add if not exists
    if (!Schema::hasColumn('work_items', 'content')) {
        $table->jsonb('content')->nullable()->after('metadata');
    }
});
```

### 7.2 Content Tab Component (Shared)

```tsx
// components/orchestration/ContentTabs.tsx
interface ContentTabsProps {
  modelType: 'sprint' | 'task'
  modelId: string
  content: Record<string, string>
  readOnly?: boolean
  onUpdate: (field: string, value: string) => Promise<void>
}

export function ContentTabs({ modelType, modelId, content, readOnly, onUpdate }: ContentTabsProps) {
  const tabs = [
    { key: 'agent', label: 'Agent', icon: <Users className="h-4 w-4" /> },
    { key: 'plan', label: 'Plan', icon: <FileText className="h-4 w-4" /> },
    { key: 'context', label: 'Context', icon: <Info className="h-4 w-4" /> },
    { key: 'todo', label: 'Todo', icon: <CheckSquare className="h-4 w-4" /> },
    { key: 'summary', label: 'Summary', icon: <FileCheck className="h-4 w-4" /> },
  ]
  
  return (
    <Tabs defaultValue="agent" className="h-full flex flex-col">
      <TabsList>
        {tabs.map(tab => (
          <TabsTrigger key={tab.key} value={tab.key}>
            {tab.icon}
            <span>{tab.label}</span>
          </TabsTrigger>
        ))}
      </TabsList>
      
      {tabs.map(tab => (
        <TabsContent key={tab.key} value={tab.key} className="flex-1">
          <MarkdownEditor
            content={content[tab.key] || ''}
            onSave={(value) => onUpdate(tab.key, value)}
            readOnly={readOnly}
          />
        </TabsContent>
      ))}
    </Tabs>
  )
}
```

### 7.3 Use in Both Modals

```tsx
// In TaskDetailModal
<ContentTabs
  modelType="task"
  modelId={task.id}
  content={task.content || {}}
  onUpdate={async (field, value) => {
    await fetch(`/api/tasks/${task.id}/content`, {
      method: 'PATCH',
      body: JSON.stringify({ field, content: value })
    })
  }}
/>

// In SprintDetailModal (new feature)
<ContentTabs
  modelType="sprint"
  modelId={sprint.id}
  content={sprint.content || {}}
  onUpdate={async (field, value) => {
    await fetch(`/api/sprints/${sprint.id}/content`, {
      method: 'PATCH',
      body: JSON.stringify({ field, content: value })
    })
  }}
/>
```

---

## TASK 8: Audit Logging

### 8.1 Ensure Activity Log Package

```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan migrate
```

### 8.2 Add to Models

```php
// app/Models/Sprint.php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Sprint extends Model
{
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'starts_on', 'ends_on', 'meta', 'content'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

// Same for WorkItem
```

### 8.3 Activity Timeline Component

Already exists, ensure it displays all logged activities:

```tsx
// TaskActivityTimeline.tsx already handles this
// Just ensure backend provides activities:

// GET /api/tasks/{id}/activities
public function activities(WorkItem $task)
{
    $activities = Activity::forSubject($task)
        ->with('causer')
        ->latest()
        ->get()
        ->map(fn($activity) => [
            'id' => $activity->id,
            'description' => $activity->description,
            'properties' => $activity->properties,
            'causer' => $activity->causer?->name,
            'created_at' => $activity->created_at->toISOString(),
        ]);
    
    return response()->json($activities);
}
```

---

## Implementation Order (Recommended)

### Phase 1: Foundation (1-2 days)
1. ✅ Task code generator
2. ✅ Modal height fixes
3. Create shared components:
   - InlineEditText
   - InlineEditSelect
   - TagEditor
4. Add content JSONB columns to both models
5. Set up audit logging

### Phase 2: Basic Inline Editing (1 day)
6. Task title/description inline edit
7. Task status/priority dropdowns
8. Sprint title/dates inline edit
9. Tags for both tasks and sprints

### Phase 3: Rich Content (1 day)
10. Integrate TipTap
11. Create MarkdownEditor component
12. Create ContentTabs shared component
13. Add content editors to task modal
14. Add content editors to sprint detail

### Phase 4: Assignments & Navigation (1 day)
15. Agent assignment to tasks
16. Task-to-sprint assignment
17. Sprint assignment dropdown in task detail
18. Next/Previous navigation
19. Context-aware navigation (within sprint)

### Phase 5: Polish & Testing (0.5 days)
20. Test all autosave functionality
21. Verify audit logs
22. Test keyboard shortcuts (Enter, Escape)
23. Test validation and error handling
24. Performance testing with large datasets

---

## API Routes Summary

```php
// routes/api.php

// Task Field Updates
PATCH /api/tasks/{id}/field          // Update single field
PATCH /api/tasks/{id}/content        // Update content field
PATCH /api/tasks/{id}/tags           // Update tags
PATCH /api/tasks/{id}/sprint         // Assign to sprint
POST  /api/tasks/{id}/assign         // Assign agent
GET   /api/tasks/{id}/activities     // Get activity log

// Sprint Field Updates
PATCH /api/sprints/{id}/field        // Update single field
PATCH /api/sprints/{id}/content      // Update content field
PATCH /api/sprints/{id}/tags         // Update tags
GET   /api/sprints/{id}/activities   // Get activity log

// Agents (for selector)
GET   /api/agents                     // List all agents

// Sprints (for selector)
GET   /api/sprints?limit=100          // List for dropdown
```

---

## Database Schema Changes

```sql
-- Add content JSONB column to sprints
ALTER TABLE sprints ADD COLUMN content JSONB;

-- Add content JSONB column to work_items (if not exists)
ALTER TABLE work_items ADD COLUMN content JSONB;

-- Ensure activity_log table exists (from spatie/laravel-activitylog)
-- Migration auto-created by package

-- Index for performance
CREATE INDEX idx_sprints_content ON sprints USING GIN (content);
CREATE INDEX idx_work_items_content ON work_items USING GIN (content);
```

---

## Testing Checklist

### Inline Editing
- [ ] Click field to enter edit mode
- [ ] Type new value
- [ ] Press Enter to save
- [ ] Press Escape to cancel
- [ ] Click outside to save
- [ ] Verify autosave indicator
- [ ] Check audit log created

### Tags
- [ ] Add new tag via Enter
- [ ] Remove tag via X button
- [ ] Tags persist after refresh
- [ ] Special characters handled
- [ ] Empty tag rejected

### Content Editors
- [ ] Click to enter edit mode
- [ ] Toolbar buttons work
- [ ] Markdown preview accurate
- [ ] Autosave after 1 second
- [ ] Large content (>10KB) handled
- [ ] Code blocks formatted correctly

### Assignments
- [ ] Assign agent to task
- [ ] Reassign to different agent
- [ ] Unassign agent
- [ ] Assign task to sprint
- [ ] Move task between sprints
- [ ] Remove from sprint

### Navigation
- [ ] Next button navigates forward
- [ ] Previous button navigates back
- [ ] Buttons disabled at boundaries
- [ ] Count displays correctly
- [ ] Works with filtered lists
- [ ] Preserves context (sprint scope)

### Audit Logging
- [ ] All edits logged
- [ ] Old/new values captured
- [ ] User attribution correct
- [ ] Timestamps accurate
- [ ] Activity timeline displays logs

---

## Dependencies

### NPM Packages
```json
{
  "@tiptap/react": "^2.1.0",
  "@tiptap/starter-kit": "^2.1.0",
  "@tiptap/extension-markdown": "^2.1.0",
  "lodash.debounce": "^4.0.8"
}
```

### Composer Packages
```json
{
  "spatie/laravel-activitylog": "^4.7"
}
```

---

## Future Enhancements (Out of Scope)

- Bulk operations (multi-select + action)
- Advanced filtering (date ranges, text search)
- Export sprint/task data (CSV, PDF)
- Keyboard shortcuts (Vim-style navigation)
- Offline mode with sync
- Real-time collaboration
- Version history / undo
- Custom field definitions
- Automation rules

---

## Success Criteria

✅ **All fields editable inline** - Click, edit, autosave works  
✅ **Rich content editing** - TipTap editors functional on all content tabs  
✅ **Tag management** - Add/remove tags easily  
✅ **Assignments work** - Agent and sprint assignments functional  
✅ **Navigation smooth** - Next/Previous within context  
✅ **Audit complete** - All changes logged  
✅ **Performance good** - <200ms for field updates  
✅ **No data loss** - Autosave reliable, error handling robust  

---

## Related Documentation

- `SPRINT_MODULE_SPEC.md` - Original sprint CRUD spec
- `MODAL_NAVIGATION_PATTERN.md` - Navigation stack pattern
- `ADR-005-modal-navigation-pattern.md` - Architecture decision
- Spatie Activity Log: https://spatie.be/docs/laravel-activitylog/v4
- TipTap Docs: https://tiptap.dev/

---

**Ready to delegate specific tasks from this spec!**
