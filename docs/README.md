# Fragments Engine - Multi-Agent Delegation System

*The definitive guide to the Fragments Engine delegation and task management system*

## 🎯 System Overview

The Fragments Engine delegation system is designed for **efficient multi-agent collaboration** on complex software development tasks. It provides structured task management, specialized agent coordination, and concurrent development workflows.

### **Core Principles**
- **Specialized Expertise**: Role-based agents with domain-specific skills
- **Structured Tasks**: Comprehensive task packs with clear deliverables
- **Quality Focus**: Built-in quality gates and validation processes
- **Concurrent Safety**: Git worktree strategy for conflict-free development
- **Transparency**: Real-time progress tracking and status visibility

---

## 📁 System Architecture

```
delegation/
├── README.md                    # This file - system documentation
├── SPRINT_STATUS.md            # Live sprint tracking dashboard
├── PROJECT_MANAGER.md          # Agent coordination instructions
├── agents/                     # Agent management system
│   ├── templates/              # Role-based agent templates
│   │   ├── backend-engineer.md
│   │   ├── frontend-engineer.md
│   │   ├── ux-designer.md
│   │   ├── project-manager.md
│   │   └── qa-engineer.md
│   └── active/                 # Generated agent instances
│       ├── alice-backend-001.md
│       └── bob-frontend-002.md
├── sprint-46/                  # Active sprint task packs
├── sprint-43/                  # Upcoming sprint task packs
├── sprint-44/                  # Future sprint task packs
├── sprint-45/                  # Future sprint task packs
├── backlog/                    # Future enhancement tasks
└── archived/                   # Completed work and sprints
```

---

## 🤖 Agent System

### **Role-Based Agent Templates**

Our agent system uses **specialized templates** that provide domain expertise and project context:

#### **Backend Engineer** (`agents/templates/backend-engineer.md`)
- **Expertise**: Laravel 12, PHP 8.3, PostgreSQL, API development
- **Focus**: Database design, service layer, queue management, testing
- **Context**: Fragment system, AI provider abstraction, command architecture

#### **Frontend Engineer** (`agents/templates/frontend-engineer.md`)
- **Expertise**: React 18, TypeScript, Vite, shadcn/ui, Tailwind CSS v4
- **Focus**: Component development, state management, performance optimization
- **Context**: React islands, TipTap editor, chat interface, command palette

#### **UX Designer** (`agents/templates/ux-designer.md`)
- **Expertise**: Interface design, accessibility, responsive design, user research
- **Focus**: User experience, design systems, interaction patterns
- **Context**: shadcn/ui design system, mobile-first approach, accessibility standards

#### **Project Manager** (`agents/templates/project-manager.md`)
- **Expertise**: Task coordination, dependency management, quality oversight
- **Focus**: Sprint planning, agent coordination, risk mitigation
- **Context**: Multi-sprint coordination, cross-functional team management

#### **QA Engineer** (`agents/templates/qa-engineer.md`)
- **Expertise**: Pest testing, Vitest, Playwright, performance testing
- **Focus**: Test automation, quality validation, regression testing
- **Context**: Laravel testing patterns, React component testing, E2E workflows

### **Agent Creation Process**

Agents are created from templates with specific context and mission:

```bash
# Create specialized agent from template
/agent-create backend-engineer alice

# Generated: delegation/agents/active/alice-backend-eng-001.md
# Includes: Template expertise + project context + assigned mission
```

---

## 📋 Task Pack System

### **Task Pack Structure**

Each task has a dedicated folder with comprehensive documentation:

```
sprint-XX/TASK-ID/
├── AGENT.md      # Agent profile, mission, and success criteria
├── CONTEXT.md    # Technical context and integration points  
├── PLAN.md       # Phase breakdown with time estimates
└── TODO.md       # Granular implementation checklist
```

### **Task Pack Components**

#### **AGENT.md** - Agent Profile & Mission
- Specialized agent expertise required
- Mission statement and key objectives
- Success metrics and quality standards
- Communication style and workflow preferences

#### **CONTEXT.md** - Technical Integration
- Existing codebase integration points
- Technology stack requirements
- API contracts and data models
- Dependencies and constraints

#### **PLAN.md** - Implementation Strategy  
- Phase breakdown with time estimates
- Dependency mapping and critical path
- Risk assessment and mitigation strategies
- Milestone definitions and acceptance criteria

#### **TODO.md** - Implementation Checklist
- Granular task breakdowns
- Step-by-step implementation guide
- Quality checkpoints and validation steps
- Testing requirements and success criteria

---

## 🚀 Sprint Management

### **Sprint Status Tracking**

