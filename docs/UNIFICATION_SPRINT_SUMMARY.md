# Type + Command Unification Sprint

**Status:** Planning Complete - Ready for Weekend Implementation  
**Estimate:** 9-14 hours over 2-3 sessions  
**Documentation:** See `docs/UNIFIED_ARCHITECTURE.md` for full details

---

## What We're Building

A unified system where:
- **Types** define data (model-backed or fragment-backed)
- **Commands** define actions (the controller layer)
- **Fragments** are raw input (processed into typed objects)

### Key Innovation: storage_type
Types can be either:
- **model** = Own table (Sprint, Task, Agent)
- **fragment** = Stored as fragments with schema (Note, Log, Bookmark)

---

## Sprint Structure

### Sprint 1: Schema & DB (2-3 hours)
- [x] T-UNIFY-01: Create types_registry migration
- [x] T-UNIFY-02: Create commands table migration
- [x] T-UNIFY-03: Create Type model
- [x] T-UNIFY-04: Create Command model
- [x] T-UNIFY-05: Seed initial data

**Deliverable:** Clean DB schema with seeded data

---

### Sprint 2: Command System Refactor (3-4 hours)
- [x] T-UNIFY-06: Update CommandRegistry service
- [x] T-UNIFY-07: Update BaseCommand abstract class
- [x] T-UNIFY-08: Update existing command classes
- [x] T-UNIFY-09: Update command execution flow

**Deliverable:** All commands load from DB, use type config

---

### Sprint 3: Frontend Integration (2-3 hours)
- [x] T-UNIFY-10: Update CommandResultModal.tsx
- [x] T-UNIFY-11: Create missing card components
- [x] T-UNIFY-12: Update UnifiedListModal component
- [x] T-UNIFY-13: Create TypeConfigProvider context

**Deliverable:** All slash commands render correctly with DB-driven UI

---

### Sprint 4: CLI & MCP Integration (1-2 hours)
- [x] T-UNIFY-14: Update CLI command discovery
- [x] T-UNIFY-15: Update MCP tool generation
- [x] T-UNIFY-16: Test cross-interface consistency

**Deliverable:** Commands work identically across all interfaces

---

### Sprint 5: Cleanup & Documentation (1-2 hours)
- [x] T-UNIFY-17: Remove deprecated code
- [x] T-UNIFY-18: Update command development guide
- [x] T-UNIFY-19: Create admin UI for types/commands
- [x] T-UNIFY-20: Update existing documentation

**Deliverable:** Clean codebase, updated docs, admin tools

---

## Quick Commands Reference

### View all tasks
```bash
php artisan orchestration:tasks --search="T-UNIFY"
```

### Start working on a task
```bash
php artisan orchestration:task:status T-UNIFY-01 --status=in_progress
```

### Mark task complete
```bash
php artisan orchestration:task:status T-UNIFY-01 --status=completed
```

### View task detail
```bash
php artisan orchestration:task:detail T-UNIFY-01
```

---

## Database Tables Overview

### types_registry
Defines data structures (both model and fragment-backed types)

Key fields:
- `storage_type` (model/fragment) ← **THE KEY INNOVATION**
- `model_class` (for model-backed)
- `schema` (for fragment-backed)
- `default_card_component`
- `default_detail_component`
- `capabilities`, `hot_fields`

### commands
Defines all actions/controllers across slash/CLI/MCP

Key fields:
- `command` (unique identifier)
- `type_slug` (FK to types_registry)
- `handler_class` (PHP class to execute)
- `available_in_slash`, `available_in_cli`, `available_in_mcp`
- `ui_modal_container`, `ui_layout_mode`
- `ui_card_component` (override type default)
- `filters`, `default_sort`, `pagination_default`

---

## Future Enhancements (Post-Sprint)

### Phase 2: User-Defined Types
- UI for creating fragment-based types
- Schema builder
- Auto-generate commands

### Phase 3: Recall Views System
- User-defined query templates
- Markdown output templates
- Reusable saved queries
- Token cost savings

See `docs/UNIFIED_ARCHITECTURE.md` section "Future: Recall Views System" for full spec.

### Phase 4: Advanced Features
- Command versioning
- Permissions/ACL
- Type migrations
- Analytics

---

## Key Files to Create

### Migrations
- `database/migrations/YYYY_MM_DD_create_types_registry_table.php`
- `database/migrations/YYYY_MM_DD_create_commands_table.php`
- `database/migrations/YYYY_MM_DD_migrate_fragment_types_to_types.php`

### Models
- `app/Models/Type.php`
- `app/Models/Command.php`

### Seeders
- `database/seeders/TypesSeeder.php`
- `database/seeders/CommandsSeeder.php`

### Services
- Update `app/Services/CommandRegistry.php`

---

## Success Criteria

✅ All existing commands work via slash/CLI/MCP  
✅ UI configuration driven by DB  
✅ No hard-coded component routing  
✅ Type/Command admin UI functional  
✅ Documentation updated  
✅ Clean codebase (deprecated code removed)  

---

## Notes from Planning Session

### Fragments Processing Flow
```
User Input → Fragment Processor → Intent Router
    ↓
Creates: Note (fragment) + Contact (fragment) + Todo (model) + Reminder (model)
```

### storage_type Innovation
The `storage_type` enum allows the same system to handle:
- Traditional models (own tables, migrations, relationships)
- Dynamic fragment-based types (schema in JSON, stored as fragments)

This gives us:
- Full Laravel features for core types
- Flexibility for user-defined types (future)
- Consistent interface regardless of storage

### Recall Views (Future)
User-defined query + markdown templates for consistent, reusable data retrieval.

Example:
- User: `/getNews get me last 15 AI articles`
- System: Uses saved view template, consistent format, low token cost
- Benefit: Saved queries avoid repeated LLM calls

---

**Ready to sprint!** 🚀

All planning complete. All tasks created. All documentation written.

Start with Sprint 1 this weekend!
