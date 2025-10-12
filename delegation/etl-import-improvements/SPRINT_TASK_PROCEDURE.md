# Sprint & Task Creation Procedure for ETL Import Improvements

## Overview
This document defines the standardized procedure for breaking down project areas into sprints and tasks using the orchestration system.

## Data Structure & Field Usage

### Sprint Structure
```php
[
    'code' => 'SPRINT-ETL-{N}',  // Sequential numbering
    'title' => 'Short descriptive title',
    'status' => 'Planned',
    'priority' => 'high|medium|low',
    'estimate' => 'X-Y hours',
    'starts_on' => 'YYYY-MM-DD',
    'ends_on' => 'YYYY-MM-DD',
    'meta' => [
        'summary' => '2-3 sentence overview of sprint goals',
        'detailed_summary' => 'Comprehensive description of what will be accomplished',
        'context' => 'Background information, dependencies, and prerequisites',
        'success_criteria' => 'Measurable outcomes that define sprint completion',
        'area' => 'Which project area (1-4) this sprint addresses',
    ]
]
```

### Task Structure
```php
[
    'task_code' => 'T-ETL-{NN}-{DESC}',  // ETL prefix, sequential number, descriptor
    'task_name' => 'Clear action-oriented title',
    'status' => 'todo',
    'delegation_status' => 'unassigned',
    'priority' => 'high|medium|low',
    'estimate_text' => 'X-Y hours',
    'metadata' => [
        'sprint_code' => 'SPRINT-ETL-{N}',
        'dependencies' => ['T-ETL-XX-YYY'],
        'tags' => ['backend', 'architecture', 'implementation'],
    ],
    'agent_content' => 'Agent INIT instructions (see below)',
    'plan_content' => 'Detailed implementation plan',
    'context_content' => 'Background and setup information',
    'summary_content' => '2-3 sentence task description',
]
```

## Agent INIT Structure

Each task's `agent_content` field should contain:

```markdown
# Agent INIT

## Role
You are a senior backend engineer specializing in [specific area].

## Task
[Clear, specific description of what needs to be accomplished]

## Context
- Working in Laravel 12 with PostgreSQL
- Part of ETL Import Improvements project
- [Any specific context for this task]

## Success Criteria
- [ ] Specific deliverable 1
- [ ] Specific deliverable 2
- [ ] Tests written and passing
- [ ] Code follows project conventions

## Resources
- Related documentation: [paths]
- Existing code to reference: [paths]
- Design patterns to follow: [descriptions]

## Constraints
- Must maintain backward compatibility
- Follow PSR-12 coding standards
- Use existing Fragment model where appropriate
```

## Sprint Planning Process

### Phase 1: Area Analysis
1. Review area documentation (01-04 files)
2. Identify logical work groupings
3. Estimate complexity and dependencies
4. Define 2-week sprint boundaries

### Phase 2: Sprint Creation
For each sprint:
1. Create sprint with descriptive code and title
2. Add comprehensive meta information
3. Set realistic time estimates
4. Define clear success criteria

### Phase 3: Task Breakdown
For each sprint:
1. Break work into 2-8 hour tasks
2. Identify task dependencies
3. Create clear agent instructions
4. Add implementation plans
5. Include all necessary context

### Phase 4: Dependency Management
1. Map task dependencies within sprint
2. Identify cross-sprint dependencies
3. Order tasks by dependency chain
4. Note blocking relationships

## Naming Conventions

### Sprint Codes
- Format: `SPRINT-ETL-{N}`
- Examples:
  - `SPRINT-ETL-1` - Import Contracts Foundation
  - `SPRINT-ETL-2` - Media Adapters Implementation
  - `SPRINT-ETL-3` - Document Sync Foundation

### Task Codes
- Format: `T-ETL-{NN}-{DESCRIPTOR}`
- Number: Sequential across entire project
- Descriptor: 3-10 characters describing task
- Examples:
  - `T-ETL-01-CONTRACTS` - Create base import contracts
  - `T-ETL-02-TRAITS` - Implement rate limiting trait
  - `T-ETL-03-CHECKSUM` - Build checksum service

## Content Guidelines

### Summary Content (2-3 sentences)
- What: Clear description of deliverable
- Why: Business value or technical necessity
- Scope: Boundaries of the work

### Plan Content (Detailed)
```markdown
## Implementation Plan

### Step 1: [Action]
- Specific sub-task
- Expected outcome

### Step 2: [Action]
- Specific sub-task
- Expected outcome

### Testing
- Unit tests required
- Integration tests needed

### Validation
- How to verify completion
```

### Context Content
```markdown
## Background
[Why this task exists]

## Current State
[What exists now]

## Related Work
- Previous tasks: [codes]
- Related PRs: [links]
- Documentation: [paths]

## Technical Considerations
- [Key technical points]
- [Potential challenges]
```

## Sprint Sequencing Strategy

### Sprint 1-2: Foundation (Area 1)
- Core contracts and interfaces
- Base traits and services
- Hardcover adapter migration
- Letterboxd adapter creation

### Sprint 3-4: Document Management (Area 2)
- Version preservation system
- Content routing engine
- Sync command implementation
- Relationship mapping

### Sprint 5-6: Storage Enhancement (Area 3)
- CAS implementation
- Upload pipeline
- Media processing
- Artifact migration

### Sprint 7-8: Intelligence Layer (Area 4)
- Metrics extraction
- Pattern analysis
- Dashboard creation
- Alert system

## Quality Checklist

### For Each Sprint
- [ ] Clear, measurable goals
- [ ] Realistic time estimates
- [ ] All tasks created
- [ ] Dependencies mapped
- [ ] Meta information complete

### For Each Task
- [ ] Agent INIT provided
- [ ] Implementation plan detailed
- [ ] Context documented
- [ ] Dependencies identified
- [ ] Estimate provided
- [ ] Success criteria defined

## Orchestration Commands

### Sprint Creation
```bash
php artisan orchestration:sprint:save SPRINT-ETL-1 \
  --title "Import Contracts Foundation" \
  --status "Planned" \
  --priority high \
  --estimate "20-25 hours"
```

### Task Creation (via MCP tool)
```
orchestration_orchestration_tasks_save(
  task_code="T-ETL-01-CONTRACTS",
  task_name="Create base import contracts",
  status="todo",
  sprint_code="SPRINT-ETL-1",
  agent_content="...",
  plan_content="...",
  context_content="..."
)
```

### Sprint-Task Association
```bash
php artisan orchestration:sprint:tasks:attach SPRINT-ETL-1 task-uuid-1 task-uuid-2
```

## Success Metrics

### Sprint Level
- All tasks completed within estimate
- No critical bugs introduced
- Documentation updated
- Tests passing

### Task Level
- Implementation matches plan
- Code review passed
- Tests written and passing
- Documentation updated

## Notes for Engineers

1. **Task Sizing**: Keep tasks between 2-8 hours. Larger work should be split.
2. **Dependencies**: Always check and note dependencies before starting.
3. **Context Preservation**: Update task content fields as you work.
4. **Communication**: Use task activities for progress updates.
5. **Testing**: Every task must include appropriate tests.

## Review Process

1. Sprint created with all tasks → PM review
2. Task implementation → Code review
3. Sprint completion → Retrospective
4. Learnings documented → Process improvement

This procedure ensures consistent, well-documented sprint and task creation that provides engineers with everything needed to execute successfully.