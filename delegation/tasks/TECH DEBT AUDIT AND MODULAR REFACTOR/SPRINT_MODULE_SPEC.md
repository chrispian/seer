# Sprint Module Complete Specification
## Full CRUD + Actions System Design
## Target: Production-Ready Sprint Management

---

## Current State Analysis

### What Works ✅
- `/sprints` - List view with task counts
- `/sprint-detail` - Detail view with task list
- Basic data fetching and display
- Navigation between sprints and tasks

### What's Missing ❌
- Create sprint UI and flow
- Edit sprint capabilities
- Delete with confirmation
- State transitions (planned → active → completed)
- Bulk operations
- Task assignment/delegation
- Export/print functionality
- Validation and business rules

---

## Complete CRUD Specification

### 1. CREATE - New Sprint

#### Command: `/sprint-create`
```php
namespace App\Commands\Orchestration\Sprint;

class CreateCommand extends BaseCommand {
    public function handle(): array {
        return $this->respond([
            'component' => 'SprintFormModal',
            'mode' => 'create',
            'data' => [
                'default_values' => [
                    'status' => 'planned',
                    'start_date' => now()->startOfWeek()->format('Y-m-d'),
                    'end_date' => now()->endOfWeek()->addWeek()->format('Y-m-d'),
                ],
                'available_tasks' => $this->getUnassignedTasks(),
                'team_members' => $this->getTeamMembers(),
            ]
        ]);
    }
}
```

#### Frontend Form Component
```typescript
interface SprintFormModalProps {
    mode: 'create' | 'edit'
    data: {
        sprint?: Sprint
        default_values?: Partial<Sprint>
        available_tasks?: Task[]
        team_members?: User[]
    }
    onSubmit: (data: SprintFormData) => void
    onClose: () => void
}

export function SprintFormModal({ mode, data, onSubmit, onClose }: SprintFormModalProps) {
    const form = useForm<SprintFormData>({
        defaultValues: mode === 'edit' ? data.sprint : data.default_values
    })
    
    return (
        <Dialog open onOpenChange={onClose}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{mode === 'create' ? 'Create Sprint' : 'Edit Sprint'}</DialogTitle>
                </DialogHeader>
                
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                        <FormField name="title" label="Sprint Title" required>
                            <Input placeholder="Sprint 2025-W42" />
                        </FormField>
                        
                        <FormField name="code" label="Sprint Code" required>
                            <Input placeholder="SPR-2025-42" pattern="^SPR-\d{4}-\d{2}$" />
                        </FormField>
                        
                        <div className="grid grid-cols-2 gap-4">
                            <FormField name="start_date" label="Start Date" required>
                                <DatePicker />
                            </FormField>
                            
                            <FormField name="end_date" label="End Date" required>
                                <DatePicker />
                            </FormField>
                        </div>
                        
                        <FormField name="goals" label="Sprint Goals">
                            <Textarea rows={3} placeholder="Key objectives for this sprint" />
                        </FormField>
                        
                        <FormField name="tasks" label="Assign Tasks">
                            <TaskSelector 
                                available={data.available_tasks}
                                selected={form.watch('tasks')}
                            />
                        </FormField>
                        
                        <DialogFooter>
                            <Button variant="ghost" onClick={onClose}>Cancel</Button>
                            <Button type="submit">
                                {mode === 'create' ? 'Create Sprint' : 'Update Sprint'}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
```

### 2. READ - Already Working ✅

### 3. UPDATE - Edit Sprint

#### Command: `/sprint-edit`
```php
class EditCommand extends BaseCommand {
    protected string $sprintCode;
    
    public function handle(): array {
        $sprint = Sprint::where('code', $this->sprintCode)->firstOrFail();
        
        return $this->respond([
            'component' => 'SprintFormModal',
            'mode' => 'edit',
            'data' => [
                'sprint' => $sprint->toArray(),
                'available_tasks' => $this->getUnassignedTasks(),
                'current_tasks' => $sprint->tasks->toArray(),
            ]
        ]);
    }
}
```

### 4. DELETE - Remove Sprint

