# Seer Systems Inventory

## Core Systems

### 1. Fragments Engine (Content-Addressable Storage)
**Purpose**: Content-addressable storage for all text content with deduplication and vector search

**Models**:
- Fragment (primary content storage)
- FragmentLink (wiki-style links between fragments)
- Source (track content sources)
- Type (fragment type classification)

**Tables**:
- fragments (id, content, hash, metadata, vectors)
- fragment_links (from_id, to_id)
- sources
- types

**Services**:
- app/Services/FragmentService.php
- app/Services/CAS/ContentAddressableStorage.php
- app/Services/Ingestion/* (pipeline for processing content)

**Commands**:
- app/Commands/Fragment/* (sync, search, manage)

**Key Features**:
- SHA-256 based deduplication
- pgvector for semantic search
- Markdown processing
- Wiki-style linking

---

### 2. AI Provider Management
**Purpose**: Unified interface for multiple LLM providers with tool calling support

**Models**:
- AIModel (available models)
- Provider (API configurations)
- AICredential (API keys/secrets)

**Tables**:
- ai_models
- providers
- ai_credentials

**Services**:
- app/Services/AI/AIProviderManager.php
- app/Services/AI/Providers/* (OpenAI, Anthropic, Ollama, OpenRouter)
- app/Services/AI/PrismProviderAdapter.php (unified Prism integration)

**Configuration**:
- config/fragments.php (AI_USE_PRISM flag)
- config/openai.php
- config/prism.php

**Key Features**:
- Multi-provider support (OpenAI, Anthropic, Ollama, OpenRouter)
- Streaming responses
- Tool calling / function execution
- Model selection per session
- Prism v0.91.1 integration

---

### 3. Chat System
**Purpose**: Interactive chat interface with AI models, context management, and tool execution

**Models**:
- ChatSession (conversations)
- Message (individual messages)
- SessionActivity (time tracking)
- SessionContextHistory (context snapshots)

**Tables**:
- chat_sessions
- messages
- session_activities
- session_context_history

**Services**:
- app/Services/Chat/ChatService.php
- app/Services/Chat/SessionManager.php

**Frontend**:
- resources/js/islands/chat/ChatIsland.tsx (main interface)
- resources/js/islands/chat/ChatComposer.tsx (input)
- resources/js/hooks/useChatSessions.ts

**Key Features**:
- Multi-turn conversations
- Context window management
- Model selection per session
- Voice input support
- Markdown rendering

---

### 4. Tool-Aware Orchestration (M3)
**Purpose**: LLM-powered request routing and tool execution with multi-stage pipeline

**Models**:
- ToolDefinition (available tools from MCP)
- Command (executable commands)

**Tables**:
- tool_definitions
- commands

**Services**:
- app/Services/Orchestration/ToolAware/ToolAwarePipeline.php (main pipeline)
- app/Services/Orchestration/ToolAware/Router.php (analyze & route)
- app/Services/Orchestration/ToolAware/ToolSelector.php (select tools)
- app/Services/Orchestration/ToolAware/OutcomeSummarizer.php (summarize results)
- app/Services/Orchestration/ToolAware/FinalComposer.php (compose response)

**Pipeline Stages**:
1. Router - Analyze request, determine routing
2. ToolSelector - Select appropriate tools
3. Tool Execution - Execute selected tools
4. OutcomeSummarizer - Summarize results
5. FinalComposer - Generate final response

**Key Features**:
- Streaming status updates
- Provider selection per stage
- Tool execution with validation
- Multi-stage reasoning
- Error handling & fallbacks

---

### 5. MCP (Model Context Protocol) Integration
**Purpose**: Dynamic tool discovery and execution via MCP servers

**Models**:
- ToolDefinition (MCP tool schemas)

**Tables**:
- tool_definitions

**Services**:
- app/Mcp/McpManager.php
- app/Mcp/McpServerManager.php
- app/Mcp/Tools/* (native tool implementations)

**Commands**:
- app/Commands/Mcp/SyncMcpTools.php
- app/Commands/Mcp/ListMcpServers.php

**Jobs**:
- app/Jobs/RefreshMcpToolsJob.php

**Configuration**:
- .mcp.json (server configurations)

**Key Features**:
- Dynamic tool discovery
- Multiple MCP servers
- Tool schema validation
- Native tool fallbacks

---

### 6. Telemetry & Observability
**Purpose**: Comprehensive logging, metrics, and performance monitoring

**Models**:
- TelemetryEvent (events)
- TelemetryMetric (metrics)
- TelemetryCorrelationChain (trace requests)
- TelemetryHealthCheck (health status)
- TelemetryPerformanceSnapshot (performance data)

**Tables**:
- telemetry_events
- telemetry_metrics
- telemetry_correlation_chains
- telemetry_health_checks
- telemetry_performance_snapshots

**Services**:
- app/Services/Telemetry/TelemetrySink.php
- app/Services/Telemetry/TelemetryQueryService.php

**Commands**:
- app/Commands/Telemetry/TelemetryCleanupCommand.php
- app/Commands/Telemetry/TelemetryHealthCommand.php

**Jobs**:
- app/Jobs/ProcessTelemetryBatch.php

**Configuration**:
- config/llm-telemetry.php
- config/command-telemetry.php
- config/fragment-telemetry.php

**Key Features**:
- Event streaming
- Metrics aggregation
- Correlation chains
- Health checks
- Performance snapshots
- Batch processing

---

### 7. Memory System (Agent Memory)
**Purpose**: Agent long-term memory for notes, decisions, and context

**Models**:
- AgentNote (general notes)
- AgentDecision (decision records)
- RecallDecision (recall logic)

**Tables**:
- agent_notes
- agent_decisions
- recall_decisions

**Services**:
- app/Mcp/Tools/MemorySearchTool.php
- app/Mcp/Tools/MemoryWriteTool.php

**Key Features**:
- Topical organization
- TTL support
- Provenance tracking
- Tag-based retrieval
- Decision rationale

---

### 8. Vault System
**Purpose**: Multi-vault content organization with routing rules

**Models**:
- Vault (content containers)
- VaultRoutingRule (routing logic)

**Tables**:
- vaults
- vault_routing_rules

**Services**:
- app/Services/Vault/VaultRoutingRuleService.php
- app/Services/Vault/RoutingRulesManager.php

**Frontend**:
- resources/js/components/sidebar/VaultSelector.tsx

**Key Features**:
- Multi-vault support
- Pattern-based routing
- Default vault fallback
- Vault switching in UI

---

### 9. Orchestration & Project Management
**Purpose**: Task, sprint, and project management with agent orchestration

**Models**:
- OrchestrationTask (tasks)
- OrchestrationSprint (sprints)
- OrchestrationArtifact (deliverables)
- OrchestrationEvent (events)
- Sprint (legacy sprint model)
- SprintItem (sprint items)
- TaskAssignment (assignments)
- TaskActivity (activity log)
- WorkItem (work items)
- WorkSession (time tracking)

**Tables**:
- orchestration_tasks
- orchestration_sprints
- orchestration_artifacts
- orchestration_events
- sprints
- sprint_items
- task_assignments
- task_activities
- work_items
- work_sessions

**Services**:
- app/Services/Orchestration/TaskOrchestrationService.php
- app/Services/Orchestration/SprintOrchestrationService.php
- app/Services/Orchestration/AgentOrchestrationService.php
- app/Services/Orchestration/OrchestrationPMToolsService.php

**Frontend**:
- resources/js/components/orchestration/* (modals)

**Key Features**:
- Task management
- Sprint planning
- Agent assignment
- Time tracking
- Artifact generation
- Event logging

---

### 10. Command Execution System
**Purpose**: Safe shell command execution with auditing and telemetry

**Models**:
- Command (available commands)
- CommandAuditLog (execution log)

**Tables**:
- commands
- command_audit_logs

**Services**:
- app/Services/Shell/EnhancedShellExecutor.php
- app/Services/Shell/CommandValidator.php

**Events**:
- app/Events/DestructiveCommandExecuted.php

**Commands**:
- app/Commands/Shell/* (various shell commands)

**Configuration**:
- config/command-telemetry.php

**Key Features**:
- Whitelist validation
- Destructive command detection
- Audit logging
- Telemetry integration
- Real-time output streaming

---

### 11. Ingestion Pipeline
**Purpose**: Import content from external sources (Obsidian, files, APIs)

**Services**:
- app/Services/Ingestion/ObsidianImportService.php
- app/Services/Ingestion/DocumentationImportService.php
- app/Services/Ingestion/AgentLogImportService.php

**Commands**:
- app/Commands/ObsidianSyncCommand.php
- app/Commands/ImportDocumentation.php

**Key Features**:
- Markdown parsing
- Wiki-link resolution
- Metadata extraction
- Incremental sync
- Batch processing

---

### 12. Agent System
**Purpose**: Autonomous agent profiles and execution

**Models**:
- Agent (agent profiles)
- AgentProfile (extended profiles)
- AgentNote (agent memory)
- AgentLog (execution logs)

**Tables**:
- agents
- agent_profiles
- agent_notes
- agent_logs

**Services**:
- app/Services/Orchestration/AgentOrchestrationService.php

**Key Features**:
- Agent profiles
- Memory management
- Task execution
- Decision logging

---

### 13. Security & Audit
**Purpose**: Security policies, audit logs, and approval workflows

**Models**:
- SecurityPolicy (security rules)
- CommandAuditLog (command audit)
- ApprovalRequest (approval workflow)

**Tables**:
- security_policies
- command_audit_logs
- approval_requests

**Services**:
- app/Services/Security/PolicyRegistry.php
- app/Services/ApprovalManager.php

**Configuration**:
- config/security/* (security configs)

**Key Features**:
- Policy enforcement
- Audit trails
- Approval workflows
- Command validation

---

### 14. Bookmark & Link Management
**Purpose**: Manage bookmarks and external links

**Models**:
- Bookmark (saved bookmarks)
- Link (external links)

**Tables**:
- bookmarks
- links

**Commands**:
- app/Commands/LinkCommand.php

**Frontend**:
- resources/js/components/BookmarkListModal.tsx

**Key Features**:
- URL management
- Bookmark organization
- Link tracking

---

### 15. Todo System
**Purpose**: Personal task/todo management

**Models**:
- Todo (todo items)

**Tables**:
- todos

**Frontend**:
- resources/js/islands/chat/TodoManagementModal.tsx
- resources/js/widgets/todos/* (widget implementation)

**Key Features**:
- Todo CRUD
- Status tracking
- Widget display

---

### 16. Scheduling System
**Purpose**: Scheduled command execution

**Models**:
- Schedule (schedules)
- ScheduleRun (execution history)

**Tables**:
- schedules
- schedule_runs

**Jobs**:
- app/Jobs/RunScheduledCommandJob.php

**Controllers**:
- app/Http/Controllers/ScheduleController.php

**Key Features**:
- Cron-style scheduling
- Execution history
- Status tracking

---

### 17. File Management
**Purpose**: File uploads and metadata

**Models**:
- File (file metadata)

**Tables**:
- files

**Configuration**:
- config/filesystems.php

**Key Features**:
- File uploads
- Metadata storage
- Storage abstraction

---

### 18. User Management
**Purpose**: User authentication and authorization

**Models**:
- User (users)

**Tables**:
- users

**Configuration**:
- config/auth.php

**Key Features**:
- Authentication
- Authorization
- Profile management

---

## Supporting Systems

### 19. Project & Category Management
**Models**: Project, Category, Contact
**Purpose**: Organize content by projects and categories

### 20. Documentation System
**Models**: Documentation, Article
**Purpose**: Technical documentation management

### 21. Build System
**Models**: Build
**Purpose**: Track build processes

### 22. Meeting Management
**Models**: Meeting, CalendarEvent
**Purpose**: Meeting and calendar integration

### 23. Artifact Generation
**Models**: Artifact
**Purpose**: Generate and export artifacts

### 24. Query Management
**Models**: SavedQuery
**Purpose**: Reusable database queries

---

## System Dependencies

```
Fragments Engine (CAS)
  ├─> AI Provider Management (embeddings)
  ├─> Vault System (organization)
  └─> Ingestion Pipeline (content import)

Chat System
  ├─> AI Provider Management (LLM responses)
  ├─> Tool-Aware Orchestration (tool execution)
  ├─> Fragments Engine (context retrieval)
  └─> Memory System (agent memory)

Tool-Aware Orchestration
  ├─> MCP Integration (tool discovery)
  ├─> Command Execution (shell commands)
  ├─> Telemetry (observability)
  └─> Security & Audit (validation)

MCP Integration
  ├─> Memory System (memory tools)
  ├─> Orchestration PM (project tools)
  └─> Command Execution (command tools)

Telemetry
  └─> All Systems (cross-cutting)

Security & Audit
  └─> Command Execution (validation)
  └─> All Systems (auditing)
```

---

## Storage Systems

### Database Tables
- PostgreSQL with pgvector extension
- 60+ tables across all systems

### File Storage
- Configured via `config/filesystems.php`
- Supports local, S3, and other drivers

### Cache
- Redis/Memcached support
- Session storage
- Query caching

### Queue
- Laravel Horizon (Redis)
- Job processing
- Batch jobs

---

## Configuration Files

### Core
- config/app.php
- config/database.php
- config/cache.php
- config/queue.php

### AI & Orchestration
- config/fragments.php (main config)
- config/openai.php
- config/prism.php
- config/orchestration.php

### Telemetry
- config/llm-telemetry.php
- config/command-telemetry.php
- config/fragment-telemetry.php

### Security
- config/security/* (policies)
- config/auth.php

### MCP
- .mcp.json (server configs)

---

## Frontend Architecture

### Islands
- resources/js/islands/chat/* (main chat interface)

### Components
- resources/js/components/* (UI components)
  - orchestration/* (project management)
  - sidebar/* (navigation)
  - security/* (security dashboard)
  - ui/* (shared UI)

### Hooks
- resources/js/hooks/* (React hooks)
  - useChatSessions.ts
  - useModelSelection.ts
  - useTodoData.ts

### Widgets
- resources/js/widgets/* (dashboard widgets)

---

## Testing

### Test Structure
- tests/Feature/* (integration tests)
- tests/Unit/* (unit tests)
- tests/Pest.php (Pest configuration)

### Coverage Areas
- Fragment processing
- AI provider integration
- Tool-aware orchestration
- MCP integration
- Security policies
- Command execution

---

## Next Steps

1. **Consolidation Opportunities**:
   - Merge overlapping orchestration models (Sprint vs OrchestrationSprint)
   - Consolidate logging (SeerLog vs TelemetryEvent)
   - Review WorkItem vs OrchestrationTask duplication

2. **Module Organization**:
   - Core: Fragments, Chat, AI Providers
   - Optional: Orchestration, MCP, Agents
   - Dev Tools: Telemetry, Security, Audit

3. **Documentation Needs**:
   - API documentation
   - System interaction diagrams
   - Developer onboarding guide
   - Deployment guide
