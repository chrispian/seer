# Template Engine Variable Resolution Fix

## Problem Statement

The Slash Commands DSL template engine (`app/Services/Commands/DSL/TemplateEngine.php`) has a critical issue with variable resolution that prevents proper template rendering. Currently, templates like `{{ ctx.body | default: ctx.selection | trim }}` are resolving to literal strings instead of actual values from the context.

## Current Behavior vs Expected

**Current (Broken):**
- Input template: `{{ ctx.body | default: ctx.selection | trim }}`
- Context: `{ "ctx": { "body": "Hello world", "selection": "" } }`
- Output: `"ctx.selection"` (literal string)

**Expected (Fixed):**
- Input template: `{{ ctx.body | default: ctx.selection | trim }}`
- Context: `{ "ctx": { "body": "Hello world", "selection": "" } }`
- Output: `"Hello world"` (resolved from ctx.body when ctx.selection is empty)

## Technical Context

### Current Implementation Location
- **File**: `app/Services/Commands/DSL/TemplateEngine.php`
- **Key Methods**: 
  - `render()` - Main template processing
  - `processVariable()` - Variable + filter chain processing  
  - `getValue()` - Dot notation path resolution
  - `applyFilter()` - Filter application (trim, default, etc.)

### Architecture Context
The template engine is part of the Slash Commands DSL system:
- **CommandRunner** calls `TemplateEngine::render()` for each step
- **Step configs** contain templates that need variable substitution
- **Context structure**: `{ "ctx": {...}, "steps": {...}, "now": "...", "env": {...} }`

### Known Issues
1. **Variable Resolution**: `getValue()` method may not properly navigate nested object paths
2. **Filter Chaining**: The `default:` filter fallback logic isn't working correctly
3. **Context Access**: Dot notation parsing might have edge cases with empty/null values

## Requirements

### Functional Requirements
- ✅ **Dot notation**: `ctx.body`, `steps.step-id.output`, `ctx.user.name`
- ✅ **Filter chaining**: `{{ variable | filter1 | filter2:arg }}`
- ✅ **Fallback logic**: `{{ primary | default: secondary | default: tertiary }}`
- ✅ **Null handling**: Empty/null values should trigger fallbacks properly
- ✅ **Nested access**: Deep object navigation (`ctx.user.profile.settings.theme`)

### Existing Filters (Must Preserve)
- `trim`, `lower`, `upper`, `slug`
- `default:value` (primary focus for fix)
- `take:n`, `date:format`, `jsonpath:$.path`

### Test Cases Needed
```yaml
# Basic variable resolution
"{{ ctx.body }}" + {"ctx": {"body": "test"}} → "test"

# Fallback chain
"{{ ctx.selection | default: ctx.body }}" + {"ctx": {"selection": "", "body": "fallback"}} → "fallback"

# Multiple fallbacks
"{{ ctx.a | default: ctx.b | default: 'final' }}" + {"ctx": {"a": null, "b": ""}} → "final"

# Deep nesting
"{{ ctx.user.profile.name }}" + {"ctx": {"user": {"profile": {"name": "John"}}}} → "John"

# Filter combination
"{{ ctx.body | trim | lower }}" + {"ctx": {"body": "  HELLO  "}} → "hello"
```

## Constraints

### Backward Compatibility
- **DO NOT** change the template syntax - must remain `{{ variable | filter }}`
- **DO NOT** break existing filter implementations
- **PRESERVE** all current filter functionality (trim, lower, upper, etc.)

### Performance
- Template rendering happens on every command step execution
- Keep processing lightweight and fast
- Avoid regex complexity that could cause performance issues

### Integration Points
- **Command packs** rely on this for all variable substitution
- **Built-in commands** (`/todo`, `/note`, etc.) use complex template chains
- **AI generation steps** use templates for prompt building
- **Fragment creation** uses templates for all field mapping

## Success Criteria

### Core Functionality
1. **Variable resolution works**: `{{ ctx.body }}` returns actual context value
2. **Fallback chains work**: `{{ empty | default: backup }}` properly falls back
3. **All existing commands work**: `/todo`, `/note`, `/search` execute without template errors
4. **Filter chaining works**: Multiple filters can be combined successfully

### Test Validation
1. **Unit tests pass**: Template engine has comprehensive test coverage
2. **Command tests pass**: `php artisan frag:command:test {command} --dry` works for all built-ins
3. **Integration tests pass**: End-to-end command execution produces expected fragments

## Implementation Notes

### Likely Root Causes
1. **getValue() logic**: May not be properly traversing nested objects
2. **Filter processing**: The `default:` filter might not be handling empty/falsy values correctly
3. **Variable parsing**: Regex or string processing in `processVariable()` might have bugs

### Debug Approach
1. Add logging to `getValue()` to see what paths are being resolved
2. Test `default:` filter in isolation with various input types
3. Verify regex patterns in `render()` are capturing variables correctly
4. Check for edge cases with empty strings vs null vs undefined

### Testing Strategy
- Create unit tests for `TemplateEngine` class
- Test with actual command pack scenarios
- Validate against real context structures from command execution
- Use dry-run mode for safe testing

## Out of Scope

- **New template syntax** - stick to current `{{ }}` format
- **New filters** - focus only on fixing existing functionality  
- **Performance optimizations** - reliability first, speed second
- **Template preprocessing** - keep the current real-time rendering approach

The goal is a **surgical fix** that makes existing templates work correctly without breaking changes to the API or syntax.