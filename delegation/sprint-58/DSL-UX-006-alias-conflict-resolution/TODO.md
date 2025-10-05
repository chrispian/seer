# DSL-UX-006: Alias Conflict Resolution - TODO

## Prerequisites
- [ ] **DSL-UX-001 Complete**: Enhanced registry schema available
- [ ] **DSL-UX-002 Complete**: AutocompleteService ready for integration
- [ ] **Command pack loading**: Understand current CommandPackLoader implementation

## Phase 1: Conflict Detection Foundation (2 hours)

### 1.1 Create Database Schema (30 minutes)

#### Database Migration
**File**: `database/migrations/[timestamp]_create_command_alias_conflicts_table.php`

**Tasks**:
- [ ] **Create migration file**: Generate with `php artisan make:migration create_command_alias_conflicts_table`
- [ ] **Define schema**: Table structure with all required fields
  ```php
  Schema::create('command_alias_conflicts', function (Blueprint $table) {
      $table->id();
      $table->string('alias');
      $table->string('existing_command');
      $table->string('conflicting_command');
      $table->enum('conflict_type', ['alias_collision', 'alias_to_slug_collision', 'reserved_conflict']);
      $table->enum('resolution_action', ['auto_resolved', 'manual_override', 'pending'])->default('pending');
      $table->string('resolved_in_favor_of')->nullable();
      $table->timestamp('detected_at')->useCurrent();
      $table->timestamp('resolved_at')->nullable();
      $table->json('resolver_context')->nullable();
      $table->timestamps();
      
      $table->index(['alias']);
      $table->index(['resolution_action']);
      $table->index(['detected_at']);
  });
  ```
- [ ] **Test migration**: Run `php artisan migrate` and verify table creation
- [ ] **Add to seeder**: Include in database seeder if needed

#### Eloquent Model
**File**: `app/Models/CommandAliasConflict.php`

**Tasks**:
- [ ] **Create model**: `php artisan make:model CommandAliasConflict`
- [ ] **Add fillable fields**: All editable fields
- [ ] **Add casts**: DateTime and JSON field casting
- [ ] **Add helper methods**:
  ```php
  public function isResolved(): bool
  public function getWinnerCommand(): ?string
  public function isPending(): bool
  public function isAutoResolved(): bool
  public function isManualOverride(): bool
  ```
- [ ] **Add relationships**: If needed (to CommandRegistry)
- [ ] **Add scopes**: Common query patterns

### 1.2 Implement Conflict Detection Service (1 hour)

#### AliasConflictService Creation
**File**: `app/Services/AliasConflictService.php`

**Tasks**:
- [ ] **Create service class**: Basic class structure with constructor
- [ ] **Add dependency injection**: Inject required services/models
- [ ] **Register in service provider**: Add to AppServiceProvider if needed

#### Core Detection Methods
```php
public function validateAliases(array $newAliases, string $commandSlug, string $manifestPath): array
{
    // TODO: Implement conflict detection logic
}
```

**Implementation Tasks**:
- [ ] **Build alias ownership map**: Cache existing alias → command mappings
  ```php
  private function buildAliasMap(): void
  {
      // Query CommandRegistry and build $this->aliasOwnershipMap
  }
  ```
- [ ] **Detect alias collisions**: Check if new aliases conflict with existing ones
- [ ] **Detect alias-to-slug conflicts**: Check if aliases conflict with command slugs
- [ ] **Detect reserved conflicts**: Check against system reserved aliases
- [ ] **Priority calculation**: Determine command priorities based on manifest path
- [ ] **Conflict data structure**: Return standardized conflict information

#### Priority System Implementation
```php
private function getCommandPriority(string $manifestPath): int
{
    // TODO: Implement priority rules
}
```

**Tasks**:
- [ ] **System command detection**: Highest priority for system commands
- [ ] **Core command detection**: High priority for framework commands  
- [ ] **User command detection**: Medium priority for user commands
- [ ] **Generated command detection**: Lowest priority for auto-generated commands
- [ ] **Priority constants**: Define clear priority levels

#### Reserved Alias System
```php
private function isReservedAlias(string $alias): bool
private function isSystemCommand(string $slug, string $manifestPath): bool
```

**Tasks**:
- [ ] **Reserved alias list**: Define system reserved aliases (help, version, etc.)
- [ ] **System command detection**: Identify system vs user commands
- [ ] **Configuration**: Make reserved aliases configurable if needed

### 1.3 Implement Conflict Resolution Logic (30 minutes)

