# Orchestration System Details

## Overview
The orchestration system manages tasks, sprints, and agent assignments through a Laravel backend with rich UI components. Tasks are stored as `WorkItem` models with structured content fields.

## Task Structure
Tasks have these content sections:
- **context_content**: Problem definition and background
- **plan_content**: Implementation strategy and phases  
- **todo_content**: Detailed checklist items
- **summary_content**: Brief description
- **agent_content**: Agent specialization notes

## Metadata Fields
Tasks require specific metadata for proper display:
```php
'metadata' => [
    'task_name' => 'Human-readable title',
    'task_code' => 'UNIQUE-CODE-001', 
    'description' => 'Brief summary',
    'estimate_text' => '2-3 days'
]
```

## Creating Tasks via CLI

### Method 1: CLI Command (Recommended)
```bash
php artisan frag:command "/task-create 'Fix User Login Bug' --priority=high --estimate='2 days'"
php artisan frag:command "/task-create 'Implement Dark Mode' --priority=medium --estimate='3-4 days'"
php artisan frag:command "/task-create 'Update Documentation' --priority=low --estimate='1 day'"
```

**Options:**
- `--priority=low|medium|high` (default: medium)
- `--estimate="X days"` (default: "2-3 days")
- `--type=task` (default: task)

**Auto-generated features:**
- Task codes based on title keywords (BUG-001, FEAT-002, etc.)
- Estimated hours from estimate text
- Basic content structure for context/plan/todo

### Method 2: Direct Database (Advanced)
```bash
php artisan tinker --execute "
\$task = new App\Models\WorkItem();
\$task->type = 'task';
\$task->status = 'backlog';
\$task->priority = 'medium'; // low, medium, high
\$task->estimated_hours = 16;
\$task->metadata = [
    'task_name' => 'Your Task Title',
    'task_code' => 'UNIQUE-001',
    'description' => 'Brief description',
    'estimate_text' => '2-3 days'
];
\$task->context_content = 'Problem and background...';
\$task->plan_content = 'Implementation strategy...';
\$task->todo_content = '- [ ] Step 1\n- [ ] Step 2...';
\$task->summary_content = 'Brief summary...';
\$task->save();
echo 'Task created: ' . \$task->id;
"
```

### Method 2: Using Eloquent in Code
```php
use App\Models\WorkItem;

$task = WorkItem::create([
    'type' => 'task',
    'status' => 'backlog',
    'priority' => 'high',
    'estimated_hours' => 24,
    'metadata' => [
        'task_name' => 'Fix Critical Bug',
        'task_code' => 'BUG-001',
        'description' => 'Address critical system issue',
        'estimate_text' => '3-4 days'
    ],
    'context_content' => '...',
    'plan_content' => '...',
    'todo_content' => '...',
    'summary_content' => '...'
]);
```

## Task Status Values
- `backlog` - Not yet started
- `todo` - Ready to start  
- `in_progress` - Currently being worked
- `done` - Completed
- `blocked` - Cannot proceed

## Priority Values
- `low` - Nice to have
- `medium` - Important 
- `high` - Critical/urgent

## Viewing Tasks

### List Commands
- `php artisan frag:command "/tasks"` - All tasks with status-based sorting
- `php artisan frag:command "/backlog-list"` - Only backlog items
- `php artisan frag:command "/sprints"` - All sprints with progress

### Detail Commands  
- `php artisan frag:command "/task-detail <id>"` - Full task view with tabs
- `php artisan frag:command "/sprint-detail <code>"` - Sprint breakdown with tasks

### Example Usage
```bash
php artisan frag:command "/task-create 'Fix Authentication Bug' --priority=high --estimate='1-2 days'"
php artisan frag:command "/backlog-list"
php artisan frag:command "/task-detail BUG-001"
php artisan frag:command "/task-assign BUG-001 backend-engineer"
```

## Agent Assignment
Agents are specialized roles for different task types:
- `migration-specialist` - Command/system migrations
- `ui-developer` - Frontend components
- `backend-engineer` - API and database work
- `qa-specialist` - Testing and validation

## Best Practices

### Task Naming
- Use descriptive, action-oriented titles
- Start with verb: "Fix", "Implement", "Upgrade"
- Be specific: "Fix User Authentication Bug" not "Auth Issue"

### Task Codes
- Format: `TYPE-###` (e.g., `BUG-001`, `FEAT-025`)
- Keep sequential within type
- Use consistent prefixes

### Content Structure
- **Context**: What's the problem/opportunity?
- **Plan**: How will we solve it?
- **Todo**: What specific steps are needed?
- **Summary**: One-line description

### Estimation
- Use realistic time estimates
- Include testing and documentation time
- Common ranges: "1-2 days", "3-5 days", "1-2 weeks"

## UI Features
All commands open rich UI modals with:
- Sortable/filterable tables
- Progress tracking
- Content tabs for detailed views
- Action dropdowns for task management

The system provides a modern, interactive experience for task and project management.