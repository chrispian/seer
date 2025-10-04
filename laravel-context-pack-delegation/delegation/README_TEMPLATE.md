# {PROJECT_NAME} - Delegation System

## Overview

This delegation system enables coordinated multi-agent development for the {PROJECT_NAME} project. It provides structured task management, agent specialization, and parallel development workflows.

## Quick Start

### 1. Agent Creation
Use the available agent templates to create specialized agents:

```bash
# Available agent types:
- backend-engineer      # {BACKEND_FOCUS_DESCRIPTION}
- frontend-engineer     # {FRONTEND_FOCUS_DESCRIPTION}  
- project-manager       # Coordination, task management, quality oversight
- qa-engineer          # Testing, validation, quality assurance
- ux-designer          # User experience, interface design, usability
```

### 2. Sprint Structure
Development is organized into focused sprints:

```
sprint-001/   # {SPRINT_001_DESCRIPTION}
sprint-002/   # {SPRINT_002_DESCRIPTION}
sprint-003/   # {SPRINT_003_DESCRIPTION}
sprint-004/   # {SPRINT_004_DESCRIPTION}
sprint-005/   # {SPRINT_005_DESCRIPTION}
```

### 3. Task Pack Structure
Each task follows a standardized structure:

```
{TASK-ID}/
├── AGENT.md      # Specialized agent profile with mission and context
├── CONTEXT.md    # Technical context and integration requirements
├── PLAN.md       # Implementation phases with time estimates
└── TODO.md       # Granular checklist for execution
```

## Agent Specializations

### Backend Engineer
**Focus**: {BACKEND_SPECIALIZATION_FOCUS}
- {BACKEND_SKILL_1}
- {BACKEND_SKILL_2}
- {BACKEND_SKILL_3}
- {BACKEND_SKILL_4}
- {BACKEND_SKILL_5}

### Frontend Engineer  
**Focus**: {FRONTEND_SPECIALIZATION_FOCUS}
- {FRONTEND_SKILL_1}
- {FRONTEND_SKILL_2}
- {FRONTEND_SKILL_3}
- {FRONTEND_SKILL_4}
- {FRONTEND_SKILL_5}

### Project Manager
**Focus**: Coordination, delegation, quality oversight
- Task prioritization and sprint management
- Agent coordination and dependency tracking
- Quality gate enforcement
- Progress monitoring and reporting
- Risk identification and mitigation

### QA Engineer
**Focus**: Testing, validation, quality assurance
- {QA_SKILL_1}
- {QA_SKILL_2}
- {QA_SKILL_3}
- {QA_SKILL_4}
- {QA_SKILL_5}

### UX Designer
**Focus**: User experience, interface design, usability
- {UX_SKILL_1}
- {UX_SKILL_2}
- {UX_SKILL_3}
- {UX_SKILL_4}
- {UX_SKILL_5}

## Development Workflows

### Parallel Development with Git Worktrees
Set up isolated development environments:

```bash
# Create worktrees for Sprint 001
./delegation/setup-worktree.sh 001

# Results in:
{PROJECT_NAME}-backend-sprint001/     # Backend development
{PROJECT_NAME}-frontend-sprint001/    # Frontend development  
{PROJECT_NAME}-integration-sprint001/ # Integration and testing

# Cleanup when done
./delegation/cleanup-worktree.sh 001
```

### Task Delegation Process
1. **Task Analysis**: Review requirements and create task pack
2. **Agent Selection**: Choose appropriate agent specialization
3. **Context Setup**: Provide agent with complete task pack
4. **Execution**: Agent follows structured implementation plan
5. **Integration**: Coordinate results with other agents
6. **Quality Review**: Validate against established standards

### MCP Integration
Use MCP commands for system management:

```bash
# Sprint status and management
/delegation-system sprint/status
/delegation-system sprint/analyze sprint=001

# Agent management
/delegation-system agent/create role=backend-engineer name=alice
/delegation-system agent/assign agent=alice-backendengineer-001 task=ENG-001

# Task analysis and tracking
/delegation-system task/analyze task=ENG-001-{EXAMPLE_TASK}
/delegation-system worktree/setup sprint=001
```

## Quality Standards

### {PROJECT_TYPE} Standards
- **{STANDARD_1}**: {STANDARD_1_DESCRIPTION}
- **{STANDARD_2}**: {STANDARD_2_DESCRIPTION}
- **{STANDARD_3}**: {STANDARD_3_DESCRIPTION}
- **{STANDARD_4}**: {STANDARD_4_DESCRIPTION}
- **{STANDARD_5}**: {STANDARD_5_DESCRIPTION}

### Code Quality Gates
- [ ] {QUALITY_GATE_1}
- [ ] {QUALITY_GATE_2}
- [ ] {QUALITY_GATE_3}
- [ ] {QUALITY_GATE_4}
- [ ] {QUALITY_GATE_5}
- [ ] {QUALITY_GATE_6}

