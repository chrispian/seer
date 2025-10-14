# Cleanup Opportunities

## 1. Duplicate/Overlapping Models

### Orchestration Models
**Issue**: Multiple overlapping models for tasks and sprints
- `OrchestrationTask` vs `WorkItem` - Both represent work items
- `OrchestrationSprint` vs `Sprint` - Two sprint implementations
- `TaskActivity` + `TaskAssignment` vs unified approach

**Recommendation**: 
- Consolidate around `OrchestrationTask` and `OrchestrationSprint` (more complete)
- Migrate `WorkItem` data if needed
- Remove legacy `Sprint`, `SprintItem` models

### Logging Models
**Issue**: Multiple logging systems
- `SeerLog` - Application logs
- `TelemetryEvent` - Telemetry events
- `CommandAuditLog` - Command audit

**Recommendation**:
- Keep separate (different purposes)
- OR consolidate SeerLog into TelemetryEvent if functionality overlaps

---

## 2. Unused/Empty Models (Already Removed)
✅ **Completed**: Moved 9 unused models to backup:
- AgentVector
- ArticleFragment
- CalendarEvent
- FileText
- FragmentTag
- ObjectType
- PromptEntry
- Thumbnail
- WorkItemEvent

---

## 3. Test-Only Models

### Article Model
- Only used in `ObsidianFragmentPipelineTest.php`
- Not used in production code
- **Recommendation**: Remove or integrate into fragment system

---

## 4. Incomplete Features

### OrchestrationBug
- Model exists with service
- Only 1 reference (OrchestrationBugService.php)
- No routes, no UI, no actual usage
- **Recommendation**: Complete implementation or remove

### PromptEntry (Already Removed)
✅ Removed - was incomplete prompt template system

---

## 5. Migration Cleanup

### Tables Without Models (Need Verification)
Check for orphaned migrations:
```bash
# Find tables in migrations not matching any model
comm -23 <(grep -h "Schema::create" database/migrations/*.php | sort) <(ls app/Models/*.php | sort)
```

**Action**: Create list of migrations that might be orphaned

---

## 6. Unused Routes

### API Routes
Search for routes without controllers or usage:
- Check `routes/api.php`
- Check `routes/web.php`
- Verify each route has active usage

**Action**: Audit all route definitions

---

## 7. Dead Code in Services

### Services to Review
- `app/Services/Ingestion/AgentLogImportService.php` - Only used once, import feature complete?
- Services with single-use methods
- Services with TODO comments

**Action**: Search for TODO, FIXME, HACK comments:
```bash
grep -r "TODO\|FIXME\|HACK" app/Services/
```

---

## 8. Unused Configuration

### Config Files
- Review each config file for unused keys
- Check for deprecated settings
- Consolidate similar configs

**Files to Review**:
- config/fragments.php - Large config, may have unused keys
- config/orchestration.php
- config/metrics.php

---

## 9. Orphaned Views

### Blade Templates
Check `resources/views/` for unused templates:
- Views not referenced in controllers
- Views not included by other views
- Legacy templates

**Action**: 
```bash
# Find blade files
find resources/views -name "*.blade.php"
# Check each for usage
```

---

## 10. Duplicate Functionality

### Fragment Import
- `ObsidianImportService`
- `DocumentationImportService`
- `AgentLogImportService`

**Question**: Can these share a common base class or interface?

**Recommendation**: Create `AbstractImportService` with shared logic

### Time Tracking
- `SessionActivity` - Session time tracking
- `WorkSession` - Work session tracking
- `TaskActivity` - Task activity tracking

**Question**: Are all three needed or can they be consolidated?

---

## 11. Frontend Cleanup

### TypeScript Errors (Pre-existing)
- 77 TypeScript errors remain (not from our work)
- Many unused imports in modal components
- Deprecated `ElementRef` usage

**Priority Issues**:
1. `ChatToolbar.tsx` - Missing `CompactProjectPicker` module
2. Unused React imports in 10+ files
3. Unused icon imports in orchestration modals
4. Deprecated ElementRef in command.tsx

### Unused Components
Search for:
- Components not imported anywhere
- Commented-out components
- WIP components

---

## 12. Documentation Cleanup

### Docs Folder
- `docs/` has 73+ markdown files
- Some may be outdated
- Some may duplicate information

**Recommendation**:
- Review each doc for currency
- Move outdated docs to `docs/archive/`
- Create index/table of contents

### Delegation Folder
- `delegation/` has extensive task tracking
- Many archived items
- Some overlap with docs/

