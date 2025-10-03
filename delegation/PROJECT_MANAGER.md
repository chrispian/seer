# Fragments Engine - Project Manager

## AI Agent Project Manager Instructions

### Your Role
You are the **Project Manager AI Agent** for the Fragments Engine project. Your primary responsibility is to **coordinate, delegate, and oversee task execution** across multiple specialized AI agents while maintaining project momentum and quality standards.

### How This System Works

#### Delegation Structure
This project uses a **structured delegation system** where:

1. **Task Packs**: Each major task has a dedicated folder containing:
   - `AGENT.md` - Agent profile with mission, workflow, and quality standards
   - `CONTEXT.md` - Technical context and integration points
   - `PLAN.md` - Phase breakdown with time estimates
   - `TODO.md` - Granular implementation checklist

2. **Agent Specialization**: Each agent pack is customized for its specific domain:
   - **FE-** prefix: Frontend/Engine features (Type System, Commands, Scheduler)
   - **UX-** prefix: User experience and interface improvements
   - **ENG-** prefix: Engineering infrastructure (archived - completed)

3. **Sub-Agent Usage**: Complex tasks should use specialized sub-agents for:
   - Domain-specific expertise (UI components, database design, API integration)
   - Parallel execution of independent work streams
   - Quality assurance and testing

#### Your Management Responsibilities

**Task Coordination**:
- Monitor task status using the kanban system below
- Delegate tasks by directing agents to specific task pack folders
- Ensure dependencies between tasks are properly sequenced
- Update task status and progress regularly

**Quality Assurance**:
- Verify agents follow the established patterns in `CLAUDE.md`
- Ensure unified diffs are used for edits where possible
- Confirm no commits until user approval
- Validate integration with existing codebase

**Resource Management**:
- Balance agent workload across multiple tasks
- Identify opportunities for parallel execution
- Escalate blockers that require user decisions
- Coordinate cross-task dependencies

#### Delegation Protocol

**For Existing Task Packs**:
1. Review task status and priority
2. Direct agent to: `delegation/{TASK-FOLDER}/AGENT.md`
3. Instruct agent to follow the complete pack structure
4. Monitor progress and update status below

**For New Tasks** (not yet packaged):
1. **Analysis Phase**: Have agent analyze requirements
2. **Pack Creation**: Create new task pack with all 4 files
3. **Validation**: Ensure pack follows established patterns
4. **Delegation**: Assign to specialized agent
5. **Tracking**: Add to sprint tracking below

#### Quality Standards
- **No Breaking Changes**: Preserve existing functionality
- **Pattern Adherence**: Follow established codebase conventions
- **Testing Required**: Feature tests for new functionality
- **Performance Focus**: No regressions, optimization preferred
- **User Approval**: Never commit without user sign-off

#### Communication Protocol
- **Status Updates**: Keep sprint tracking current
- **Escalation**: Flag blockers requiring user decisions
- **Progress Reports**: Regular updates on task completion
- **Integration Issues**: Coordinate when tasks affect each other

---

## Sprint 40: Fragments Engine Core Systems

### Status Legend
- `backlog` - Not yet started
- `todo` - Ready to begin
- `in-progress` - Currently being worked on
- `review` - Awaiting review/testing
- `done` - Completed and approved

---

## Critical Priority Tasks

### CHAT-MODEL-PICKER-RESTORE | `done`
**Description**: Restore lost user model selection feature that allows users to select AI models (OpenAI, Anthropic, Ollama) for each chat session with persistence.

**Key Features**:
- Model selection dropdown in chat composer
- Session-specific model persistence
- Integration with existing ModelSelectionService
- Backend API for model updates
- ChatToolbar component and useModelSelection hook

**Status**: ✅ Restored successfully - UI components attached to chat input, backend integration working
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 4 hours

---

## High Priority Tasks

### FE-01-TYPE-SYSTEM | `in-progress`
**Description**: Implement file-based Type Packs with DB registry cache, JSON schema validation, and generated columns for performance optimization.

**Key Features**:
- Type Pack file structure with YAML manifest and JSON schemas
- Database registry cache for fast lookups
- Schema validation on Fragment create/update
- Generated columns for hot fields (status, due_at)
- Management commands for scaffolding and caching

**Status**: Agent pack created, foundation phase starting
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Estimated**: 2-3 days

---

### FE-02-SLASH-COMMANDS | `backlog`
**Description**: Build Command Packs with YAML DSL runner and built-in commands for common Fragment operations.

**Key Features**:
- Command registry and YAML DSL parser
- Step execution engine (transform, ai.generate, fragment.create, etc.)
- Built-in commands: /todo, /note, /link, /recall, /search
- Integration with existing AI providers
- Management commands for scaffolding and testing

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 3-4 days

---

## Medium Priority Tasks