### Integration Requirements
- [ ] {INTEGRATION_REQ_1}
- [ ] {INTEGRATION_REQ_2}
- [ ] {INTEGRATION_REQ_3}
- [ ] {INTEGRATION_REQ_4}
- [ ] {INTEGRATION_REQ_5}
- [ ] {INTEGRATION_REQ_6}

## Sprint Planning

### Sprint 001: {SPRINT_001_TITLE}
**Duration**: {SPRINT_001_DURATION}  
**Focus**: {SPRINT_001_FOCUS}

**Key Deliverables**:
- {SPRINT_001_DELIVERABLE_1}
- {SPRINT_001_DELIVERABLE_2}
- {SPRINT_001_DELIVERABLE_3}
- {SPRINT_001_DELIVERABLE_4}
- {SPRINT_001_DELIVERABLE_5}

### Sprint 002: {SPRINT_002_TITLE}
**Duration**: {SPRINT_002_DURATION}  
**Focus**: {SPRINT_002_FOCUS}

**Key Deliverables**:
- {SPRINT_002_DELIVERABLE_1}
- {SPRINT_002_DELIVERABLE_2}
- {SPRINT_002_DELIVERABLE_3}
- {SPRINT_002_DELIVERABLE_4}
- {SPRINT_002_DELIVERABLE_5}

### Sprint 003: {SPRINT_003_TITLE}
**Duration**: {SPRINT_003_DURATION}  
**Focus**: {SPRINT_003_FOCUS}

**Key Deliverables**:
- {SPRINT_003_DELIVERABLE_1}
- {SPRINT_003_DELIVERABLE_2}
- {SPRINT_003_DELIVERABLE_3}
- {SPRINT_003_DELIVERABLE_4}
- {SPRINT_003_DELIVERABLE_5}

### Sprint 004: {SPRINT_004_TITLE}
**Duration**: {SPRINT_004_DURATION}  
**Focus**: {SPRINT_004_FOCUS}

**Key Deliverables**:
- {SPRINT_004_DELIVERABLE_1}
- {SPRINT_004_DELIVERABLE_2}
- {SPRINT_004_DELIVERABLE_3}
- {SPRINT_004_DELIVERABLE_4}
- {SPRINT_004_DELIVERABLE_5}

### Sprint 005: {SPRINT_005_TITLE}
**Duration**: {SPRINT_005_DURATION}  
**Focus**: {SPRINT_005_FOCUS}

**Key Deliverables**:
- {SPRINT_005_DELIVERABLE_1}
- {SPRINT_005_DELIVERABLE_2}
- {SPRINT_005_DELIVERABLE_3}
- {SPRINT_005_DELIVERABLE_4}
- {SPRINT_005_DELIVERABLE_5}

## File Organization

```
delegation/
├── agents/
│   ├── templates/          # Agent role templates
│   └── active/            # Generated agent instances
├── sprint-001/            # {SPRINT_001_TITLE}
├── sprint-002/            # {SPRINT_002_TITLE}
├── sprint-003/            # {SPRINT_003_TITLE}
├── sprint-004/            # {SPRINT_004_TITLE}
├── sprint-005/            # {SPRINT_005_TITLE}
├── archived/              # Completed sprints and tasks
├── backlog/              # Future tasks and ideas
├── setup-worktree.sh     # Worktree management script
├── cleanup-worktree.sh   # Worktree cleanup script
├── README.md             # This file
└── SPRINT_STATUS.md      # Live sprint tracking dashboard
```

## Getting Started

1. **Review Project Context**: Understand {PROJECT_NAME} goals and architecture
2. **Select Sprint**: Choose appropriate sprint based on current priorities
3. **Create Agent**: Use agent template to create specialized agent
4. **Setup Environment**: Use worktree script for isolated development
5. **Execute Tasks**: Follow task pack structure for systematic implementation
6. **Coordinate Integration**: Work with project manager for integration
7. **Validate Quality**: Ensure all quality gates are met before completion

## Support and Documentation

- **Agent Templates**: `delegation/agents/templates/`
- **Sprint Planning**: `delegation/SPRINT_STATUS.md`
- **MCP Commands**: Use `/delegation-system` commands for system management
- **Quality Standards**: See individual sprint documentation for specific requirements

This delegation system ensures high-quality, coordinated development while maintaining {PROJECT_TYPE} standards and best practices.

---

**Template Instructions for Agents**:
When using this template, replace all `{PLACEHOLDER}` values with project-specific information:

- `{PROJECT_NAME}` - The actual project name
- `{PROJECT_TYPE}` - Type of project (e.g., "Laravel Package", "React Application", "API Service")
- `{BACKEND_FOCUS_DESCRIPTION}` - Backend technology focus
- `{FRONTEND_FOCUS_DESCRIPTION}` - Frontend technology focus
- `{SPRINT_XXX_TITLE}` - Actual sprint names and descriptions
- `{SKILL_X}` - Specific skills for each role
- `{QUALITY_GATE_X}` - Actual quality requirements
- `{INTEGRATION_REQ_X}` - Real integration requirements

Remove this template instructions section when creating the actual README.md.