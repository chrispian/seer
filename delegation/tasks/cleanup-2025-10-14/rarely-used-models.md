# Rarely Used Models - Review Needed

## Models with 1 Reference (Need Decision)

### AgentDecision
- **References**: 1 (DbQueryTool.php)
- **Purpose**: Store agent reasoning decisions
- **Status**: Part of agent memory system but only queried by DB tool
- **Recommendation**: Keep - part of agent memory architecture, likely to grow

### AgentLog
- **References**: 1 (AgentLogImportService.php)
- **Purpose**: Import agent logs from external sources
- **Status**: Import-only feature
- **Recommendation**: Review if import feature is still needed

### Article
- **References**: 1 (ObsidianFragmentPipelineTest.php - test only)
- **Purpose**: Store article content
- **Status**: Only used in tests, not production code
- **Recommendation**: Remove or integrate properly

### OrchestrationBug
- **References**: 1 (OrchestrationBugService.php)
- **Purpose**: Track bugs in orchestration system
- **Status**: Service exists but feature incomplete
- **Recommendation**: Complete integration or remove

### SavedQuery
- **References**: 1 (DbQueryTool.php)
- **Purpose**: Store reusable database queries
- **Status**: Only used by DB tool
- **Recommendation**: Keep - useful for query management

### SecurityPolicy
- **References**: 1 (PolicyRegistry.php)
- **Purpose**: Store security policies
- **Status**: Policy system partially implemented
- **Recommendation**: Keep - security feature

### SessionContextHistory
- **References**: 1 (SessionManager.php)
- **Purpose**: Track session context changes
- **Status**: Used for context management
- **Recommendation**: Keep - core feature

## Models with 2 References

### FragmentLink
- **References**: 2 (ObsidianLinksIntegrationTest.php, ObsidianImportService.php)
- **Purpose**: Links between fragments (Obsidian wiki-style)
- **Status**: Obsidian integration feature
- **Recommendation**: Keep - active feature

### ScheduleRun
- **References**: 2 (ScheduleController.php, RunScheduledCommandJob.php)
- **Purpose**: Track scheduled command executions
- **Status**: Active scheduling feature
- **Recommendation**: Keep - core feature

### SeerLog
- **References**: 2 (FragmentTest.php, SeerLogController.php)
- **Purpose**: Application logging
- **Status**: Logging system
- **Recommendation**: Keep or consolidate with TelemetryEvent

## Models with 3-5 References (Borderline)

### Contact (3 refs)
- Used in: ToolAwareGuardsTest, ContactController, AutocompleteController
- **Recommendation**: Keep - CRM feature

### SessionActivity (3 refs)
- Used in: SessionManager, TimeTrackingService, TaskCompletionValidator
- **Recommendation**: Keep - time tracking

### SprintItem (3 refs)
- Used in: DelegationMigrationService, DbQueryTool, SprintOrchestrationService
- **Recommendation**: Keep - orchestration feature

### Telemetry Models (3-4 refs each)
- TelemetryCorrelationChain
- TelemetryHealthCheck
- TelemetryPerformanceSnapshot
- TelemetryMetric
- **Recommendation**: Keep all - comprehensive telemetry system

### ToolDefinition (3 refs)
- Used in: SyncMcpTools, ToolSelector, RefreshMcpToolsJob
- **Recommendation**: Keep - MCP integration

### AgentNote (4 refs)
- Used in memory tools and tests
- **Recommendation**: Keep - agent memory system

### CommandAuditLog (4 refs)
- Used in audit system
- **Recommendation**: Keep - security/compliance

### TaskAssignment (4 refs)
- Used in orchestration services
- **Recommendation**: Keep - task management

### ApprovalRequest (5 refs)
- Active feature with API routes
- **Recommendation**: Keep

### Artifact (5 refs)
- Used in export/generation features
- **Recommendation**: Keep

### Category (5 refs)
- Fragment categorization
- **Recommendation**: Keep

### Documentation (5 refs)
- Documentation management
- **Recommendation**: Keep

### Link (5 refs)
- URL/link management
- **Recommendation**: Keep

### TelemetryEvent (5 refs)
- Core telemetry
- **Recommendation**: Keep

### VaultRoutingRule (5 refs)
- Vault routing logic
- **Recommendation**: Keep

## Summary

**Safe to Remove (need confirmation)**:
- Article (test-only)
- AgentLog (if import not needed)
- OrchestrationBug (incomplete feature)

**All Others**: Keep - they're part of active or foundational features
