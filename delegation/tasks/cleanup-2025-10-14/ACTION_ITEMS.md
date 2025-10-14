# Cleanup Action Items - 2025-10-14

## ✅ Completed

1. **Created backup structure**
   - `backup/models/` - 9 unused models backed up
   - `backup/migrations/` - 2 migrations backed up
   - `delegation/tasks/cleanup-2025-10-14/` - Task tracking

2. **Removed 9 unused models** (0 references):
   - AgentVector, ArticleFragment, CalendarEvent, FileText
   - FragmentTag, ObjectType, PromptEntry, Thumbnail, WorkItemEvent

3. **Created comprehensive documentation**:
   - Systems inventory (24 systems documented)
   - Cleanup opportunities (18 categories)
   - Rarely used models analysis (28 models)
   - Unused models details
   - Cleanup summary

4. **Verified application state**:
   - 57 models remaining (down from 66)
   - Migrations intact
   - Application functional

## 🔴 Urgent (Fix Before Production Deploy)

1. **Fix syntax error in SqliteVectorStore.php:177**
   - Parse error breaking tests
   - Unexpected token "if"
   - Likely indentation/bracket issue

2. **Fix CompactProjectPicker import**
   - Missing module in ChatToolbar.tsx
   - Blocking frontend build?

## 🟡 High Priority (This Week)

### TypeScript Cleanup (77 errors)
1. Remove unused React imports (10 files):
   - AgentProfileListModal.tsx
   - ProjectListModal.tsx
   - VaultListModal.tsx
   - SecurityDashboardModal.tsx
   - ChatToolbar.tsx

2. Remove unused icon imports:
   - DataManagementModal.tsx (Filter, Plus, Check)
   - TaskListModal.tsx (Clock, AlertCircle, User, Calendar, FileText)
   - SprintDetailModal.tsx (Calendar, CheckCircle, Clock, AlertCircle, Users)
   - TodoManagementModal.tsx (Filter, Check)
   - CustomizationPanel.tsx (Palette, Space, EyeOff, X, Plus)

3. Remove unused variables:
   - AppSidebar.tsx (Button, LoadingSpinner, etc.)
   - TaskDetailModal.tsx (contentLoading, contentError, etc.)
   - SprintDetailModal.tsx (null vs undefined type issue)

4. Fix deprecated API:
   - command.tsx (ElementRef → ComponentPropsWithoutRef)

### Code Review
1. **Article Model**: Remove or integrate?
   - Only used in tests
   - Decide: Keep for testing or remove entirely?

2. **OrchestrationBug**: Complete or remove?
   - Service exists but no integration
   - Decide: Complete feature or remove?

3. **AgentLog Import**: Still needed?
   - AgentLogImportService only has 1 reference
   - Check if import feature is actively used

## 🟢 Medium Priority (Next Sprint)

### Model Consolidation
1. **Sprint Models**: Decide strategy
   - Keep `Sprint` or `OrchestrationSprint`?
   - Plan data migration if switching
   - Update all references

2. **Work Item Models**: Decide strategy
   - Keep `WorkItem` or `OrchestrationTask`?
   - Assess feature overlap
   - Plan consolidation

3. **Time Tracking Models**: Review necessity
   - SessionActivity (chat sessions)
   - WorkSession (work blocks)
   - TaskActivity (task events)
   - Assess if all 3 needed or can consolidate

### Dependency Audit
```bash
# Check for unused Composer packages
composer show --unused

# Check for outdated packages
composer outdated
npm outdated

# Security audit
composer audit
npm audit
```

### Documentation Review
1. Archive old docs in `docs/`
2. Archive completed delegation tasks
3. Create system architecture diagram
4. Write developer onboarding guide

## ⚪ Low Priority (Backlog)

### Code Quality
1. Search for TODO/FIXME comments:
   ```bash
   grep -r "TODO\|FIXME\|HACK" app/
   ```

2. Run code style fixes:
   ```bash
   ./vendor/bin/pint
   npm run lint:fix
   ```

3. Review orphaned views:
   ```bash
   find resources/views -name "*.blade.php" -type f
   ```

### Performance
1. Run test coverage report:
   ```bash
   composer test:coverage
   ```

2. Profile database queries (N+1 detection)
3. Review caching opportunities
4. Optimize asset bundles

### Security
1. Search for hardcoded secrets
2. Review SQL injection risks
3. Audit command injection vectors
4. Run dependency vulnerability scan

## 📋 Discussion Needed

These require product/architecture decisions:

1. **Sprint Model Strategy**
   - Current: Both `Sprint` and `OrchestrationSprint` exist
   - Question: Which to keep? Migration path?
   - Impact: High - affects orchestration features

2. **Work Item Strategy**
   - Current: Both `WorkItem` and `OrchestrationTask` exist
   - Question: Consolidate or keep separate?
   - Impact: Medium - affects task management

3. **Logging Consolidation**
   - Current: `SeerLog` and `TelemetryEvent` overlap
   - Question: Merge or keep separate?
   - Impact: Low - both functional

4. **Time Tracking Models**
   - Current: 3 models for time tracking
   - Question: All needed or consolidate?
   - Impact: Low - each serves different purpose

5. **Article Model Fate**
   - Current: Only used in tests
   - Question: Remove entirely or integrate?
   - Impact: Low - minimal usage

6. **OrchestrationBug Feature**
   - Current: Service exists, no integration
   - Question: Complete feature or remove?
   - Impact: Low - not currently used

7. **Import Services Architecture**
   - Current: 3 separate import services
   - Question: Create shared base class?
   - Impact: Low - code quality improvement

## 🎯 Success Metrics

### Target State
- [x] Models: 66 → 57 (-13.6%) ✅ 
- [ ] TypeScript errors: 77 → 0
- [ ] Test passing rate: Current → 100%
- [ ] Code coverage: Current → >80%

### Current State
- ✅ 9 unused models removed
- ✅ Comprehensive documentation created
- ✅ Cleanup roadmap defined
- ❌ TypeScript errors remain (77)
- ❌ Test syntax error needs fix

## 📅 Timeline

### Week 1 (Current)
- ✅ Remove unused models
- ✅ Create documentation
- [ ] Fix urgent issues (SqliteVectorStore, CompactProjectPicker)
- [ ] Fix TypeScript errors

### Week 2
- [ ] Model consolidation decisions
- [ ] Dependency audit
- [ ] Code quality fixes

### Month 1
- [ ] Complete model consolidation
- [ ] Documentation improvements
- [ ] Performance optimization

### Quarter 1
- [ ] Security audit
- [ ] Test coverage >80%
- [ ] Modular architecture planning

## 🔗 Related Files

- **Main Tracking**: `delegation/tasks/cleanup-2025-10-14/README.md`
- **Systems Map**: `systems-inventory.md`
- **Opportunities**: `cleanup-opportunities.md`
- **Model Analysis**: `rarely-used-models.md`, `unused-models-details.md`
- **Summary**: `CLEANUP_SUMMARY.md`

## 📞 Stakeholders

- **Product Owner**: Model consolidation decisions
- **Tech Lead**: Architecture review, consolidation strategy
- **QA**: Test coverage, critical path testing
- **DevOps**: Performance impact, deployment considerations