#### Command: `/sprint-delete`
```php
class DeleteCommand extends BaseCommand {
    protected string $sprintCode;
    protected bool $cascade = false;
    
    public function handle(): array {
        $sprint = Sprint::where('code', $this->sprintCode)->firstOrFail();
        
        // Check for dependencies
        if ($sprint->tasks()->count() > 0 && !$this->cascade) {
            return $this->respond([
                'component' => 'ConfirmationModal',
                'data' => [
                    'title' => 'Sprint Has Tasks',
                    'message' => "This sprint contains {$sprint->tasks()->count()} tasks. What would you like to do?",
                    'options' => [
                        ['label' => 'Move to Backlog', 'action' => '/sprint-delete-move-backlog'],
                        ['label' => 'Delete Everything', 'action' => '/sprint-delete-cascade'],
                        ['label' => 'Cancel', 'action' => null]
                    ]
                ]
            ]);
        }
        
        $sprint->delete();
        
        return $this->respond([
            'message' => 'Sprint deleted successfully',
            'redirect' => '/sprints'
        ]);
    }
}
```

---

## Actions System Implementation

### Action Configuration Structure
```php
// database/migrations/add_actions_to_commands_table.php
Schema::table('commands', function (Blueprint $table) {
    $table->json('actions')->nullable()->after('navigation_config');
});

// Example actions configuration in commands table:
{
    "actions": [
        {
            "id": "activate",
            "label": "Activate",
            "icon": "play",
            "type": "single",
            "command": "/sprint-activate",
            "conditions": {
                "status": ["planned", "paused"]
            },
            "confirmation": false
        },
        {
            "id": "complete",
            "label": "Complete Sprint",
            "icon": "check-circle",
            "type": "single",
            "command": "/sprint-complete",
            "conditions": {
                "status": ["active"]
            },
            "confirmation": true,
            "confirmation_message": "Mark this sprint as completed?"
        },
        {
            "id": "delegate",
            "label": "Delegate Tasks",
            "icon": "users",
            "type": "single",
            "command": "/sprint-delegate",
            "modal": "DelegationModal"
        },
        {
            "id": "export",
            "label": "Export",
            "icon": "download",
            "type": "both",
            "command": "/sprint-export",
            "options": [
                {"value": "csv", "label": "CSV"},
                {"value": "json", "label": "JSON"},
                {"value": "pdf", "label": "PDF Report"}
            ]
        },
        {
            "id": "bulk-activate",
            "label": "Activate Selected",
            "icon": "play-circle",
            "type": "bulk",
            "command": "/sprints-bulk-activate",
            "min_selection": 1,
            "max_selection": 5
        }
    ]
}
```

### Action Commands Implementation

#### Sprint Activation
```php
namespace App\Commands\Orchestration\Sprint;

class ActivateCommand extends BaseCommand {
    protected string $sprintCode;
    
    public function handle(): array {
        $sprint = Sprint::where('code', $this->sprintCode)->firstOrFail();
        
        // Business logic validation
        if ($sprint->status === 'active') {
            return $this->error('Sprint is already active');
        }
        
        // Deactivate other active sprints (only one active at a time)
        Sprint::where('status', 'active')->update(['status' => 'paused']);
        
        // Activate this sprint
        $sprint->status = 'active';
        $sprint->activated_at = now();
        $sprint->save();
        
        // Create activity log
        Activity::log('sprint.activated', $sprint);
        
        return $this->respond([
            'message' => "Sprint {$sprint->code} activated",
            'sprint' => $sprint->fresh()->toArray(),
            'refresh' => true  // Tells frontend to refresh the list
        ]);
    }
}
```

#### Sprint Completion
```php
class CompleteCommand extends BaseCommand {
    protected string $sprintCode;
    
    public function handle(): array {
        $sprint = Sprint::where('code', $this->sprintCode)->firstOrFail();
        
        // Check incomplete tasks
        $incompleteTasks = $sprint->tasks()->whereNotIn('status', ['completed', 'cancelled'])->count();
        
        if ($incompleteTasks > 0) {
            return $this->respond([
                'component' => 'SprintCompletionModal',
                'data' => [
                    'sprint' => $sprint->toArray(),
                    'incomplete_tasks' => $incompleteTasks,
                    'options' => [
                        'move_to_next' => 'Move to next sprint',
                        'move_to_backlog' => 'Move to backlog',
                        'complete_anyway' => 'Complete anyway'
                    ]
                ]
            ]);
        }
        
        $sprint->complete();
        
        return $this->respond([
            'message' => 'Sprint completed successfully',
            'metrics' => $sprint->getCompletionMetrics()
        ]);
    }
}
```

