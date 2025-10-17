# Orchestration v2 Package Extraction Documentation

This directory contains complete documentation for extracting the Orchestration subsystem from the Seer Laravel application into a standalone `hollis-labs/orchestration` package.

---

## Document Index

### 📋 [PACKAGE-MIGRATION-PLAN.md](./PACKAGE-MIGRATION-PLAN.md)
**Comprehensive migration strategy and architecture overview**

- Complete package structure
- All 103 files organized by category
- Dependencies and integration points
- 11-phase migration strategy
- Configuration requirements
- Testing checklist
- File count summaries

**Read this first** for the big picture and strategic planning.

---

### 📦 [FILE-INVENTORY.md](./FILE-INVENTORY.md)
**Detailed listing of every file with metadata**

- Complete file-by-file breakdown
- Line counts and descriptions
- Relationships and dependencies
- Route endpoints (42 total)
- Tool names and signatures
- Migration complexity assessments
- Size and complexity statistics

**Use this** as your reference during actual file migration.

---

### ✅ [MIGRATION-CHECKLIST.md](./MIGRATION-CHECKLIST.md)
**Step-by-step actionable checklist**

- 103 individual file checkboxes
- 11 migration phases
- Testing requirements per phase
- Namespace update tracking
- Rollback plan
- Success criteria
- Estimated timeline (~2 weeks)

**Use this** to track migration progress and ensure nothing is missed.

---

## Quick Reference

### What is Being Extracted?

The **Orchestration subsystem** - a complete AI agent workflow management system that handles:

- **Task Management** - Work item tracking and lifecycle
- **Sprint Management** - Iteration planning and execution
- **Agent Coordination** - Multi-agent workflow orchestration
- **Event Sourcing** - Complete audit trail and replay capability
- **Session Management** - Agent work session state tracking
- **Context Brokering** - AI context distribution and search
- **Git Integration** - Commit tracking and PR linking
- **PM Tools** - ADR generation, bug reporting, status reports
- **MCP Tools** - 27 AI-accessible tools for agent interaction
- **Workflow Automation** - Phase-based workflow management

---

## Package Overview

### File Breakdown

```
📁 Models                    5 files
📁 Services                 16 files
📁 Controllers              10 files
📁 Console Commands         31 files (27 to package, 4 stay in app)
📁 MCP Tools                27 files + 1 concern
📁 Migrations               10 files
📁 Factories                 2 files
📁 Config                    1 file
📁 Templates                 1 file
📁 Events                    1 file
📁 Enums                     1 file
📁 Routes                    1 section (~42 endpoints)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL                      103 files
```

### Key Statistics

- **API Endpoints:** 42
- **MCP Tools:** 27
- **Console Commands:** 27 (4 migration commands stay in main app)
- **Database Tables:** 5 (tasks, sprints, events, artifacts, bugs)
- **Services:** 16 (4 core + 12 specialized)
- **Lines of Code:** ~30,000+ (estimated)

---

## Migration Approach

### 11 Phases

1. **Core Models & Data Structures** - Foundation (5 models, 1 enum, 1 event)
2. **Services Layer** - Business logic (16 services)
3. **HTTP Layer** - API controllers and routes (10 controllers, 42 endpoints)
4. **Console Commands** - Artisan commands (27 commands)
5. **MCP Tools** - AI agent tools (27 tools)
6. **Database & Configuration** - Migrations, factories, config (13 files)
7. **Package Integration** - Connect to main app
8. **Documentation** - Complete package docs
9. **Testing & QA** - Comprehensive test suite
10. **Cleanup & Finalization** - Remove old files, update imports
11. **Release** - Version tagging, publishing

### Estimated Timeline

**~82 hours / 2 weeks** for complete migration

---

## Dependencies

### Package Will Depend On (Main App)

- `App\Models\AgentProfile` - Agent definitions
- `App\Models\Project` - Project context
- `App\Models\Fragment` - Memory/context fragments
- `App\Models\User` - User authentication
- Vector search service
- Memory service
- Git service (shared)

### Main App Will Depend On (Package)

- All orchestration models
- All orchestration services
- All MCP tools
- All API endpoints
- All console commands

---

## Critical Integration Points

### 🔴 High Risk Areas

1. **Event Sourcing** - Complex event replay and audit trail
2. **Session Management** - State tracking with workflow.yaml
3. **Context Search** - Vector search integration
4. **MCP Server** - Tool protocol integration
5. **Git Integration** - Process management for commits
6. **Memory Service** - AI context storage and retrieval

### 🟡 Medium Risk Areas

1. Service layer refactoring
2. Controller namespace changes
3. Route mounting
4. Migration ordering

### 🟢 Low Risk Areas

1. Models (clean boundaries)
2. Simple tools
3. Basic commands
4. Configuration

---

## Getting Started

### Step 1: Review Documentation
Read the documents in this order:
1. This README (you are here)
2. PACKAGE-MIGRATION-PLAN.md (strategy)
3. FILE-INVENTORY.md (details)
4. MIGRATION-CHECKLIST.md (execution)

### Step 2: Set Up Package Repository
```bash
# Create package directory
mkdir -p vendor/hollis-labs/orchestration
cd vendor/hollis-labs/orchestration

# Initialize git
git init

# Create basic structure
mkdir -p src/{Models,Services,Http/Controllers,Console/Commands,Tools,Events,Enums}
mkdir -p database/{migrations,factories}
mkdir -p routes
mkdir -p config
mkdir -p resources/templates/orchestration
```

