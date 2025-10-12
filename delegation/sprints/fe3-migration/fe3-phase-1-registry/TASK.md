# Task: Phase 1 - Module Registry & Loader

**Task ID**: `fe3-phase-1-registry`  
**Sprint**: `fe3-migration`  
**Phase**: 1  
**Status**: Pending  
**Priority**: P0  
**Estimated Duration**: 2 weeks

---

## Objective

Implement the module registry and loader that will discover, validate, and manage the lifecycle of FE 3.0 modules.

---

## Context

After establishing core contracts in the previous task, we now need the infrastructure to discover and manage modules. The registry acts as the central hub for all module operations.

Reference:
- `delegation/Fragments Engine 3.0/ASSESSMENT_AND_PLAN.md` (Section: Phase 1)
- Previous task: `fe3-phase-1-contracts`

---

## Tasks

### 1. Module Registry Service
- [ ] Create `app/Core/Services/ModuleRegistry.php`
  - `register(ModuleDTO $module): void`
  - `get(string $id): ?ModuleDTO`
  - `all(): Collection<ModuleDTO>`
  - `enabled(): Collection<ModuleDTO>`
  - `enable(string $id): bool`
  - `disable(string $id): bool`
  - `has(string $id): bool`
  - Cache registered modules for performance

### 2. Module Loader Service
- [ ] Create `app/Core/Services/ModuleLoader.php`
  - `discover(): Collection<ModuleDTO>` - Scan paths for module.json
  - `load(string $path): ModuleDTO` - Load single module
  - `validate(ModuleDTO $module): bool` - Validate manifest
  - `getDependencies(ModuleDTO $module): array` - Resolve deps
  - Handle missing/invalid manifests gracefully

### 3. Module Validator
- [ ] Create `app/Core/Services/ModuleValidator.php`
  - `validateManifest(array $manifest): array` - JSON schema validation
  - `checkCompatibility(string $version): bool` - Engine version check
  - `checkDependencies(array $deps): array` - Dependency validation
  - Return validation errors with context

### 4. Service Provider Integration
- [ ] Update `app/Providers/FragmentsEngineServiceProvider.php`
  - Bind ModuleRegistry as singleton
  - Bind ModuleLoader as singleton
  - Auto-discover modules on boot if `FE3_AUTO_DISCOVER=true`
  - Register module service providers

### 5. Testing
- [ ] Create `tests/Unit/Core/Services/ModuleRegistryTest.php`
  - Test register/get/all/enabled
  - Test enable/disable
  - Test caching behavior
  
- [ ] Create `tests/Unit/Core/Services/ModuleLoaderTest.php`
  - Test discovery from multiple paths
  - Test loading valid manifests
  - Test handling invalid manifests
  - Test dependency resolution

- [ ] Create `tests/Unit/Core/Services/ModuleValidatorTest.php`
  - Test manifest schema validation
  - Test compatibility checks
  - Test dependency validation

### 6. Documentation
- [ ] Document module discovery process
- [ ] Document module lifecycle (register → enable → boot)
- [ ] Add examples for module.json validation

---

## Deliverables

1. **Module Registry**
   - ModuleRegistry service with caching

2. **Module Loader**
   - ModuleLoader service with auto-discovery

3. **Module Validator**
   - ModuleValidator service with JSON schema validation

4. **Service Provider**
   - Updated FragmentsEngineServiceProvider

5. **Tests**
   - 20+ Pest tests covering all services

6. **Documentation**
   - Module lifecycle documentation

---

## Acceptance Criteria

- ✅ ModuleRegistry can register, retrieve, enable, and disable modules
- ✅ ModuleLoader auto-discovers modules from configured paths
- ✅ ModuleValidator enforces engine compatibility constraints
- ✅ Service provider boots all enabled modules on application start
- ✅ All tests pass (composer test:unit -- --filter=Module)
- ✅ PSR-12 compliant code
- ✅ Comprehensive PHPDoc on all public methods

---

## Testing

### Manual Testing
```bash
# 1. Test module discovery
php artisan tinker
>>> app(ModuleLoader::class)->discover()

# 2. Test module registration
>>> $registry = app(ModuleRegistry::class)
>>> $registry->all()

# 3. Test enable/disable
>>> $registry->enable('test-module')
>>> $registry->disable('test-module')
```

### Automated Testing
```bash
composer test:unit -- --filter=Module
```

---

## Notes

- Use Laravel's cache facade for registry caching (60min TTL)
- Module discovery should scan: `config('engine.paths.modules')`
- Circular dependency detection not required yet (Phase 2)
- Invalid modules should log warnings but not crash

---

## Dependencies

- `fe3-phase-1-contracts` must be completed first (provides DTOs and interfaces)
- `fe3-phase-0-setup` must be completed (provides config/engine.php)

---

## References

- [Module System Spec](../../Fragments%20Engine%203.0/fragments_engine_v_3_spec_prd_adrs_and_quickstart.md#3-high-level-architecture)
- [ADR-001: Module-Based Architecture](../../../docs/fragments-engine-v3/adr/ADR-001-module-based-architecture.md)

---

## Status Updates

**Started**: [Date]  
**Progress**: [0/6 deliverables complete]  
**Blockers**: [None]  
**Completed**: [Date]

---

**Task Hash**: `300c5db40bb3c79c0813a137f5822b5621ac9ed028b1c88fa54f7902541e06b7`
