# Frontend Agent - Orchestration System Guide

Quick reference for managing sprints and tasks via CLI commands.

---

## 📋 Sprint Management

### Create/Update Sprint
```bash
# Create a new sprint
php artisan orchestration:sprint:save SPRINT-FE-1 \
  --title="Frontend UI Implementation" \
  --status="In Progress" \
  --priority="high" \
  --estimate="20-25 hours" \
  --starts_on="2025-10-11" \
  --ends_on="2025-10-18"

# Update existing sprint
php artisan orchestration:sprint:save SPRINT-FE-1 \
  --status="Completed" \
  --note="All tasks completed successfully"
```

### View Sprint Details
```bash
# View sprint with all tasks
php artisan orchestration:sprint:detail SPRINT-FE-1

# List all sprints
php artisan orchestration:sprints
```

### Update Sprint Status
```bash
# Change sprint status
php artisan orchestration:sprint:status SPRINT-FE-1 "In Progress" \
  --note="Started frontend work"
```

---

## 📝 Task/Work Item Management

### Create Task
```bash
# Create a new task
php artisan orchestration:task:save T-FE-01-BTN \
  --task_name="Create Button Component" \
  --description="Build reusable button component with variants" \
  --type="task" \
  --status="todo" \
  --priority="high" \
  --sprint_code="SPRINT-FE-1" \
  --estimated_hours=3 \
  --estimate_text="2-3 hours" \
  --tags="frontend,component,ui"
```

### Add Context to Task
```bash
# Add context/notes to existing task
php artisan orchestration:task:save T-FE-01-BTN \
  --agent_content="Implemented button with primary, secondary, and ghost variants. Added loading state and icon support. Tests passing." \
  --status="in_progress"
```

### Update Task Status
```bash
# Update task status
php artisan orchestration:task:status T-FE-01-BTN completed \
  --note="Button component complete with all variants and tests"

# Mark in progress
php artisan orchestration:task:status T-FE-01-BTN in_progress \
  --note="Started implementation"

# Block task
php artisan orchestration:task:status T-FE-01-BTN blocked \
  --note="Waiting for design review"
```

### View Task Details
```bash
# View single task
php artisan orchestration:task:detail T-FE-01-BTN

# List all tasks
php artisan orchestration:tasks

# Filter by sprint
php artisan orchestration:tasks --sprint=SPRINT-FE-1

# Filter by status
php artisan orchestration:tasks --status=in_progress --status=todo

# Search tasks
php artisan orchestration:tasks --search="button"
```

### Assign Task to Agent
```bash
# Assign task to yourself
php artisan orchestration:task:assign T-FE-01-BTN frontend-agent \
  --status=in_progress \
  --note="Starting work on button component"
```

---

## 🔄 Common Workflows

### Starting Work on a New Task
```bash
# 1. Create the task
php artisan orchestration:task:save T-FE-02-FORM \
  --task_name="Build Form Component" \
  --description="Create form wrapper with validation" \
  --sprint_code="SPRINT-FE-1" \
  --estimated_hours=4 \
  --priority="high"

# 2. Assign to yourself and mark in progress
php artisan orchestration:task:assign T-FE-02-FORM frontend-agent --status=in_progress

# 3. Add context as you work
php artisan orchestration:task:save T-FE-02-FORM \
  --agent_content="Created FormField wrapper. Integrated react-hook-form. Added validation display."
```

### Completing a Task
```bash
# 1. Add final context
php artisan orchestration:task:save T-FE-02-FORM \
  --agent_content="Form component complete. Supports text, email, password fields. Validation working. Tests added."

# 2. Mark complete
php artisan orchestration:task:status T-FE-02-FORM completed \
  --note="Form component finished with full validation support"
```

### Checking Your Work
```bash
# View all your in-progress tasks
php artisan orchestration:tasks --status=in_progress

# View sprint progress
php artisan orchestration:sprint:detail SPRINT-FE-1

# Search for specific tasks
php artisan orchestration:tasks --search="component"
```

### Creating Multiple Related Tasks
```bash
# Create parent task
php artisan orchestration:task:save T-FE-03-NAV \
  --task_name="Navigation System" \
  --description="Build complete navigation system" \
  --sprint_code="SPRINT-FE-1" \
  --estimated_hours=8

# Create subtasks (add parent reference in description/tags)
php artisan orchestration:task:save T-FE-03-NAV-A \
  --task_name="Navigation - Mobile Menu" \
  --description="Mobile responsive navigation menu (Part of T-FE-03-NAV)" \
  --sprint_code="SPRINT-FE-1" \
  --estimated_hours=3 \
  --tags="navigation,mobile,subtask"

php artisan orchestration:task:save T-FE-03-NAV-B \
  --task_name="Navigation - Breadcrumbs" \
  --description="Breadcrumb navigation component (Part of T-FE-03-NAV)" \
  --sprint_code="SPRINT-FE-1" \
  --estimated_hours=2 \
  --tags="navigation,breadcrumbs,subtask"
```

---

## 💡 Tips

**Task Naming Convention:**
- Use descriptive codes: `T-FE-01-BTN`, `T-FE-02-FORM`, etc.
- `FE` = Frontend, followed by sequential number and short identifier

**Status Values:**
- `todo` - Not started
- `in_progress` - Currently working on
- `blocked` - Blocked by something
- `completed` - Done
- `done` - Fully complete

**Priority Values:**
- `low`, `medium`, `high`, `critical`

**Task Types:**
- `task` - Standard work item
- `feature` - New feature
- `bug` - Bug fix
- `refactor` - Code refactoring

**Adding Context Frequently:**
Use `--agent_content` to document your progress. This helps with:
- Tracking what you've done
- Providing context for reviews
- Helping future work on related tasks

**Example of good context:**
```bash
php artisan orchestration:task:save T-FE-01-BTN \
  --agent_content="Created Button.tsx with variants (primary, secondary, ghost, danger). Added size prop (sm, md, lg). Implemented loading state with spinner. Added icon support (left/right). Created Button.test.tsx with 15 test cases. All tests passing. Added to Storybook."
```

---

## 🔍 Quick Reference

```bash
# Create sprint
orchestration:sprint:save SPRINT-XX --title="..." --status="..." --priority="..."

# Create task
orchestration:task:save T-XX-YYY --task_name="..." --sprint_code="..." --estimated_hours=X

# Update task context
orchestration:task:save T-XX-YYY --agent_content="..."

# Change task status
orchestration:task:status T-XX-YYY completed --note="..."

# View tasks
orchestration:tasks --sprint=SPRINT-XX --status=in_progress

# View sprint
orchestration:sprint:detail SPRINT-XX
```

---

**That's it!** Use these commands to manage your frontend work in the orchestration system.
