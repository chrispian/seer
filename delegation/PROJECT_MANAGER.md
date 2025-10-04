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
**Notes**: ✅ Includes comprehensive Sprint 40 planning docs and PROJECT_MANAGER agent system

---

## High Priority Tasks

### FE-01-TYPE-SYSTEM | `done`
**Description**: Implement file-based Type Packs with DB registry cache, JSON schema validation, and generated columns for performance optimization.

**Key Features**:
- Type Pack file structure with YAML manifest and JSON schemas
- Database registry cache for fast lookups
- Schema validation on Fragment create/update
- Generated columns for hot fields (status, due_at)
- Management commands for scaffolding and caching

**Status**: ✅ Completed with full UI integration - TypeSystemWidget with API endpoints, TypeBadge components
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 2-3 days

---

### FE-02-SLASH-COMMANDS | `done`
**Description**: Build Command Packs with YAML DSL runner and built-in commands for common Fragment operations.

**Key Features**:
- Command registry and YAML DSL parser
- Step execution engine (transform, ai.generate, fragment.create, etc.)
- Built-in commands: /todo, /note, /link, /recall, /search
- Integration with existing AI providers
- Management commands for scaffolding and testing

**Status**: ✅ Completed - 10 command packs implemented with DSL execution engine
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 3-4 days

---

## Medium Priority Tasks

### FE-03-SCHEDULER | `done`
**Description**: Lightweight scheduler with timezone support for automated Command Pack execution.

**Key Features**:
- Schedules and schedule_runs tables
- Timezone-aware next run calculator
- Cron-based tick command and queue integration
- Demo scheduling commands: /news-digest-ai, /remind

**Status**: ✅ Completed with full UI integration - SchedulerWidget with real-time status monitoring
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 2-3 days

---

### FE-04-TOOL-REGISTRY | `done`
**Description**: Tool providers with capability gating and secure invocation logging.

**Key Features**:
- Tool registry with capability-based security
- Core providers: Shell, FileSystem, MCP, Gmail, Todoist
- Invocation logging and audit trail
- Integration with DSL tool.call step type

**Status**: ✅ Completed - Tool registry with capability-based security and audit logging
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 2-3 days

---

## Lower Priority Tasks

### FE-05-OBSERVERS | `done`
**Description**: Event projectors for metrics collection and read model optimization.

**Key Features**:
- Pipeline metrics and performance tracking
- Command and scheduler event projectors
- Read model tables for query optimization
- Backfill commands for historical data

**Status**: ✅ Completed - Event-driven metrics system with projectors and backfill commands
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 1-2 days

---

### FE-06-INBOX | `done`
**Description**: AI-assisted inbox service with intelligent Fragment classification and routing.

**Key Features**:
- Inbox service with AI classification
- Prompt factory for intelligent processing
- Fragment routing and categorization
- Integration with existing AI providers

**Status**: ✅ Completed with comprehensive UI - InboxWidget with bulk operations, filtering, and review interface
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 2-3 days

---

## Sprint Progress

**Total Tasks**: 7
**Status Distribution**:
- `todo`: 0
- `backlog`: 0
- `in-progress`: 0
- `review`: 0
- `done`: 7

**Estimated Total**: 13-19 days
**Sprint Start**: 2025-01-03
**Target Completion**: 2025-01-21
**Actual Completion**: 2025-01-03 (Sprint 40 COMPLETED! 🎉)

### Sprint 40 Achievements Summary
✅ **Complete Backend + Frontend Integration Delivered**:
- **Type System**: File-based type packs with UI indicators and validation
- **Command System**: 10 command packs with DSL execution engine
- **Scheduler**: Time-based automation with real-time monitoring UI
- **Tool Registry**: Capability-based tool system with audit logging
- **Observer System**: Event-driven metrics with projectors
- **Inbox System**: AI-assisted fragment review with comprehensive UI

### New UI Components Added
- **InboxWidget**: Complete fragment review interface with bulk operations
- **TypeSystemWidget**: Type pack browser with validation indicators  
- **SchedulerWidget**: Real-time schedule monitoring and run history
- **Enhanced FragmentReviewCard**: Inline editing with type system integration
- **API Endpoints**: Complete REST APIs for all Sprint 40 features

**Result**: Fragments Engine now has a fully functional core system with modern UI!