### FE-03-SCHEDULER | `backlog`
**Description**: Lightweight scheduler with timezone support for automated Command Pack execution.

**Key Features**:
- Schedules and schedule_runs tables
- Timezone-aware next run calculator
- Cron-based tick command and queue integration
- Demo scheduling commands: /news-digest-ai, /remind

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 2-3 days

---

### FE-04-TOOL-REGISTRY | `backlog`
**Description**: Tool providers with capability gating and secure invocation logging.

**Key Features**:
- Tool registry with capability-based security
- Core providers: Shell, FileSystem, MCP, Gmail, Todoist
- Invocation logging and audit trail
- Integration with DSL tool.call step type

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 2-3 days

---

## Lower Priority Tasks

### FE-05-OBSERVERS | `backlog`
**Description**: Event projectors for metrics collection and read model optimization.

**Key Features**:
- Pipeline metrics and performance tracking
- Command and scheduler event projectors
- Read model tables for query optimization
- Backfill commands for historical data

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 1-2 days

---

### FE-06-INBOX | `backlog`
**Description**: AI-assisted inbox service with intelligent Fragment classification and routing.

**Key Features**:
- Inbox service with AI classification
- Prompt factory for intelligent processing
- Fragment routing and categorization
- Integration with existing AI providers

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 2-3 days

---

## Sprint Progress

**Total Tasks**: 7
**Status Distribution**:
- `todo`: 1
- `backlog`: 5
- `in-progress`: 1
- `review`: 0
- `done`: 1

**Estimated Total**: 13-19 days
**Sprint Start**: 2025-01-03
**Target Completion**: 2025-01-21

---

## Sprint 41: UX Modernization & shadcn Blocks Integration

### UX-02-01-SIDEBAR-ENHANCEMENT | `todo`
**Description**: Enhance AppSidebar with shadcn sidebar blocks to provide collapsible/expandable functionality while preserving existing navigation structure.

**Key Features**:
- Install and integrate sidebar-03 and sidebar-07 blocks
- Implement smooth collapse/expand animations
- Maintain existing navigation functionality
- Mobile-responsive behavior improvements
- Accessible keyboard navigation

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 6-8 hours

---

### UX-02-02-DASHBOARD-LAYOUT | `backlog`
**Description**: Implement dashboard block patterns for main content area layout structure while preserving existing chat functionality.

**Key Features**:
- Install dashboard-01 blocks for improved organization
- Maintain chat functionality and routing intact
- Responsive grid patterns across breakpoints
- Professional dashboard-like interface
- Enhanced content organization

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 8-10 hours

---

### UX-02-03-WIDGET-CONTAINERS | `backlog`
**Description**: Implement widget container patterns using shadcn blocks for better organization and user customization foundation.

**Key Features**:
- Widget container blocks for modular layout
- User customization foundation
- Preserve existing widget functionality
- Responsive container patterns
- Enhanced widget organization

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 6-8 hours

---

### UX-02-04-RESPONSIVE-ADAPTATION | `backlog`
**Description**: Enhance responsive design using shadcn block patterns for optimal mobile and tablet experiences.

**Key Features**:
- Mobile-first responsive patterns
- Tablet optimization improvements
- Breakpoint-specific layouts
- Touch interaction enhancements
- Performance optimization for mobile

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 8-10 hours

---

### UX-02-05-CUSTOMIZATION-FOUNDATION | `backlog`
**Description**: Establish user customization foundation using block patterns for layout personalization.

**Key Features**:
- User layout preferences system
- Customizable widget arrangements
- Block-based customization interface
- Preference persistence
- Customization preview system

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 10-12 hours

---

### UX-02-SHADCN-BLOCKS-INTEGRATION | `backlog`
**Description**: Modernize the overall layout system using shadcn blocks for enhanced modularity and responsive design.

**Key Features**:
- Comprehensive block system integration
- Modular layout architecture
- Enhanced responsive design patterns
- Widget system migration strategy
- User experience improvements

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 12-15 hours

---

## Sprint Progress Summary

### Sprint 40 Progress
**Total Tasks**: 7
**Status Distribution**:
- `todo`: 1
- `backlog`: 5
- `in-progress`: 1
- `review`: 0
- `done`: 1

**Estimated Total**: 13-19 days

### Sprint 41 Progress
**Total Tasks**: 6
**Status Distribution**:
- `todo`: 1
- `backlog`: 5
- `in-progress`: 0
- `review`: 0
- `done`: 0

**Estimated Total**: 50-63 hours (6-8 days)

### Overall Project Status
**Total Active Tasks**: 13
**Combined Estimated Effort**: 19-27 days
**Completed Tasks**: 1
**Success Rate**: 8% (1/13) - Sprint 40 just started

---

## Project Management Guidelines

