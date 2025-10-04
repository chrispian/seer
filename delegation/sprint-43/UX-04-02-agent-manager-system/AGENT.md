# UX-04-02 Agent Manager System Agent Profile

## Mission
Implement a comprehensive agent profile management system with hybrid primary agent model, supporting both system and user agents with cloning capabilities, mode-based execution boundaries, and per-scope defaults.

## Workflow
- Review agent manager requirements from backlog documentation
- Create database schema for agent profiles and history tracking
- Implement backend services for agent management and resolution
- Build React UI for agent CRUD operations and profile management
- Create agent selection and mode override interfaces
- Integrate with existing AI provider and command systems
- Implement logging and telemetry for agent interactions

## Quality Standards
- Database schema supports versioning and lineage tracking
- Agent profiles properly validate mode constraints and tool permissions
- UI follows existing Fragments Engine patterns and shadcn components
- Agent resolution follows proper scope hierarchy (command → project → workspace → global)
- Logging captures complete agent interaction chains
- Performance optimized for agent switching and profile loading
- Security validates tool access and execution permissions

## Deliverables
- Database migrations for agent_profiles and agent_profile_histories tables with avatar support
- AgentResolver, AgentVersioner, PromptAssembler, and AgentAvatarService services
- Agent management API endpoints with full CRUD operations and avatar management
- AgentProfileManager React component with CRUD interface and avatar upload
- Agent selector components with avatar display for chat and command interfaces
- AgentAvatar component with upload, initials generation, and fallback support
- Mode override system with capability validation
- Agent lineage and history tracking
- Comprehensive logging and telemetry integration

## Key Features to Implement
- **Agent Profiles**: Name, description, role, mode, model, tools, personality settings
- **Avatar System**: Upload avatars, initials generation, emoji support, with future AI generation
- **Mode System**: Agent (full execution), Plan (read-only), Chat (conversational), Assistant (productivity)
- **Cloning System**: Create agent profiles with lineage tracking and version management
- **Scope Defaults**: Per-command, project, workspace, and global agent defaults
- **Hybrid Model**: Primary chat agent with sub-agent delegation capabilities
- **Profile Versioning**: Change tracking with rollback capabilities
- **Tool Registry**: Allowed tools validation with capability-based security

## Technical Integration Points
- Uses existing AI provider abstraction for model management
- Integrates with command system for agent resolution
- Leverages user authentication for profile ownership
- Uses existing modal and sheet patterns for UI components
- Implements proper TypeScript types throughout
- Follows established API patterns for CRUD operations

## Safety Notes
- System agents must be protected from deletion or unauthorized modification
- Agent mode constraints must be properly enforced during execution
- Tool access validation prevents security breaches
- Profile versioning ensures rollback capabilities
- Logging captures all agent interactions for audit trail
- Performance impact minimized through proper caching

## Communication
- Report database schema creation and migration progress
- Document agent resolution hierarchy and mode constraints
- Provide API endpoint documentation and integration examples
- Confirm UI component integration with existing patterns
- Deliver comprehensive testing results and security validation