---

## Sprint 41: UX Modernization & shadcn Blocks Integration

### UX-02-01-SIDEBAR-ENHANCEMENT | `done`
**Description**: Enhance AppSidebar with collapsible functionality while preserving existing navigation structure and three-column layout.

**Key Features**:
- ✅ Simple toggle-based collapse functionality (64px icon mode)
- ✅ Smooth CSS transitions for width changes
- ✅ Maintain existing navigation functionality and three-column layout
- ✅ Preserve Ribbon + Sidebar + Main + RightRail design
- ✅ Compatible solution without breaking existing patterns

**Status**: ✅ Completed successfully with simple collapse solution that preserves layout
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 3 hours

---

### UX-02-02-DASHBOARD-LAYOUT | `done`
**Description**: Implement professional dashboard layout patterns for main content area while preserving existing chat functionality.

**Key Features**:
- ✅ Enhanced semantic HTML structure (main, header elements)
- ✅ Responsive grid-based organization ready for future widgets
- ✅ Professional card styling with shadows and borders
- ✅ Responsive padding scaling (p-4 md:p-6 lg:p-8)
- ✅ Maximum width container for large screen optimization
- ✅ Visual hierarchy with background layers and proper spacing
- ✅ All chat functionality and routing preserved perfectly

**Status**: ✅ Completed successfully with professional dashboard patterns
**Last Updated**: 2025-01-03 | **Assignee**: Primary Agent | **Completed**: 2 hours

---

### UX-02-03-WIDGET-CONTAINERS | `todo`
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

## Sprint 42: User Setup & Profile Management System

### UX-03-01-DATABASE-SCHEMA | `todo`
**Description**: Enhance the users table schema to support comprehensive user profiles, avatar management, and settings persistence for the setup system.

**Key Features**:
- Database migration for user profile fields (display_name, avatar_path, use_gravatar, profile_settings, profile_completed_at)
- Updated User model with profile-related methods and relationships
- Profile settings JSON structure with proper validation
- Migration rollback safety and data integrity constraints

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 3-4 hours

---

### UX-03-02-BACKEND-SERVICES | `todo`
**Description**: Create backend services for user profile management, avatar handling, and settings persistence with comprehensive API endpoints.

**Key Features**:
- AvatarService for Gravatar integration and file upload management
- UserProfileService for profile operations and validation
- API controllers for profile and settings endpoints
- Local caching system for offline Gravatar functionality
- Security validation for file uploads and data integrity

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 4-6 hours

---

### UX-03-03-SETUP-WIZARD | `todo`
**Description**: Create an intuitive multi-step setup wizard using shadcn components to replace traditional authentication with user-friendly onboarding.

**Key Features**:
- Multi-step wizard with Welcome, Profile, Avatar, Preferences, and Completion steps
- Form validation and error handling with shadcn form components
- Step progress indication and smooth navigation
- Integration with backend APIs for data submission
- Responsive design and accessibility support

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 6-8 hours

---

### UX-03-04-AVATAR-SYSTEM | `todo`
**Description**: Implement comprehensive avatar management system with Gravatar integration, custom file uploads, and local caching optimized for NativePHP desktop environment.

**Key Features**:
- Real-time Gravatar preview based on email input
- Drag-and-drop file upload with image validation
- Image processing capabilities (cropping, resizing, optimization)
- Local caching system for offline Gravatar access
- Fallback handling and professional avatar management interface

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 6-8 hours

---

### UX-03-05-SETTINGS-PAGE | `todo`
**Description**: Create comprehensive settings management interface with tabbed navigation, real-time updates, and persistent user preference storage.

**Key Features**:
- Tabbed settings interface (Profile, Preferences, AI Settings, Appearance)
- Real-time validation and settings persistence
- Settings import/export capabilities
- Integration with user context and preference systems
- Professional interface matching application design system

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 8-10 hours

---

### UX-03-USER-SETUP-SYSTEM | `todo`
**Description**: Overall coordination task for integrating all user setup system components into a cohesive experience that replaces traditional authentication.

**Key Features**:
- Integration of all setup system components
- Replacement of AuthModal with SetupWizard
- Testing and validation of complete user flow
- Documentation and user experience optimization
- NativePHP desktop app optimization

**Last Updated**: 2025-01-03 | **Assignee**: TBD | **Estimated**: 2-3 hours