Live sprint tracking is maintained in `SPRINT_STATUS.md`:
- **Current sprint progress** with task status and agent assignments
- **Upcoming sprint queue** with priority and dependencies
- **Risk indicators** and resource conflict identification
- **Progress metrics** and completion targets

### **Sprint Lifecycle**

1. **Planning**: Task analysis, dependency mapping, agent assignment
2. **Execution**: Parallel development with git worktree isolation
3. **Integration**: Merge coordination and conflict resolution
4. **Validation**: Quality assurance and user acceptance testing
5. **Completion**: Sprint review and next sprint preparation

### **Priority System**

- **CRITICAL**: Sprint 46 - Command system unification (architectural foundation)
- **HIGH**: Sprint 43 - Enhanced UX and system management
- **MEDIUM**: Sprint 44 - Advanced transclusion features  
- **LOWER**: Sprint 45 - Provider management UI improvements

---

## 🔧 Custom Commands

### **Sprint Management Commands**

```bash
# Sprint Operations
/sprint-status                  # View current sprint dashboard
/sprint-analyze <sprint-number> # Analyze sprint readiness and dependencies
/sprint-start <sprint-number>   # Initialize sprint with git strategy
/sprint-review <sprint-number>  # Review completion and quality

# Task Operations
/task-analyze <task-id>         # Deep analysis of specific task
/task-execute <task-id>         # Execute task with assigned agent
/task-complete <task-id>        # Mark task complete and update tracking
/task-validate <task-id>        # Run validation checks on task output

# Agent Management
/agent-create <role> <name>     # Create specialized agent from template
/agent-assign <agent-id> <task-id>  # Assign agent to specific task
/agent-status <agent-id>        # Check agent progress and blockers
/agent-handoff <from> <to> <task>   # Transfer task ownership

# Development Workflow
/worktree-setup <sprint-number> # Create isolated work environments
/conflict-check                 # Identify potential file conflicts
/integration-check             # Check merge compatibility
```

### **Quality Assurance Commands**

```bash
# Quality Gates
/quality-check <task-id>        # Run comprehensive quality validation
/performance-test <task-id>     # Execute performance benchmarks
/security-review <task-id>      # Security validation for credential handling
/accessibility-test <task-id>   # WCAG compliance validation

# Reporting
/sprint-report <sprint-number>  # Generate comprehensive progress report
/quality-metrics               # Project-wide quality dashboard
/delegation-export             # Export status for external tools
```

---

## 🌳 Concurrent Development Strategy

### **Git Worktree Approach** (Recommended)

**Why Git Worktree?**
- **True isolation**: Each agent works in separate file system directory
- **No merge conflicts**: Concurrent development without interference
- **Preserved state**: Independent working directories with full git history
- **Easy integration**: Clean merge from isolated environments

**Worktree Setup**:
```bash
# Sprint initialization
git worktree add ../seer-sprint-46 sprint-46/main
git worktree add ../seer-backend-work sprint-46/backend  
git worktree add ../seer-frontend-work sprint-46/frontend

# Agent isolation
# Alice (Backend): works in ../seer-backend-work
# Bob (Frontend): works in ../seer-frontend-work
# Integration: coordinated merge in main repository
```

**Directory Structure**:
```
/Users/chrispian/Projects/
├── seer/                    # Main repository
├── seer-backend-work/       # Backend agent workspace  
├── seer-frontend-work/      # Frontend agent workspace
└── seer-integration/        # Integration testing workspace
```

### **Alternative: Feature Branch Strategy**

When worktrees aren't feasible:
- **Module boundaries**: Strict backend vs frontend separation
- **Conflict detection**: Automated conflict checking before work assignment
- **Sequential integration**: Continuous validation with merge coordination

---

## 📊 Quality Standards

### **Code Quality Requirements**

- **PSR-12 Compliance**: All PHP code follows PSR-12 standards
- **Type Safety**: Comprehensive TypeScript types and PHP type declarations
- **Performance**: No regressions, optimization where possible
- **Security**: Credential protection and authentication flow validation
- **Accessibility**: WCAG 2.1 AA compliance for all UI components

### **Testing Requirements**

- **Backend**: Pest framework with feature and unit tests
- **Frontend**: Vitest for components, Playwright for E2E testing
- **Coverage**: Minimum 80% coverage for new features
- **Performance**: Benchmark validation for critical paths
- **Integration**: Cross-component workflow validation

### **Documentation Standards**

- **Code Documentation**: Comprehensive docblocks and inline comments
- **API Documentation**: Clear endpoint specifications and examples
- **User Documentation**: Updated help system and user guides
- **Architecture Documentation**: Decision records and pattern documentation