### Task Prioritization
1. **Critical/High Priority**: Core engine features (Sprint 40)
2. **Medium Priority**: UX improvements that enhance existing functionality
3. **Low Priority**: Nice-to-have features and optimizations

### Dependency Management
- **Sprint 40 → Sprint 41**: Type System should complete before major UX changes
- **Within Sprint 41**: Sidebar → Dashboard → Widgets → Responsive → Customization
- **Cross-Sprint**: Model selection feature enables better UX for provider switching

### Risk Mitigation
- **Breaking Changes**: All tasks emphasize preserving existing functionality
- **Scope Creep**: Each task pack has clear boundaries and acceptance criteria
- **Resource Conflicts**: Stagger UI-heavy tasks to avoid merge conflicts
- **User Adoption**: Gradual enhancement approach maintains familiar workflows

---

## Delegation Quick Reference

### Ready-to-Delegate Task Packs
- `delegation/FE-01-TYPE-SYSTEM/` - Type Packs with registry cache
- `delegation/sprint-41/UX-02-01-sidebar-enhancement/` - Collapsible sidebar
- `delegation/sprint-41/UX-02-02-dashboard-layout/` - Dashboard patterns
- `delegation/sprint-41/UX-02-03-widget-containers/` - Widget organization
- `delegation/sprint-41/UX-02-04-responsive-adaptation/` - Mobile optimization
- `delegation/sprint-41/UX-02-05-customization-foundation/` - User customization
- `delegation/sprint-41/UX-02-shadcn-blocks-integration/` - Overall modernization

### Example Delegation Commands
```
"Please execute the task defined in delegation/FE-01-TYPE-SYSTEM/AGENT.md. 
Follow the complete pack structure including CONTEXT.md, PLAN.md, and TODO.md."

"Take on the UX sidebar enhancement task. Start with delegation/sprint-41/UX-02-01-sidebar-enhancement/AGENT.md 
and follow the workflow defined in the task pack."
```

---

## Notes

- All tasks follow established patterns from existing codebase
- Each task has dedicated agent pack in delegation/ folder
- Sub-agents will be used for complex domain-specific work
- No commits until user approval on working features
- Unified diffs preferred for all edits

---

## Project Context for AI Agents

### Technology Stack
- **Backend**: Laravel 12, PHP 8.3, PostgreSQL
- **Frontend**: React + TypeScript, Vite, shadcn/ui, Tailwind CSS v4
- **AI**: Multiple providers (OpenAI, Anthropic, Ollama, OpenRouter)
- **Testing**: Pest (PHP), React Testing Library
- **Formatting**: Laravel Pint, Prettier

### Key Architectural Patterns
- **Fragment System**: Core data model for all content types
- **AI Provider Abstraction**: Unified interface for multiple AI services
- **Modular Widgets**: React islands in Blade for interactive components
- **Type System**: JSON schema validation with generated columns
- **Command System**: Slash commands with YAML DSL runners

### Development Workflow
1. **Analysis**: Understand existing patterns before implementing
2. **Planning**: Break complex tasks into phases
3. **Implementation**: Follow established conventions and patterns
4. **Testing**: Feature tests for new functionality
5. **Integration**: Ensure no regressions in existing features
6. **Review**: User approval before committing

### Critical Files to Understand
- `CLAUDE.md` - Development guidelines and conventions
- `app/Models/Fragment.php` - Core data model
- `app/Services/AI/` - AI provider abstraction
- `resources/js/` - Frontend React components
- `config/fragments.php` - System configuration

### Quality Checkpoints
- [ ] Code follows PSR-12 and project conventions
- [ ] TypeScript types are properly defined
- [ ] No breaking changes to existing functionality
- [ ] Performance maintained or improved
- [ ] Mobile responsiveness preserved
- [ ] Accessibility standards maintained
- [ ] Tests pass and provide adequate coverage

### Success Metrics
- **Functionality**: All existing features continue to work
- **Performance**: No measurable regressions
- **User Experience**: Enhanced without disrupting workflows
- **Code Quality**: Consistent with established patterns
- **Maintainability**: Clear, documented, and extensible code

---

## Agent Activation Instructions

**You are now the Project Manager AI Agent for Fragments Engine.** Your role is to:

1. **Monitor and update task status** in the sprint tracking above
2. **Delegate tasks** by directing agents to specific task pack folders
3. **Coordinate dependencies** between related tasks
4. **Ensure quality standards** are maintained across all work
5. **Escalate blockers** that require user decisions or input

**To get started:**
- Review the current sprint status above
- Identify the highest priority `todo` tasks
- Begin delegation by directing agents to appropriate task packs
- Update status and progress as work proceeds

**Remember**: Your job is coordination and oversight, not direct implementation. Use the established delegation structure and agent specialization for optimal results.