#### Resolution Engine
```php
public function resolveConflicts(array $conflicts): array
{
    // TODO: Implement automatic conflict resolution
}
```

**Implementation Tasks**:
- [ ] **Priority-based resolution**: Higher priority commands win conflicts
- [ ] **Alphabetical fallback**: Deterministic resolution for same-priority conflicts
- [ ] **Resolution actions**: Define transfer_alias, reject_alias actions
- [ ] **Resolution reasons**: Track why each conflict was resolved a certain way

#### Conflict Recording
```php
private function recordConflict(array $conflict, array $resolution): void
{
    // TODO: Store conflict and resolution in database
}
```

**Tasks**:
- [ ] **Database insertion**: Create CommandAliasConflict records
- [ ] **Resolution context**: Store detailed resolution information
- [ ] **Timestamp tracking**: Record detection and resolution times
- [ ] **Error handling**: Handle database insertion failures gracefully

## Phase 2: Integration with Command Loading (1-2 hours)

### 2.1 Enhance CommandPackLoader (1 hour)

#### Locate Current Implementation
**Tasks**:
- [ ] **Find CommandPackLoader**: Locate existing command pack loading service
- [ ] **Understand current flow**: Map how commands are currently loaded into registry
- [ ] **Identify injection points**: Where to add conflict detection
- [ ] **Backup current logic**: Ensure rollback capability

#### Integration Implementation
**File**: `app/Services/Commands/CommandPackLoader.php` (modifications)

**Tasks**:
- [ ] **Add dependency injection**: Inject AliasConflictService
- [ ] **Modify updateRegistryCache()**: Add conflict detection to loading process
  ```php
  public function updateRegistryCache(): array
  {
      // TODO: Add conflict detection and resolution
  }
  ```
- [ ] **Add conflict processing**: New method for handling commands with conflicts
  ```php
  private function processCommandWithConflictDetection(array $commandData, string $packPath): array
  {
      // TODO: Detect and resolve conflicts for individual commands
  }
  ```
- [ ] **Add resolution application**: Apply conflict resolutions to command data
  ```php
  private function applyResolutions(array $originalAliases, array $resolutions): array
  {
      // TODO: Modify aliases based on conflict resolutions
  }
  ```
- [ ] **Add alias removal**: Remove aliases from losing commands
  ```php
  private function removeAliasFromCommand(string $commandSlug, string $aliasToRemove): void
  {
      // TODO: Update existing command to remove conflicting alias
  }
  ```
- [ ] **Add logging**: Comprehensive logging of conflict detection and resolution
  ```php
  private function logRegistryRebuildSummary(array $summary): void
  {
      // TODO: Log rebuild summary with conflict information
  }
  ```

#### Testing Integration
**Tasks**:
- [ ] **Test with sample conflicts**: Create test scenarios with known conflicts
- [ ] **Verify conflict detection**: Ensure conflicts are properly detected
- [ ] **Verify resolution**: Ensure resolutions are applied correctly
- [ ] **Performance testing**: Ensure loading performance is acceptable
- [ ] **Rollback testing**: Ensure system can revert if needed

### 2.2 Update AutocompleteService (30 minutes)

#### Conflict-Aware Alias Resolution
**File**: `app/Services/AutocompleteService.php` (add methods)

**Tasks**:
- [ ] **Enhance resolveAlias()**: Check for conflict resolutions before normal lookup
  ```php
  public function resolveAlias(string $alias): ?string
  {
      // TODO: Check CommandAliasConflict table first, then normal resolution
  }
  ```
- [ ] **Add conflict query methods**: Helper methods for conflict information
  ```php
  public function getAliasConflicts(string $alias): Collection
  public function hasActiveConflicts(string $alias): bool
  ```
- [ ] **Add conflict caching**: Cache conflict resolutions for performance
- [ ] **Update alias ownership map**: Ensure map reflects conflict resolutions

#### Integration Testing
**Tasks**:
- [ ] **Test conflict resolution**: Verify aliases resolve to correct commands after conflicts
- [ ] **Test caching**: Ensure conflict resolution caching works
- [ ] **Performance testing**: Ensure alias resolution performance is maintained
- [ ] **Edge case testing**: Test with unresolved conflicts, missing commands, etc.

## Phase 3: Administrative Interface (1 hour)

### 3.1 Conflict Management API (30 minutes)

#### Controller Creation
**File**: `app/Http/Controllers/Admin/ConflictController.php`