---

## 🔄 Workflow Examples

### **Starting a New Sprint**

```bash
# 1. Analyze sprint readiness
/sprint-analyze 46

# 2. Set up concurrent development environment
/worktree-setup 46

# 3. Create specialized agents
/agent-create backend-engineer alice
/agent-create frontend-engineer bob

# 4. Assign tasks based on expertise
/agent-assign alice ENG-08-01-command-architecture-analysis
/agent-assign bob UX-04-01-todo-management-modal

# 5. Start sprint execution
/sprint-start 46
```

### **Executing a Task**

```bash
# 1. Analyze task requirements
/task-analyze ENG-08-01-command-architecture-analysis

# 2. Execute task with assigned agent
/task-execute ENG-08-01

# 3. Monitor progress and validate quality
/quality-check ENG-08-01

# 4. Complete task and update tracking
/task-complete ENG-08-01
```

### **Managing Dependencies**

```bash
# 1. Check cross-task dependencies
/dependency-check ENG-08-02

# 2. Identify potential conflicts
/conflict-check

# 3. Coordinate integration
/integration-check ENG-08-01 ENG-08-02

# 4. Merge coordination
/merge-coordinate sprint-46
```

---

## 🚨 Risk Management

### **Common Risks & Mitigation**

#### **Resource Conflicts**
- **Risk**: Multiple agents needing same expertise
- **Mitigation**: Staggered task execution, agent specialization

#### **Merge Conflicts**  
- **Risk**: Concurrent work on overlapping files
- **Mitigation**: Git worktree isolation, module boundaries

#### **Quality Regressions**
- **Risk**: New features breaking existing functionality
- **Mitigation**: Comprehensive testing, quality gates

#### **Scope Creep**
- **Risk**: Tasks expanding beyond defined boundaries
- **Mitigation**: Clear acceptance criteria, regular reviews

### **Escalation Procedures**

#### **Technical Blockers**
- External dependency conflicts
- Performance bottlenecks requiring architectural changes
- Security vulnerabilities requiring immediate attention

#### **User Decision Required**
- Breaking changes affecting user workflows
- Major architectural decisions impacting multiple systems
- Feature scope changes affecting timeline or budget

---

## 📈 Success Metrics

### **Sprint Metrics**
- **On-Time Delivery**: Sprints completed within estimated timeframes
- **Quality Standards**: All deliverables meet quality gates
- **Team Efficiency**: Optimal agent utilization and minimal blockers

### **Quality Metrics**
- **Test Coverage**: Comprehensive coverage of new features
- **Performance**: No regressions, optimization improvements
- **User Experience**: Enhanced workflows without disrupting existing functionality

### **Process Metrics**
- **Coordination Efficiency**: Minimal agent conflicts and dependencies
- **Communication Quality**: Clear status updates and issue resolution
- **Continuous Improvement**: Regular process optimization and learning

---

## 🎯 Getting Started

### **For Project Managers**
1. Review `SPRINT_STATUS.md` for current sprint status
2. Identify highest priority tasks requiring delegation
3. Create specialized agents using `/agent-create` commands
4. Assign tasks based on agent expertise and availability
5. Monitor progress and coordinate cross-task dependencies

### **For Specialized Agents**
1. Read your assigned task pack (`AGENT.md`, `CONTEXT.md`, `PLAN.md`, `TODO.md`)
2. Understand project context and integration requirements
3. Follow established patterns and quality standards
4. Provide regular progress updates and escalate blockers
5. Complete comprehensive testing and documentation

### **For Quality Assurance**
1. Review task requirements and acceptance criteria
2. Develop test strategies and validation approaches
3. Execute comprehensive testing across all quality dimensions
4. Coordinate with development agents for issue resolution
5. Validate final deliverables meet all quality standards

---

## 📚 Additional Resources

### **Key Documentation**
- `CLAUDE.md` - Development guidelines and conventions
- `PROJECT_MANAGER.md` - Agent coordination instructions  
- `SPRINT_STATUS.md` - Live sprint tracking dashboard

### **Technology References**
- **Laravel**: Framework documentation and best practices
- **React**: Component patterns and performance optimization
- **shadcn/ui**: Design system components and customization
- **Pest**: Testing framework and patterns

### **Quality Standards**
- **PSR-12**: PHP coding standards
- **WCAG 2.1**: Accessibility guidelines
- **TypeScript**: Type safety and documentation patterns

---

*This system is designed for maximum efficiency and quality in multi-agent software development. Questions? See `PROJECT_MANAGER.md` for coordination support.*