### Step 3: Create composer.json
```json
{
  "name": "hollis-labs/orchestration",
  "description": "AI agent orchestration and workflow management",
  "type": "library",
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "symfony/yaml": "^7.0"
  },
  "autoload": {
    "psr-4": {
      "HollisLabs\\Orchestration\\": "src/"
    }
  },
  "extra": {
    "laravel": {
      "providers": [
        "HollisLabs\\Orchestration\\OrchestrationServiceProvider"
      ]
    }
  }
}
```

### Step 4: Create Service Provider
```php
<?php

namespace HollisLabs\Orchestration;

use Illuminate\Support\ServiceProvider;

class OrchestrationServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register services
        $this->mergeConfigFrom(
            __DIR__.'/../config/orchestration.php', 'orchestration'
        );
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/orchestration.php' => config_path('orchestration.php'),
        ], 'orchestration-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'orchestration-migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Load commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Register commands here
            ]);
        }
    }
}
```

### Step 5: Start Migration
Follow the MIGRATION-CHECKLIST.md, starting with Phase 1.

---

## Testing Strategy

### Unit Tests
- Test each model independently
- Test each service method
- Test each tool
- Test each command

### Integration Tests
- Test service interactions
- Test controller endpoints
- Test event sourcing flow
- Test workflow state transitions

### Feature Tests
- Complete sprint workflow
- Complete task workflow
- Session lifecycle
- MCP tool integration

### Performance Tests
- Event sourcing performance
- Search query performance
- Large dataset handling

---

## Rollback Strategy

If migration fails:

1. **Keep backup** of original files (don't delete until verified)
2. **Version control** - work in separate branch
3. **Test thoroughly** before removing old code
4. **Document issues** encountered
5. **Have contingency** - can revert to monolithic structure

---

## Success Criteria

✅ All 103 files migrated  
✅ All tests passing  
✅ No broken imports  
✅ All 42 API endpoints working  
✅ All 27 commands working  
✅ All 27 MCP tools working  
✅ Documentation complete  
✅ Package installable via composer  
✅ Zero downtime  

---

## Support & Resources

### Documentation Files
- `PACKAGE-MIGRATION-PLAN.md` - Strategy and architecture
- `FILE-INVENTORY.md` - Complete file listing
- `MIGRATION-CHECKLIST.md` - Step-by-step checklist

### Code Locations
- Current: `app/`, `database/`, `config/`, `routes/`, `resources/`
- Target: `vendor/hollis-labs/orchestration/src/`

### Key Concepts
- **Event Sourcing** - All state changes captured as events
- **MCP Protocol** - Model Context Protocol for AI tools
- **Workflow Phases** - Planning → Implementation → Review → Testing → Deployment → Closed
- **Session Management** - Agent work session state tracking
- **Context Brokering** - Distributing context to agents

---

## FAQ

### Q: Why extract into a package?
**A:** To improve:
- **Modularity** - Clear boundaries and dependencies
- **Reusability** - Use in other projects
- **Testability** - Isolated testing
- **Maintainability** - Independent versioning

### Q: What stays in the main app?
**A:** 
- Agent profiles (domain-specific)
- Projects (domain-specific)
- Memory/fragments (shared infrastructure)
- Users (authentication)
- Migration commands (one-time utilities)

### Q: What moves to the package?
**A:**
- All orchestration-specific models
- All orchestration services
- All orchestration controllers
- All orchestration commands
- All MCP tools
- All orchestration routes
- Orchestration config and templates

### Q: Will this break existing functionality?
**A:** No, if done correctly:
- Namespace changes will be the main breaking change
- Use search/replace for imports
- Service provider handles registration
- Routes remain the same
- Database structure unchanged

### Q: How long will migration take?
**A:** ~2 weeks (82 hours) estimated:
- 1 week for file migration and basic testing
- 1 week for comprehensive testing and documentation

### Q: Can we migrate gradually?
**A:** Not easily - orchestration is tightly coupled. Better to:
1. Migrate everything
2. Test thoroughly in dev
3. Deploy as single release

### Q: What if we find issues?
**A:** 
- Keep backup of original code
- Work in separate branch
- Can revert if needed
- Document all issues

---

## Timeline

```
Week 1:
├─ Day 1-2: Phases 1-3 (Models, Services, HTTP)
├─ Day 3-4: Phases 4-5 (Commands, Tools)
└─ Day 5:   Phase 6 (Database & Config)

Week 2:
├─ Day 1:   Phase 7 (Integration)
├─ Day 2-3: Phases 8-9 (Documentation & Testing)
├─ Day 4:   Phase 10 (Cleanup)
└─ Day 5:   Phase 11 (Release)
```

---

## Next Steps

1. ✅ Review all documentation (you're doing it!)
2. ⏳ Get team approval for migration
3. ⏳ Schedule migration window
4. ⏳ Create package repository
5. ⏳ Begin Phase 1: Core Models
6. ⏳ Follow MIGRATION-CHECKLIST.md

---

## Conclusion

This is a significant but well-planned extraction. The orchestration system is:

- **Well-defined** - Clear boundaries and responsibilities
- **Well-documented** - Complete file inventory and migration plan
- **Well-tested** - Comprehensive test strategy
- **Well-architected** - Clean service layer and event sourcing

Following this documentation, the migration should be:

- ✅ **Systematic** - Step-by-step process
- ✅ **Safe** - Testing at each phase
- ✅ **Reversible** - Rollback plan in place
- ✅ **Trackable** - Checklist to monitor progress

Good luck! 🚀

---

**Last Updated:** October 16, 2025  
**Status:** Ready for Migration  
**Estimated Effort:** 82 hours / 2 weeks  
**Files to Migrate:** 103
