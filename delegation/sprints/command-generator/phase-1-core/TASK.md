# Task: Phase 1: Core Generator Framework

**Task ID**: `phase-1-core`  
**Sprint**: `command-generator`  
**Phase**: 1  
**Status**: Pending  
**Priority**: P0  
**Estimated Duration**: 2 weeks

---

## Objective

Build the foundational command generator framework that can create slash command modules from configuration files. This includes the main artisan command, config schema, and core generator infrastructure.

---

## Context

The current system requires 2-3 hours of manual work to create a new slash command module (handler, modal, seeder entry, frontend registration). This task creates a generator that reduces that to 10 minutes.

**Why This Matters**:
- Standardizes command creation
- Makes migrating existing commands easier  
- Reduces human error in wiring
- Enables rapid prototyping

Reference:
- `delegation/tasks/COMMAND-GENERATOR-SYSTEM.md` - Complete specification
- `docs/NAVIGATION_SYSTEM_COMPLETE_GUIDE.md` - System architecture
- `app/Commands/Orchestration/Sprint/ListCommand.php` - Gold standard example

---

## Tasks

### 1. Create Config Schema
- [ ] Create `config/command-generator.php`
- [ ] Define all config sections
- [ ] Add inline documentation
- [ ] Create example: `config/sprint-module-example.json`

### 2. Create Main Generator Command
- [ ] Create `app/Console/Commands/MakeCommandModule.php`
- [ ] Implement signature with options (--config, --pattern, --dry-run, --force)
- [ ] Add config validation
- [ ] Add generator orchestration
- [ ] Add progress reporting
- [ ] Add dry-run mode

### 3. Create Generator Base Classes
- [ ] Create `app/Console/Commands/Generators/` directory
- [ ] Create `BaseGenerator.php` with utilities
- [ ] Create `GeneratorConfig.php` for parsing

### 4. Create Stub System
- [ ] Create stub directories (command/, modal/, config/)
- [ ] Document stub variable syntax
- [ ] Create stub loader utility

### 5. Testing Infrastructure
- [ ] Create test fixtures
- [ ] Write config validation tests
- [ ] Write stub rendering tests

---

## Deliverables

1. **Config Schema**
   - `config/command-generator.php` with full documentation
   - `config/sprint-module-example.json` working example

2. **Main Command**
   - `app/Console/Commands/MakeCommandModule.php` fully functional
   - Options: --config, --pattern, --dry-run, --force

3. **Generator Base**
   - `BaseGenerator.php` with reusable utilities
   - `GeneratorConfig.php` for config management

4. **Stub System**
   - Directory structure with initial stubs
   - Documentation on creating stubs

5. **Tests**
   - Config validation tests
   - Stub rendering tests

---

## Acceptance Criteria

- ✅ Can run `php artisan make:command-module sprint --config=... --dry-run`
- ✅ Config validation catches invalid schemas
- ✅ BaseGenerator renders stubs with variables
- ✅ GeneratorConfig parses JSON and applies defaults
- ✅ All tests pass (composer test:unit -- --filter=Generator)
- ✅ Code follows PSR-12 standards
- ✅ Documentation complete for all classes

---

## Testing

### Manual Testing
```bash
# 1. Test dry-run mode
php artisan make:command-module sprint --config=config/sprint-module-example.json --dry-run

# 2. Test validation
php artisan make:command-module invalid --config=config/invalid.json
# Should show validation errors
```

### Automated Testing
```bash
# Run all generator tests
composer test:unit -- --filter=Generator

# Specific suites
composer test:unit -- --filter=ConfigValidation
composer test:unit -- --filter=StubRendering
```

**Expected Results**: Dry-run shows file list, validation catches errors, tests green

---

## Notes

- **JSON format**: Easier to read/write than PHP arrays
- **Sensible defaults**: Minimize required config fields
- **Pattern-based**: Three patterns (direct, wrapper, custom)
- **Dry-run first**: Always test before generating
- **No destructive ops**: Never delete existing files

---

## Dependencies

- None (this is the foundation task)

---

## References

- **Spec**: `delegation/tasks/COMMAND-GENERATOR-SYSTEM.md`
- **Architecture**: `docs/NAVIGATION_SYSTEM_COMPLETE_GUIDE.md`
- **Example Handler**: `app/Commands/Orchestration/Sprint/ListCommand.php`
- **Example Modal**: `resources/js/components/orchestration/SprintListModal.tsx`
- **Seeder**: `database/seeders/CommandsSeeder.php`

---

## Status Updates

<!-- Agent: Update this section as you progress -->

**Started**: [Date]  
**Progress**: [X/Y deliverables complete]  
**Blockers**: [None/List any issues]  
**Completed**: [Date]

---

**Task Hash**: `7fa80122288479292506c67f005393d5dabb27ca0b3808a12abac554a3ca5646`
