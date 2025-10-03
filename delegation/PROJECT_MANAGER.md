# Fragments Engine - Project Manager

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

## Notes

- All tasks follow established patterns from existing codebase
- Each task has dedicated agent pack in delegation/ folder
- Sub-agents will be used for complex domain-specific work
- No commits until user approval on working features
- Unified diffs preferred for all edits