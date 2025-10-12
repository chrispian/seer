# Task: Phase 0 - Foundation & Planning

**Task ID**: `fe3-phase-0-setup`  
**Sprint**: `fe3-migration`  
**Phase**: 0  
**Status**: Pending  
**Priority**: P0  
**Estimated Duration**: 2 weeks

---

## Objective

Establish FE 3.0 infrastructure and documentation scaffolding. This task creates the foundation for all subsequent phases.

---

## Context

This is the first task in the Fragments Engine 3.0 migration sprint. Before implementing any functionality, we need:
1. Clear architectural decision records (ADRs)
2. Configuration structure
3. Database schema planning
4. Documentation organization
5. Feature flags for gradual rollout

Reference:
- `delegation/Fragments Engine 3.0/ASSESSMENT_AND_PLAN.md` (Section: Phase 0)
- `delegation/Fragments Engine 3.0/fragments_engine_v_3_spec_prd_adrs_and_quickstart.md` (Section 15: ADRs)

---

## Tasks

### 1. Documentation Structure
- [ ] Create `docs/fragments-engine-v3/` directory
- [ ] Create subdirectories:
  - `docs/fragments-engine-v3/adr/`
  - `docs/fragments-engine-v3/modules/`
  - `docs/fragments-engine-v3/ui-dsl/`
  - `docs/fragments-engine-v3/agents/`
  - `docs/fragments-engine-v3/prompts/`
  - `docs/fragments-engine-v3/rules/`
  - `docs/fragments-engine-v3/flows/`
  - `docs/fragments-engine-v3/observability/`
  - `docs/fragments-engine-v3/scaffolding/`
- [ ] Create `docs/fragments-engine-v3/README.md` (index/overview)
- [ ] Create `docs/fragments-engine-v3/adr/README.md` (ADR index template)

### 2. ADR Documents
- [ ] Write `docs/fragments-engine-v3/adr/ADR-001-module-based-architecture.md`
  - Context: Why modules over pure domain layers
  - Decision: Module-based with manifests
  - Consequences: Isolation, versioning, slight duplication
  - Alternatives: Monolithic, service-oriented

- [ ] Write `docs/fragments-engine-v3/adr/ADR-002-fluent-php-json-ui-contracts.md`
  - Context: Need for typed UI definitions
  - Decision: Fluent PHP → JSON → React
  - Consequences: Build step, schema tests needed
  - Alternatives: JSX-only, Laravel Inertia

- [ ] Write `docs/fragments-engine-v3/adr/ADR-003-command-router-single-entry.md`
  - Context: UI actions scattered across controllers
  - Decision: All actions route through Command Router
  - Consequences: Slight boilerplate, huge observability win
  - Alternatives: Direct controller calls, event sourcing

- [ ] Write `docs/fragments-engine-v3/adr/ADR-004-hash-pinning-artifacts.md`
  - Context: Need for reproducibility and dedupe
  - Decision: Hash all configs/prompts/layouts/templates
  - Consequences: Migration path needed on schema changes
  - Alternatives: Version numbers only, no pinning

### 3. Configuration Files
- [ ] Create `config/engine.php` with sections:
  ```php
  return [
      'enabled' => env('FE3_ENABLED', false),
      'version' => '3.0.0',
      
      'paths' => [
          'modules' => base_path('modules'),
          'core' => app_path('Core'),
          'templates' => resource_path('templates/fe3'),
      ],
      
      'registry' => [
          'auto_discover' => env('FE3_AUTO_DISCOVER', true),
          'cache_ttl' => env('FE3_CACHE_TTL', 3600),
      ],
      
      'safety_rails' => [
          'fs_scope' => storage_path('fe3/artifacts'),
          'timeouts' => [
              'action_seconds' => 90,
              'plan_seconds' => 60,
          ],
      ],
      
      'telemetry' => [
          'enabled' => env('FE3_TELEMETRY_ENABLED', true),
          'correlation_ids' => true,
      ],
  ];
  ```

### 4. Database Migrations
- [ ] Create migration: `create_modules_table`
  ```php
  Schema::create('modules', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('slug')->unique();
      $table->string('name');
      $table->text('description')->nullable();
      $table->string('version', 50);
      $table->boolean('enabled')->default(true);
      $table->json('config')->nullable();
      $table->timestamps();
  });
  ```