#### Task Delegation
```php
class DelegateCommand extends BaseCommand {
    protected string $sprintCode;
    protected array $assignments; // ['task_code' => 'user_id']
    
    public function handle(): array {
        $sprint = Sprint::where('code', $this->sprintCode)->firstOrFail();
        
        if (empty($this->assignments)) {
            // Show delegation modal
            return $this->respond([
                'component' => 'DelegationModal',
                'data' => [
                    'sprint' => $sprint->toArray(),
                    'tasks' => $sprint->tasks->toArray(),
                    'team_members' => User::active()->get()->toArray()
                ]
            ]);
        }
        
        // Process assignments
        foreach ($this->assignments as $taskCode => $userId) {
            Task::where('code', $taskCode)
                ->update(['assigned_to' => $userId]);
        }
        
        return $this->respond([
            'message' => 'Tasks delegated successfully',
            'refresh' => true
        ]);
    }
}
```

#### Export Functionality
```php
class ExportCommand extends BaseCommand {
    protected string $sprintCode;
    protected string $format = 'csv';
    
    public function handle(): array {
        $sprint = Sprint::where('code', $this->sprintCode)
            ->with(['tasks', 'tasks.assignee'])
            ->firstOrFail();
        
        $exporter = match($this->format) {
            'csv' => new SprintCsvExporter($sprint),
            'json' => new SprintJsonExporter($sprint),
            'pdf' => new SprintPdfExporter($sprint),
            default => throw new \InvalidArgumentException("Unknown format: {$this->format}")
        };
        
        $file = $exporter->export();
        
        return $this->respond([
            'download' => [
                'url' => $file->url(),
                'filename' => $file->name(),
                'mime_type' => $file->mimeType()
            ]
        ]);
    }
}
```

### Frontend Action Integration

#### Action Buttons Component
```typescript
interface ActionButtonsProps {
    actions: Action[]
    item: any
    onAction: (action: Action, item: any) => void
}

export function ActionButtons({ actions, item, onAction }: ActionButtonsProps) {
    const evaluateConditions = (action: Action): boolean => {
        if (!action.conditions) return true
        
        return Object.entries(action.conditions).every(([field, values]) => {
            return values.includes(item[field])
        })
    }
    
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                    <MoreHorizontal className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {actions
                    .filter(a => a.type === 'single' && evaluateConditions(a))
                    .map(action => (
                        <DropdownMenuItem
                            key={action.id}
                            onClick={() => onAction(action, item)}
                        >
                            {action.icon && <Icon name={action.icon} />}
                            {action.label}
                        </DropdownMenuItem>
                    ))
                }
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
```

#### Bulk Actions Bar
```typescript
export function BulkActionsBar({ 
    actions, 
    selectedItems, 
    onAction 
}: BulkActionsBarProps) {
    const bulkActions = actions.filter(a => 
        a.type === 'bulk' || a.type === 'both'
    )
    
    if (selectedItems.length === 0) return null
    
    return (
        <div className="fixed bottom-4 left-1/2 -translate-x-1/2 
                        bg-gray-900 text-white p-4 rounded-lg 
                        shadow-lg flex items-center gap-4">
            <span>{selectedItems.length} selected</span>
            
            {bulkActions.map(action => (
                <Button
                    key={action.id}
                    variant="ghost"
                    size="sm"
                    onClick={() => onAction(action, selectedItems)}
                    disabled={
                        selectedItems.length < (action.min_selection || 1) ||
                        selectedItems.length > (action.max_selection || 999)
                    }
                >
                    {action.icon && <Icon name={action.icon} />}
                    {action.label}
                </Button>
            ))}
            
            <Button variant="ghost" size="sm" onClick={clearSelection}>
                Clear
            </Button>
        </div>
    )
}
```