**Tasks**:
- [ ] **Create controller**: `php artisan make:controller Admin/ConflictController --api`
- [ ] **Add index method**: List conflicts with filtering and pagination
  ```php
  public function index(Request $request): JsonResponse
  {
      // TODO: List conflicts with filters (status, alias, command)
  }
  ```
- [ ] **Add show method**: Get detailed conflict information
  ```php
  public function show(CommandAliasConflict $conflict): JsonResponse
  {
      // TODO: Return conflict details with related command information
  }
  ```
- [ ] **Add resolve method**: Manual conflict resolution
  ```php
  public function resolve(CommandAliasConflict $conflict, Request $request): JsonResponse
  {
      // TODO: Allow manual override of conflict resolution
  }
  ```
- [ ] **Add stats method**: Conflict statistics and analytics
  ```php
  public function stats(): JsonResponse
  {
      // TODO: Return conflict statistics for dashboard
  }
  ```

#### Helper Methods Implementation
**Tasks**:
- [ ] **Add transferAlias()**: Transfer alias between commands
- [ ] **Add removeAliasFromBothCommands()**: Remove alias entirely
- [ ] **Add validation**: Validate manual resolution requests
- [ ] **Add authorization**: Ensure only admin users can resolve conflicts
- [ ] **Add audit logging**: Log all manual resolutions

### 3.2 API Routes Configuration (15 minutes)

#### Route Definition
**File**: `routes/api.php` (add admin section)

**Tasks**:
- [ ] **Add admin route group**: Protected admin routes
  ```php
  Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
      // TODO: Add conflict management routes
  });
  ```
- [ ] **Add resource routes**: Standard CRUD routes for conflicts
- [ ] **Add custom routes**: Resolution and stats endpoints
- [ ] **Add middleware**: Authentication and authorization
- [ ] **Add rate limiting**: Prevent abuse of admin endpoints

#### Route Testing
**Tasks**:
- [ ] **Test authentication**: Verify admin middleware works
- [ ] **Test authorization**: Verify non-admin users are blocked
- [ ] **Test route resolution**: Verify all routes resolve correctly
- [ ] **Test parameter binding**: Verify model binding works for conflicts

### 3.3 Statistics and Analytics (15 minutes)

#### Statistics Implementation
**Add to ConflictController**:

**Tasks**:
- [ ] **Total conflict counts**: Count conflicts by status
- [ ] **Most conflicted aliases**: Identify problematic aliases
- [ ] **Recent conflict trends**: Show conflict patterns over time
- [ ] **Resolution success rates**: Track auto vs manual resolution effectiveness
- [ ] **Command conflict frequency**: Identify commands most involved in conflicts
- [ ] **Performance impact**: Track conflict detection overhead

#### Dashboard Data
**Tasks**:
- [ ] **Real-time stats**: Current conflict state
- [ ] **Historical trends**: Conflict patterns over time
- [ ] **Top conflicts**: Most problematic aliases and commands
- [ ] **Resolution effectiveness**: Success rates of different resolution strategies
- [ ] **System health**: Impact of conflicts on system performance

## Phase 4: Monitoring and Logging (30 minutes)

### 4.1 Enhanced Logging (15 minutes)

#### MetricsService Integration
**File**: `app/Services/MetricsService.php` (add methods)

**Tasks**:
- [ ] **Add recordAliasConflict()**: Log conflict detection events
  ```php
  public function recordAliasConflict(array $conflictData): void
  {
      // TODO: Log conflict with structured data
  }
  ```
- [ ] **Add recordConflictResolution()**: Log resolution events
  ```php
  public function recordConflictResolution(array $resolutionData): void
  {
      // TODO: Log resolution with outcome data
  }
  ```
- [ ] **Add metrics sending**: Send metrics to monitoring systems
- [ ] **Add performance tracking**: Track conflict detection overhead

#### Logging Integration
**Tasks**:
- [ ] **Integrate with CommandPackLoader**: Log conflicts during loading
- [ ] **Integrate with ConflictController**: Log manual resolutions
- [ ] **Add structured logging**: Use consistent log formats
- [ ] **Add error logging**: Log conflict detection/resolution failures
- [ ] **Add performance logging**: Track conflict processing times

### 4.2 Health Check Integration (15 minutes)

#### HealthController Enhancement
**File**: `app/Http/Controllers/HealthController.php` (add method)

**Tasks**:
- [ ] **Add checkAliasConflicts()**: Health check for conflict system
  ```php
  private function checkAliasConflicts(): array
  {
      // TODO: Check pending conflicts and system health
  }
  ```
