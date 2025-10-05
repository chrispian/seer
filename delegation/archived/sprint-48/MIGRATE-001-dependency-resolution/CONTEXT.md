# MIGRATE-001: Dependency Resolution Context

## Sprint Context
Sprint 48 represents a fundamental shift in our delegation workflow from file-based sprint management to Fragments Engine-powered agent orchestration. This migration cannot proceed without critical dependencies being completed first.

## Technical Architecture Context

### **Current Delegation System**
- **File-based Sprint Management**: Sprints stored as folders with markdown task packs
- **Manual Agent Coordination**: Agents assigned manually via comments and documentation
- **Git Worktree Isolation**: Parallel development using separate git worktrees
- **Shell Script Automation**: Setup and cleanup via bash scripts
- **Context Storage**: Task context stored in markdown files

### **Target Fragments Engine Architecture**
- **Database-backed Sprint Management**: Sprints and tasks stored in relational database
- **Agent Profile System**: Standardized agent profiles with capabilities and permissions
- **MCP-Exposed Orchestration**: Tools accessible via Model Context Protocol
- **Memory System**: Agent notes, decisions, and context with vector search
- **Tool Contract Architecture**: Standardized tool interfaces with versioning and telemetry

## Dependency Analysis

### **ENG-09-01: Tool SDK Foundation**
**Critical Path Dependency**

**What It Provides:**
- `ToolContract` PHP interface for standardized tool definitions
- `ToolRegistry` with JSON schema loading and validation
- Scoping and quota management for tool permissions
- Telemetry middleware for tool usage tracking and audit
- Versioning system for tool contract evolution

**Why We Need It:**
- Foundation for all MCP integration work
- Required for agent tool permission validation
- Enables audit trail for agent actions
- Provides standardized interface for all tool interactions

**Risk Assessment:**
- **High Impact**: Without this, no tool integration possible
- **Medium Complexity**: Well-defined scope with clear deliverables
- **Low Risk**: Standard Laravel patterns, no external dependencies

### **ENG-09-04: Agent Memory Foundation**
**Critical Path Dependency**

**What It Provides:**
- `AgentNote` model for short-term agent memory storage
- `AgentDecision` model for long-term decision tracking
- `AgentVector` model for embedding-based memory search
- Memory tools: `memory.write`, `memory.search`, `memory.rollup`
- Vector search capabilities for context retrieval

**Why We Need It:**
- Essential for context preservation during workflow migration
- Required for RAG-based task context retrieval
- Enables agent learning and decision tracking
- Foundation for cross-task agent coordination

**Risk Assessment:**
- **High Impact**: Critical for context preservation and agent coordination
- **High Complexity**: Vector search integration, memory management
- **Medium Risk**: Dependency on embedding provider, performance considerations

### **ENG-09-05: Prompt Orchestrator**
**Critical Path Dependency**

**What It Provides:**
- Dynamic system prompt assembly with context integration
- Memory integration for relevant context injection
- A/B testing capabilities for prompt optimization
- Telemetry for prompt performance tracking
- Tool schema integration for dynamic tool availability

**Why We Need It:**
- Required for system project manager agent implementation
- Enables context-aware agent behavior
- Essential for agent handoff and coordination
- Foundation for intelligent task assignment

**Risk Assessment:**
- **High Impact**: Core to agent orchestration and coordination
- **Medium Complexity**: Prompt templating, context integration
- **Medium Risk**: Performance optimization, context relevance tuning

### **UX-04-02: Agent Manager System**
**Critical Path Dependency**

**What It Provides:**
- Agent profile database schema and management
- Agent mode system (Agent/Plan/Chat/Assistant)
- Avatar system with upload and generation capabilities
- Scope resolution hierarchy (Vault > Project > Chat > Message > Task)
- Agent cloning and versioning capabilities

**Why We Need It:**
- Foundation for agent profile migration
- Required for agent permission and capability management
- Enables scope-based agent resolution
- Essential for agent governance and security

**Risk Assessment:**
- **High Impact**: Core to entire agent management strategy
- **Medium Complexity**: UI integration, file upload, scope resolution
- **Low Risk**: Well-defined requirements, standard Laravel patterns

## Integration Requirements

### **MCP Server Architecture**
Once dependencies are complete, we need to understand:
- Available MCP server endpoints and methods
- Authentication and authorization mechanisms
- Rate limiting and quota management
- Error handling and retry strategies
- Telemetry and monitoring capabilities

### **Agent Profile Schema**
We need to understand the final agent profile structure:
- Required vs optional fields for agent profiles
- Capability and permission definition format
- Mode constraint specification
- Tool access control mechanisms
- Scope resolution configuration

### **Memory System Integration**
Critical details about the memory system:
- Memory scoping (global, vault, project, task)
- Vector search query capabilities and performance
- Memory rollup automation and scheduling
- Context relevance scoring and retrieval
- Cross-agent memory sharing policies

### **Tool Contract Specifications**
Essential for implementation planning:
- Tool input/output schema definitions
- Permission and scoping mechanisms
- Approval gate configuration and workflows
- Telemetry data collection requirements
- Error handling and rollback procedures

## Current Unknowns

### **API Specifications**
- Exact MCP server method signatures and parameters
- Authentication tokens and session management
- Rate limiting policies and quota calculations
- Error response formats and retry strategies

### **Performance Characteristics**
- Database query performance for sprint/task operations
- Vector search performance for memory retrieval
- MCP call latency and timeout configurations
- Concurrent agent operation limits

### **Configuration Requirements**
- Environment variables and configuration file structure
- Database migration requirements and data seeding
- Permission configuration for agent capabilities
- Integration points with existing Fragments Engine features

## Success Criteria for Dependency Resolution

### **Functional Readiness**
- [ ] Tool SDK can register and execute tools with proper scoping
- [ ] Agent Memory can store and retrieve context with vector search
- [ ] Prompt Orchestrator can assemble context-aware prompts
- [ ] Agent Manager can create, manage, and resolve agent profiles

### **API Completeness**
- [ ] MCP server endpoints documented with examples
- [ ] Tool contracts defined with input/output schemas
- [ ] Authentication and authorization mechanisms operational
- [ ] Error handling and telemetry systems functional

### **Integration Testing**
- [ ] End-to-end workflow from agent creation to task execution
- [ ] Cross-component integration (memory + orchestrator + tools)
- [ ] Performance validation under expected load
- [ ] Security validation for permission and scoping systems

## Timeline Impact Assessment

### **Best Case Scenario**
- All dependencies complete on schedule (2-3 weeks)
- Sprint 48 can commence with minor task updates required
- Full implementation possible within original 4-week timeline

### **Expected Scenario**
- Dependencies complete with 1-2 week delay
- Sprint 48 requires 1 week for task plan updates
- Implementation timeline extends to 5-6 weeks total

### **Worst Case Scenario**
- Major dependency delays or scope changes (>3 weeks)
- Sprint 48 requires significant replanning
- Consider alternative approaches or sprint postponement

---

**Document Owner**: Dependency Resolution Agent  
**Last Updated**: Sprint 48 Initialization  
**Next Review**: Weekly until dependencies resolved