---

## Validation & Business Rules

### Sprint Validation Rules
```php
class SprintValidator {
    public static function rules(): array {
        return [
            'code' => [
                'required',
                'unique:sprints,code',
                'regex:/^SPR-\d{4}-\d{2}$/'
            ],
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'in:planned,active,paused,completed,cancelled',
        ];
    }
    
    public static function businessRules(Sprint $sprint): array {
        $errors = [];
        
        // Only one active sprint
        if ($sprint->status === 'active') {
            $activeCount = Sprint::where('status', 'active')
                ->where('id', '!=', $sprint->id)
                ->count();
            
            if ($activeCount > 0) {
                $errors[] = 'Only one sprint can be active at a time';
            }
        }
        
        // Sprint duration limits
        $duration = $sprint->start_date->diffInDays($sprint->end_date);
        if ($duration < 7) {
            $errors[] = 'Sprint must be at least 7 days';
        }
        if ($duration > 30) {
            $errors[] = 'Sprint cannot exceed 30 days';
        }
        
        return $errors;
    }
}
```

---

## State Machine

### Sprint States & Transitions
```php
enum SprintStatus: string {
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

class SprintStateMachine {
    private static array $transitions = [
        'planned' => ['active', 'cancelled'],
        'active' => ['paused', 'completed', 'cancelled'],
        'paused' => ['active', 'completed', 'cancelled'],
        'completed' => [],  // Terminal state
        'cancelled' => [],  // Terminal state
    ];
    
    public static function canTransition(Sprint $sprint, string $newStatus): bool {
        return in_array(
            $newStatus, 
            self::$transitions[$sprint->status] ?? []
        );
    }
    
    public static function transition(Sprint $sprint, string $newStatus): void {
        if (!self::canTransition($sprint, $newStatus)) {
            throw new InvalidStateTransition(
                "Cannot transition from {$sprint->status} to {$newStatus}"
            );
        }
        
        $sprint->status = $newStatus;
        $sprint->{"transitioned_to_{$newStatus}_at"} = now();
        $sprint->save();
        
        event(new SprintStatusChanged($sprint, $newStatus));
    }
}
```

---

## Database Schema Updates

```php
// database/migrations/add_sprint_enhancements.php
Schema::table('sprints', function (Blueprint $table) {
    // State transition timestamps
    $table->timestamp('activated_at')->nullable();
    $table->timestamp('paused_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    
    // Metrics
    $table->integer('planned_points')->default(0);
    $table->integer('completed_points')->default(0);
    $table->integer('task_completion_rate')->default(0);
    
    // Team
    $table->foreignId('sprint_master_id')->nullable();
    $table->json('team_members')->nullable();
    
    // Goals & notes
    $table->text('goals')->nullable();
    $table->text('retrospective_notes')->nullable();
    
    // Indexes for performance
    $table->index(['status', 'start_date']);
    $table->index('code');
});
```

---

## Success Metrics

### Functional Requirements
- ✅ Full CRUD operations working
- ✅ 10+ configurable actions per entity
- ✅ State transitions with validation
- ✅ Bulk operations on 100+ items
- ✅ Export to 3+ formats
- ✅ Real-time updates via events

### Performance Requirements
- List loads in < 200ms
- Actions execute in < 500ms
- Bulk operations handle 1000+ items
- Export handles 10,000+ records

### User Experience
- Single-click access to common actions
- Keyboard shortcuts for power users
- Undo/redo for destructive operations
- Progress indicators for long operations
- Clear error messages with recovery options

---

## Implementation Priority

### Week 1: Core CRUD
1. Create form and validation
2. Edit capabilities
3. Delete with cascading options
4. Basic state transitions

### Week 2: Actions System
1. Action configuration schema
2. Single-item actions
3. Bulk operations
4. Export functionality

### Week 3: Polish & Advanced
1. Keyboard shortcuts
2. Undo/redo system
3. Advanced filtering
4. Performance optimization
5. Comprehensive testing