# Command Quick Reference

**Last Updated**: 2025-10-10  
**Status**: All commands tested and working ✅

---

## Orchestration Commands

### Sprints
- `/sprints` or `/sprint-list` - List all sprints
- `/sprint-detail CODE` - Show sprint details
- `/sprint-save CODE` - Create/update sprint (CLI/MCP only)
- `/sprint-status CODE STATUS` - Update sprint status (CLI/MCP only)
- `/sprint-attach-tasks CODE` - Attach tasks to sprint (CLI/MCP only)

**Aliases**: `sl` (sprint list), `sd` (sprint detail)

### Tasks
- `/tasks` or `/task-list` - List all tasks
- `/task-detail CODE` - Show task details
- `/task-save CODE` - Create/update task (CLI/MCP only)
- `/task-assign CODE AGENT` - Assign task to agent (CLI/MCP only)
- `/task-status CODE STATUS` - Update task status (CLI/MCP only)

**Aliases**: `tl` (task list), `td` (task detail)

### Agents
- `/agents` or `/agent-list` - List all agent profiles
- `/agent-profiles` - Same as agents

**Aliases**: `al`, `ap`

### Backlog
- `/backlog` or `/backlog-list` - List backlog items

**Aliases**: `bl`

---

## Content Commands

### Search & Recall
- `/search QUERY` - Search fragments
- `/recall` - Recall fragments

**Aliases**: `s` (search)

### Inbox
- `/inbox` - Show inbox items

**Aliases**: `in`

### Fragments
- `/frag` - Create fragment
- `/frag-simple` - Simple fragment creation

---

## Collections

### Bookmarks
- `/bookmarks` or `/bookmark` - List bookmarks

**Aliases**: `bm`

### Notes
- `/notes` - List notes
- `/note` - Create/view note

---

## Session & Navigation

### Sessions
- `/sessions` or `/session` - List sessions

### Channels
- `/channels` - List channels

### Projects & Vaults
- `/projects` or `/project` - List projects
- `/vaults` or `/vault` - List vaults

**Aliases**: `p` (project), `v` (vault)

---

## Utility Commands

### Todo
- `/todos` or `/todo` - Manage todos

**Aliases**: `t`

### Context & Composition
- `/context` - Show context
- `/compose` - Compose mode

**Aliases**: `ctx` (context), `c` (compose)

### Routing & Navigation
- `/routing` - Show routing info
- `/join CODE` - Join session/channel
- `/clear` - Clear chat
- `/name` - Set name

**Aliases**: `j` (join)

### Management
- `/types` - Manage types
- `/accept` or `/approve` - Accept items
- `/link` - Create link
- `/remind` or `/reminder` - Set reminder

### System
- `/help` - Show help
- `/setup`, `/onboard`, `/configure` - Setup wizard
- `/news-digest`, `/digest`, `/news` - News digest
- `/schedule-list`, `/schedules` - List schedules

---

## Command Patterns

### Singular vs Plural
Most commands support both:
- ✅ `/sprint` or `/sprints`
- ✅ `/task` or `/tasks`
- ✅ `/agent` or `/agents`
- ✅ `/bookmark` or `/bookmarks`
- ✅ `/todo` or `/todos`

### With Arguments
- **Space-separated**: `/sprint-detail SPRINT-67`
- **Key:value pairs**: `/search query:testing limit:10`
- **Mixed**: Commands support both styles

---

## Interface Availability

| Command Type | Web UI | MCP | CLI |
|-------------|--------|-----|-----|
| List/Detail (Read) | ✅ | ✅ | ✅ |
| Save/Create (Write) | ⚠️* | ✅ | ✅ |
| Assign/Status (Write) | ⚠️* | ✅ | ✅ |

*Write operations available via MCP and CLI. Web UI may add forms in the future.

---

## Testing Commands

### Web UI
Open chat and type: `/sprints`

### MCP (AnythingLLM)
```
Use orchestration_sprints_list with limit: 10
```

### CLI
```bash
php artisan orchestration:sprints --limit=10
```

---

## Common Issues

### "Command not recognized"
- Check spelling (use `/help` to list available commands)
- Try plural/singular variant
- Check `CommandRegistry.php` for exact name

### "Command Failed"
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify command constructor accepts `array $options`
- Ensure command is registered in `CommandRegistry.php`

---

## Adding New Commands

See: `COMMAND_DEVELOPMENT_GUIDE.md`

Quick steps:
1. Create command class in `app/Commands/`
2. Register in `CommandRegistry.php`
3. Test via web, MCP, and CLI

---

**All commands tested and working as of 2025-10-10** ✅