- [ ] Create migration: `create_module_types_table`
  ```php
  Schema::create('module_types', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('module_id')->constrained()->cascadeOnDelete();
      $table->string('slug');
      $table->string('label');
      $table->string('plural_label');
      $table->json('schema')->nullable();
      $table->json('ui_config')->nullable();
      $table->timestamps();
      $table->unique(['module_id', 'slug']);
  });
  ```

- [ ] Create migration: `create_module_commands_table`
  ```php
  Schema::create('module_commands', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('module_id')->constrained()->cascadeOnDelete();
      $table->string('command')->unique();
      $table->string('handler_class');
      $table->text('description')->nullable();
      $table->json('navigation_config')->nullable();
      $table->json('permissions')->nullable();
      $table->timestamps();
  });
  ```

- [ ] Create migration: `create_module_permissions_table`
  ```php
  Schema::create('module_permissions', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('module_id')->constrained()->cascadeOnDelete();
      $table->string('ability');
      $table->text('description')->nullable();
      $table->timestamps();
      $table->unique(['module_id', 'ability']);
  });
  ```

### 5. Feature Flags
- [ ] Add to `.env.example`:
  ```
  # Fragments Engine 3.0
  FE3_ENABLED=false
  FE3_AUTO_DISCOVER=true
  FE3_CACHE_TTL=3600
  FE3_TELEMETRY_ENABLED=true
  ```

### 6. Directory Structure
- [ ] Create `app/Core/` directory (placeholder for Phase 1)
- [ ] Create `modules/` directory (placeholder for Phase 3)
- [ ] Create `resources/templates/fe3/` directory (placeholder for Phase 10)

---

## Deliverables

1. **ADR Documents** (4)
   - ADR-001: Module-Based Architecture
   - ADR-002: Fluent PHP → JSON UI Contracts
   - ADR-003: Command Router as Single Entry
   - ADR-004: Hash Pinning for Artifacts

2. **Config File**
   - `config/engine.php` with documented structure

3. **Database Migrations** (4)
   - `modules` table
   - `module_types` table
   - `module_commands` table
   - `module_permissions` table

4. **Documentation Structure**
   - `docs/fragments-engine-v3/` with 9 subdirectories
   - README.md files for navigation

5. **Feature Flags**
   - `.env.example` entries for FE3

---

## Acceptance Criteria

- ✅ All 4 ADRs written following template (Context, Decision, Consequences, Alternatives)
- ✅ `config/engine.php` exists with all documented sections
- ✅ 4 migration files created (not yet run - awaiting review)
- ✅ Documentation directory structure complete with README files
- ✅ `FE3_ENABLED` feature flag available in `.env.example`
- ✅ `app/Core/`, `modules/`, `resources/templates/fe3/` directories created
- ✅ All files follow coding standards (PSR-12 for PHP, proper indentation)

---

## Testing

### Manual Testing
1. Verify directory structure exists
2. Check migration syntax with `php artisan migrate:status`
3. Verify config loads: `php artisan config:show engine`
4. Review ADRs for completeness and clarity

### Automated Testing
- Not applicable for Phase 0 (scaffolding only)

---

## Notes

- **Do NOT run migrations yet** - wait for Phase 1 to ensure schema is correct
- **Do NOT implement functionality** - this is foundation only
- Keep ADRs concise (1-2 pages each)
- Config should be self-documenting with comments
- Follow existing project conventions (see CLAUDE.md)

---

## Dependencies

- None (this is the first task)

---

## References

- `delegation/Fragments Engine 3.0/ASSESSMENT_AND_PLAN.md`
- `delegation/Fragments Engine 3.0/fragments_engine_v_3_spec_prd_adrs_and_quickstart.md`
- Existing ADRs in `docs/adr/` for template examples

---

## Status Updates

<!-- Agent: Update this section as you progress -->

**Started**: [Date]  
**Progress**: [X/6 deliverables complete]  
**Blockers**: [None/List any issues]  
**Completed**: [Date]

---

**Task Hash**: `eea01496721b4e1895e4718de0bd5f85633703aaec2141db5f881e6b3749c7b1`
