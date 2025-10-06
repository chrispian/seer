# DSL-UX-003: Dynamic Help System

## Agent Role
Help system architect focused on replacing static help content with dynamic, registry-driven help generation. Transform the help command from hardcoded YAML to a live system that reflects current command state.

## Objective
Replace the static `/help` YAML template with a dynamic system that renders help content from the command registry metadata, ensuring help information is always current and comprehensive.

## Core Task
Create a dynamic help system that generates help content from the enhanced command registry, provides a reusable API for help data, and maintains fast response times through intelligent caching.

## Key Deliverables

### 1. Dynamic Help Template System
**File**: `fragments/commands/help/command.yaml` (enhanced)
- Replace static content with dynamic template rendering
- Integrate with registry metadata for current command state
- Categorized command grouping
- Rich formatting with examples and usage patterns

### 2. Help API Endpoint
**File**: `app/Http/Controllers/HelpController.php`
- `GET /api/commands/help` for reusable help data
- JSON response format for UI consumption
- Category-based filtering and organization
- Search functionality within help content

### 3. Help Cache Management
**File**: `app/Services/HelpCacheService.php`
- Long-lived cache (`command_help.index`) for performance
- Auto-invalidation when command packs change
- Partial cache updates for efficiency
- Cache warming strategies

### 4. Template Rendering Engine
**File**: `app/Services/Commands/HelpRenderer.php`
- Convert registry metadata to formatted help content
- Category grouping and sorting logic
- Example formatting and usage pattern display
- Markdown/HTML output for different contexts

## Success Criteria

### Functionality:
- [ ] Help content reflects current command registry state
- [ ] New commands appear in help immediately after cache rebuild
- [ ] Commands removed from registry disappear from help
- [ ] Rich metadata (examples, categories, summaries) display properly

### Performance:
- [ ] Help generation under 200ms for full registry
- [ ] Cache hits provide sub-50ms response times
- [ ] Cache invalidation completes within 5 seconds
- [ ] Memory usage remains reasonable for large registries

### User Experience:
- [ ] Help content is well-organized by category
- [ ] Command examples are clear and actionable
- [ ] Usage patterns help users understand syntax
- [ ] Search functionality helps discover relevant commands
