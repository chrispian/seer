# FIX-TESTS-001: Test Suite Recovery After Agent Migration Failure

## Context
Recent agent migration caused catastrophic test failures across the entire test suite. Tests are failing due to missing dependencies, configuration issues, and incomplete migration artifacts. This task groups failing tests by root cause to enable systematic fixes.

## Problem Groups & Root Causes

### 1. **Import/Use Statement Issues**
**Affected Tests:** `CacheChatSessionTest`, `ToolTelemetryTest`
**Root Cause:** Unused `ReflectionClass` imports causing warnings
**Impact:** Minor warnings, but indicate sloppy code
**Fix:** Remove unused imports

### 2. **Database/Model Schema Issues**
**Affected Tests:**
- `AgentOrchestrationSchemaTest` (6 tests)
- `FragmentTest` (2 tests)
- `FragmentDeletionTest` (3 tests)
- `FragmentIdempotencyTest` (8 tests)
- `FragmentProcessingPipelineTest` (8 tests)
- `FragmentRecallIntegrationTest` (6 tests)

**Root Cause:** Database schema changes during migration not reflected in test setup
**Impact:** Core fragment functionality broken
**Fix:** Update test database schema and model relationships

### 3. **AI/Model Selection Service Failures**
**Affected Tests:**
- `ModelSelectionIntegrationTest` (10 tests)
- `ModelSelectionServiceTest` (18 tests)
- `DeterministicControlsTest` (10 tests)
- `OperationSpecificProviderTest` (8 tests)

**Root Cause:** Model selection service configuration broken after migration
**Impact:** AI functionality completely broken
**Fix:** Restore model selection service configuration and provider mappings

### 4. **Vector/Embedding System Failures**
**Affected Tests:**
- `VectorMigrationTest` (6 tests)
- `EmbeddingStoreManagerTest` (2 tests)
- `SqliteVectorStoreTest` (8 tests)
- `EmbedFragmentJobTest` (4 tests)
- `EmbeddingsBackfillCommandTest` (6 tests)
- `EmbeddingsToggleFeatureTest` (6 tests)

**Root Cause:** Vector extension dependencies and embedding configuration broken
**Impact:** Search and embedding features disabled
**Fix:** Restore vector extension setup and embedding service configuration

### 5. **Orchestration System Failures**
**Affected Tests:**
- `PostmasterWorkflowTest` (5 tests)
- `MessagingApiTest` (5 tests)
- `ArtifactsApiTest` (5 tests)

**Root Cause:** Orchestration services and messaging system not properly configured
**Impact:** Agent orchestration and messaging broken
**Fix:** Restore orchestration service configuration and database setup

### 6. **Provider/API Integration Failures**
**Affected Tests:**
- `ProviderApiTest` (13 tests)
- `ProviderStreamingTest` (6 tests)
- `StreamChatProviderTest` (4 tests)
- `StreamingActionsTest` (12 tests)

**Root Cause:** AI provider configurations and streaming setup broken
**Impact:** External AI provider integrations broken
**Fix:** Restore provider configurations and API credentials setup

### 7. **Cost Calculation & Action Failures**
**Affected Tests:**
- `CostCalculationTest` (8 tests)
- `CacheChatSessionTest` (5 tests)
- `StreamingActionsTest` (12 tests)

**Root Cause:** Cost calculation service and action caching broken
**Impact:** Billing and usage tracking broken
**Fix:** Restore cost calculation formulas and caching mechanisms

### 8. **Command & Console Interface Failures**
**Affected Tests:**
- `ComposeCommandTest` (6 tests)
- `ContextCommandTest` (4 tests)
- `InboxCommandTest` (5 tests)
- `ProjectCommandTest` (5 tests)
- `VaultCommandTest` (9 tests)
- `ObsidianSyncCommandTest` (7 tests)
- `ReadwiseSyncCommandTest` (1 test)
- `ChatGptImportCommandTest` (2 test)

**Root Cause:** Command architecture changes not reflected in tests
**Impact:** CLI functionality broken
**Fix:** Update command test expectations and mock setups

### 9. **File Upload & Processing Failures**
**Affected Tests:**
- `FileUploadTest` (5 tests)
- `ChatPromptIngestionTest` (6 tests)
- `ConversationTrackingTest` (6 tests)

**Root Cause:** File processing pipeline and chat ingestion broken
**Impact:** File uploads and chat processing broken
**Fix:** Restore file processing pipeline and chat ingestion services

### 10. **DSL & Step Processing Failures**
**Affected Tests:**
- `ModelCreateStepTest` (12 tests)
- `ModelQueryStepTest` (13 tests)
- `CriticalFixesTest` (5 tests)

**Root Cause:** DSL step processing and model operations broken
**Impact:** Dynamic model operations broken
**Fix:** Restore DSL step implementations and model operation handlers

### 11. **Service Integration Failures**
**Affected Tests:**
- `DelegationMigrationServiceTest` (3 tests)
- `ReadwiseImportServiceTest` (2 tests)
- `TaskContentServiceTest` (6 tests)
- `LogRecallDecisionTest` (4 tests)
- `LinkResolverTest` (7 tests)
- `ObsidianLinksIntegrationTest` (5 tests)

**Root Cause:** Service integrations and import processes broken
**Impact:** Data import and external service integrations broken
**Fix:** Restore service configurations and integration points

### 12. **Configuration & Settings Failures**
**Affected Tests:**
- `SettingsServiceTest` (9 tests)
- `ToastServiceTest` (8 tests)
- `JsonMetadataExtractionTest` (6 tests)
- `MemoryWriteToolTest` (6 tests)

**Root Cause:** Configuration services and tool implementations broken
**Impact:** Application settings and tool functionality broken
**Fix:** Restore configuration services and tool implementations

## Recovery Strategy

### Phase 1: Infrastructure Fixes (High Priority)
1. Fix database schema and model relationships
2. Restore basic service configurations
3. Fix import/use statement issues

### Phase 2: Core Service Recovery (High Priority)
1. Restore model selection service
2. Fix vector/embedding system
3. Restore orchestration services

### Phase 3: Integration Recovery (Medium Priority)
1. Fix provider integrations
2. Restore command interfaces
3. Fix file processing pipeline

### Phase 4: Feature Recovery (Low Priority)
1. Restore DSL operations
2. Fix remaining service integrations
3. Clean up configuration issues

## Success Criteria
- All tests pass without errors
- Core functionality (fragments, search, AI) works
- CLI commands functional
- External integrations restored
- No critical security issues introduced

## Risk Assessment
- **High Risk:** Database corruption from failed migration
- **Medium Risk:** Configuration drift causing inconsistent behavior
- **Low Risk:** Test-only issues that don't affect production

## Dependencies
- Database backup/restore capabilities
- Access to migration logs
- Understanding of agent migration changes
- Team coordination for phased rollout</content>
</xai:function_call/>
<xai:function_call name="read">
<parameter name="filePath">delegation/FIX-TESTS-001.md