**Recommendation**:
- Clean up completed tasks
- Archive old sprints
- Consolidate overlapping content

---

## 13. Test Coverage

### Missing Tests
Identify important code without tests:
- Critical services
- Complex orchestration logic
- Security features

**Action**: Run coverage report:
```bash
composer test:coverage
```

### Obsolete Tests
- Tests for removed features
- Tests for unused code
- Duplicate test scenarios

---

## 14. Database Cleanup

### Unused Indexes
- Review indexes on rarely-queried tables
- Check for duplicate indexes

### Unused Columns
- Review migration history for added columns never used
- Check for nullable columns that are always null

**Action**: Query database for column usage statistics

---

## 15. Dependency Cleanup

### Composer Dependencies
Review `composer.json`:
- Unused packages
- Outdated versions
- Development-only packages in production

**Action**:
```bash
composer show --unused
composer outdated
```

### NPM Dependencies
Review `package.json`:
- Unused frontend packages
- Outdated versions
- Duplicate functionality

**Action**:
```bash
npm ls --depth=0
npm outdated
```

---

## 16. Code Style Issues

### Formatting
- Run PHP CS Fixer on all files
- Run ESLint on frontend files
- Ensure consistent code style

### Naming Conventions
- Review inconsistent naming
- Ensure PSR compliance
- Standardize frontend naming

---

## 17. Security Review

### Exposed Secrets
- Search for hardcoded credentials
- Check for leaked API keys
- Review .env.example

### SQL Injection
- Review raw queries
- Ensure proper parameterization
- Check user input handling

### Command Injection
- Review shell command execution
- Verify whitelist enforcement
- Check input sanitization

---

## 18. Performance Opportunities

### N+1 Queries
- Review model relationships
- Add eager loading where needed
- Check for missing indexes

### Cache Optimization
- Identify frequently-queried data
- Add caching where appropriate
- Review cache invalidation

### Asset Optimization
- Minify JS/CSS
- Optimize images
- Review bundle sizes

---

## Priority Order

### Phase 1: Safe Removals (Done)
✅ Unused models removed (9 models)
✅ Migrations backed up

### Phase 2: Model Consolidation
1. Review Article model - remove or integrate
2. Decide on OrchestrationBug - complete or remove
3. Plan Sprint/OrchestrationSprint migration

### Phase 3: Code Quality
1. Fix TypeScript errors (77 remaining)
2. Remove unused imports
3. Fix deprecated API usage

### Phase 4: Feature Audit
1. Review incomplete features
2. Complete or remove OrchestrationBug
3. Assess import services usage
4. Review time tracking model overlap

### Phase 5: Documentation
1. Update outdated docs
2. Archive old delegation tasks
3. Create system overview diagram
4. Write developer onboarding guide

### Phase 6: Dependencies
1. Audit composer packages
2. Audit npm packages
3. Update outdated dependencies
4. Remove unused dependencies

### Phase 7: Performance
1. Run performance profiling
2. Identify slow queries
3. Add caching where needed
4. Optimize asset delivery

### Phase 8: Security
1. Security audit
2. Dependency vulnerability scan
3. Code review for security issues
4. Update security documentation

---

## Quick Wins (Can Do Now)

1. ✅ Remove unused models (DONE)
2. Remove unused imports in frontend files
3. Fix CompactProjectPicker import issue
4. Run composer show --unused
5. Run npm outdated
6. Search and document TODO comments
7. Fix deprecated ElementRef usage
8. Remove unused icon imports

---

## Needs Discussion

1. **Sprint Model Consolidation** - Which to keep?
2. **WorkItem vs OrchestrationTask** - Migration strategy?
3. **Time Tracking Models** - Consolidate or keep separate?
4. **Import Services** - Worth creating shared base class?
5. **Article Model** - Remove entirely or integrate?
6. **OrchestrationBug** - Complete feature or remove?
7. **SeerLog vs TelemetryEvent** - Keep both or merge?

---

## Metrics

### Current State
- **Models**: 66 total → 57 active (9 removed)
- **Migrations**: 119 total
- **TypeScript Errors**: 77 (pre-existing)
- **Services**: 36 in app/Services/
- **Commands**: 30 in app/Commands/
- **Tests**: Feature + Unit tests

### Target State
- **Models**: ~50 (consolidate duplicates)
- **Migrations**: Clean up orphaned
- **TypeScript Errors**: 0
- **Dead Code**: 0%
- **Test Coverage**: >80%