---

## Sprint Progress Summary

### Sprint 40 Progress ✅ COMPLETED
**Total Tasks**: 7
**Status Distribution**:
- `todo`: 0
- `backlog`: 0
- `in-progress`: 0
- `review`: 0
- `done`: 7

**Estimated Total**: 13-19 days
**Actual Total**: 1 day (Accelerated completion with full UI integration!)

### Sprint 41 Progress
**Total Tasks**: 6
**Status Distribution**:
- `todo`: 1
- `backlog`: 5
- `in-progress`: 0
- `review`: 0
- `done`: 0

**Estimated Total**: 50-63 hours (6-8 days)

### Sprint 42 Progress
**Total Tasks**: 6
**Status Distribution**:
- `todo`: 6
- `backlog`: 0
- `in-progress`: 0
- `review`: 0
- `done`: 0

**Estimated Total**: 29-39 hours (4-5 days)

### Overall Project Status
**Total Active Tasks**: 19
**Combined Estimated Effort**: 25-35 days
**Completed Tasks**: 7 (Full Sprint 40 + Model Selection)
**Success Rate**: 42% (8/19) - Sprint 40 complete with comprehensive UI integration!

---

## Project Management Guidelines

### Task Prioritization
1. **Critical/High Priority**: Core engine features (Sprint 40)
2. **Medium Priority**: UX improvements that enhance existing functionality
3. **Low Priority**: Nice-to-have features and optimizations

### Dependency Management
- **Sprint 40 → Sprint 41**: Type System should complete before major UX changes
- **Within Sprint 41**: Sidebar → Dashboard → Widgets → Responsive → Customization
- **Sprint 41 → Sprint 42**: Enhanced layout blocks enable better settings page integration
- **Within Sprint 42**: Database Schema → Backend Services → Setup Wizard → Avatar System → Settings Page → Integration
- **Cross-Sprint**: Model selection feature enables better UX for provider switching

### Risk Mitigation
- **Breaking Changes**: All tasks emphasize preserving existing functionality
- **Scope Creep**: Each task pack has clear boundaries and acceptance criteria
- **Resource Conflicts**: Stagger UI-heavy tasks to avoid merge conflicts
- **User Adoption**: Gradual enhancement approach maintains familiar workflows

---

## Delegation Quick Reference

### Ready-to-Delegate Task Packs

**Sprint 40 (Core Engine)**:
- `delegation/FE-01-TYPE-SYSTEM/` - Type Packs with registry cache

**Sprint 41 (Layout Modernization)**:
- `delegation/sprint-41/UX-02-01-sidebar-enhancement/` - Collapsible sidebar
- `delegation/sprint-41/UX-02-02-dashboard-layout/` - Dashboard patterns
- `delegation/sprint-41/UX-02-03-widget-containers/` - Widget organization
- `delegation/sprint-41/UX-02-04-responsive-adaptation/` - Mobile optimization
- `delegation/sprint-41/UX-02-05-customization-foundation/` - User customization
- `delegation/sprint-41/UX-02-shadcn-blocks-integration/` - Overall modernization

**Sprint 42 (User Setup & Profile Management)**:
- `delegation/sprint-42/UX-03-01-database-schema/` - User profile database schema
- `delegation/sprint-42/UX-03-02-backend-services/` - Profile and avatar backend services
- `delegation/sprint-42/UX-03-03-setup-wizard/` - Multi-step onboarding wizard
- `delegation/sprint-42/UX-03-04-avatar-system/` - Gravatar integration and upload system
- `delegation/sprint-42/UX-03-05-settings-page/` - Comprehensive settings management
- `delegation/sprint-42/UX-03-user-setup-system/` - Overall integration and coordination

### Example Delegation Commands
```
"Please execute the task defined in delegation/FE-01-TYPE-SYSTEM/AGENT.md. 
Follow the complete pack structure including CONTEXT.md, PLAN.md, and TODO.md."

"Take on the UX sidebar enhancement task. Start with delegation/sprint-41/UX-02-01-sidebar-enhancement/AGENT.md 
and follow the workflow defined in the task pack."

"Begin the user setup system database schema task. Start with delegation/sprint-42/UX-03-01-database-schema/AGENT.md 
and follow the complete task pack structure."
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