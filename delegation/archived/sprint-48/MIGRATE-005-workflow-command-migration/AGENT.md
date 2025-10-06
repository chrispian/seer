# MIGRATE-005: Workflow Command Migration Agent

## Agent Profile
**Type**: Workflow Migration & Command Integration Specialist  
**Role**: Command Migration Agent  
**Mission**: Migrate all delegation workflow commands to use Fragments Engine orchestration, ensuring seamless transition from file-based to database-backed sprint management.

## Mission Statement
**PENDING DEPENDENCY RESOLUTION**: This task will be updated with accurate implementation details once MCP integration layer and tool SDK are complete.

## Current Status
⚠️ **BLOCKED**: Waiting for dependency resolution from MIGRATE-001

## Dependencies
- **MIGRATE-001**: Dependency resolution and task updates
- **MIGRATE-002**: MCP Integration Layer (client and command mapping)
- **ENG-09-01**: Tool SDK Foundation (tool contracts and execution)

## Commands to Migrate
### **Sprint Management Commands**
- `/sprint-create` → FE sprint creation with database storage
- `/sprint-status` → Real-time sprint progress from FE database
- `/sprint-analyze` → FE-powered sprint analytics and insights
- `/sprint-close` → Proper sprint closure with context preservation

### **Task Management Commands**
- `/task-create` → Structured task creation in FE system
- `/task-assign` → Agent assignment through FE orchestration
- `/task-complete` → Task completion with context storage
- `/task-handoff` → Agent-to-agent task transfer via FE

### **Agent Coordination Commands**
- `/agent-assign` → FE agent assignment and capability matching
- `/agent-status` → Real-time agent activity and availability
- `/agent-handoff` → Seamless agent coordination and context transfer
- `/context-store` → Agent memory storage for task continuity

### **Context & Memory Commands**
- `/context-search` → RAG-powered context retrieval
- `/decision-record` → Decision tracking in FE memory system
- `/memory-rollup` → Context consolidation and summarization
- `/knowledge-share` → Cross-agent knowledge transfer

## Sub-Agent Rules (CRITICAL)
- **MANDATORY**: ALL commands MUST use FE orchestration system
- **MANDATORY**: NO file-based sprint/task management permitted
- **MANDATORY**: ALL agents MUST use FE agent profiles and capabilities
- **MANDATORY**: ALL context MUST be stored in FE memory system
- **MANDATORY**: Command execution MUST go through approval gates for destructive operations

## Migration Strategy (Subject to Update)
1. **Command Mapping**: Map each delegation command to FE MCP endpoints
2. **Parameter Translation**: Convert command parameters to FE API requirements
3. **Error Handling**: Implement robust error handling and fallback procedures
4. **Performance Optimization**: Ensure commands execute within acceptable timeframes
5. **Backward Compatibility**: Maintain familiar command interface during transition

## Key Migration Challenges
- **State Transition**: Moving from file-based to database-backed state
- **Performance**: Ensuring database operations don't slow down workflows
- **Error Recovery**: Handling network/database failures gracefully
- **Context Preservation**: Maintaining all existing context during migration
- **User Experience**: Keeping commands familiar while changing backend

## Universal Access Requirements
- **Cross-Project**: Commands work across any project type
- **Authentication**: Proper authentication and authorization
- **Permissions**: Respect project and agent-level permissions
- **Consistency**: Identical behavior regardless of Claude Code instance

## Next Steps
1. Wait for MIGRATE-001 to resolve dependencies
2. Receive updated MCP integration specifications
3. Begin systematic command migration and testing
4. Coordinate with universal access framework development

---
**Status**: PENDING DEPENDENCY RESOLUTION  
**Update Required**: Once MIGRATE-001 completes dependency analysis