- [ ] **Integrate with main health check**: Add to overall system health
- [ ] **Add status thresholds**: Define healthy/degraded/unhealthy states
- [ ] **Add monitoring metrics**: Include conflict health in monitoring

#### Alert Configuration
**Tasks**:
- [ ] **High pending conflict count**: Alert when too many unresolved conflicts
- [ ] **Conflict detection failures**: Alert on system errors
- [ ] **Resolution system failures**: Alert on resolution failures
- [ ] **Performance degradation**: Alert when conflict processing is slow

## Testing Implementation

### Unit Tests (1 hour)
**File**: `tests/Unit/AliasConflictServiceTest.php`

**Test Cases**:
- [ ] **test_detects_alias_collision()**: Basic alias conflict detection
- [ ] **test_detects_alias_to_slug_collision()**: Alias conflicts with command slugs
- [ ] **test_detects_reserved_alias_conflicts()**: System reserved alias conflicts
- [ ] **test_resolves_conflicts_by_priority()**: Priority-based conflict resolution
- [ ] **test_alphabetical_fallback_resolution()**: Same-priority conflict resolution
- [ ] **test_records_conflicts_in_database()**: Database recording functionality
- [ ] **test_builds_alias_map_correctly()**: Alias ownership mapping
- [ ] **test_handles_empty_aliases_gracefully()**: Edge case handling

### Integration Tests (30 minutes)
**File**: `tests/Feature/ConflictResolutionTest.php`

**Test Cases**:
- [ ] **test_command_loading_handles_conflicts()**: End-to-end conflict resolution
- [ ] **test_autocomplete_respects_conflict_resolution()**: Alias resolution integration
- [ ] **test_admin_can_override_conflict_resolution()**: Manual resolution functionality
- [ ] **test_conflict_api_endpoints_work()**: API endpoint testing
- [ ] **test_health_check_includes_conflicts()**: Health check integration
- [ ] **test_metrics_track_conflicts()**: Monitoring integration

### Performance Tests (30 minutes)
**File**: `tests/Performance/ConflictPerformanceTest.php`

**Test Cases**:
- [ ] **test_conflict_detection_performance()**: Overhead measurement
- [ ] **test_large_registry_conflict_detection()**: Scalability testing
- [ ] **test_alias_resolution_performance()**: Resolution speed testing
- [ ] **test_memory_usage_during_conflict_processing()**: Memory efficiency

## Quality Assurance

### Code Review Checklist
- [ ] **Conflict detection accuracy**: All conflict types properly detected
- [ ] **Resolution determinism**: Same conflicts always resolve the same way
- [ ] **Performance impact**: Minimal overhead added to command loading
- [ ] **Error handling**: Graceful handling of all failure scenarios
- [ ] **Security**: Proper authorization for administrative functions

### Performance Validation
- [ ] **Detection overhead <10%**: Conflict detection doesn't significantly slow loading
- [ ] **Resolution speed <50ms**: Alias resolution remains fast with conflict checking
- [ ] **Memory efficiency**: Conflict system doesn't significantly increase memory usage
- [ ] **Database performance**: Conflict queries are optimized and indexed

### Functional Validation
- [ ] **Zero silent conflicts**: All conflicts detected and logged
- [ ] **Deterministic resolution**: Predictable conflict outcomes
- [ ] **Admin override capability**: Administrators can resolve any conflict
- [ ] **Complete audit trail**: All conflicts and resolutions tracked

## Success Criteria Validation

### Functional Requirements
- [ ] **Detect all conflict types**: Alias collisions, slug conflicts, reserved conflicts
- [ ] **Automatic resolution**: Priority-based resolution with fallback rules
- [ ] **Administrative control**: Full manual override capabilities
- [ ] **Complete history**: Audit trail of all conflicts and resolutions

### Performance Requirements
- [ ] **Loading overhead <10%**: Conflict detection doesn't significantly impact performance
- [ ] **Resolution speed <50ms**: Alias resolution remains fast
- [ ] **Admin interface <2s**: Conflict management interface loads quickly

### Quality Requirements
- [ ] **Zero silent failures**: All conflicts detected and addressed
- [ ] **Deterministic behavior**: Consistent conflict resolution outcomes
- [ ] **Complete monitoring**: Full observability of conflict system health
- [ ] **Audit compliance**: Complete tracking of all administrative actions

This comprehensive TODO ensures robust alias conflict management with proper administrative oversight and system monitoring.