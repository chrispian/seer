# Fix Tool Names to Match MCP Pattern Requirements

## Context

MCP tool names must match the pattern: `^[a-zA-Z0-9_-]{1,128}$`

This means tool names can ONLY contain:
- Letters (a-z, A-Z)
- Numbers (0-9)
- Underscores (_)
- Hyphens (-)

**Dots (.) are NOT allowed.**

Currently, all tools use dots in their names, which causes this error when connecting to OpenCode/Claude:

```
AI_APICallError: tools.11.custom.name: String should match pattern '^[a-zA-Z0-9_-]{1,128}$'
```

## Required Changes

Replace all dots (`.`) with underscores (`_`) in tool names.

### Tool Name Mappings

| Current Name (INVALID) | New Name (VALID) |
|------------------------|------------------|
| `json.query`           | `json_query`     |
| `text.search`          | `text_search`    |
| `file.read`            | `file_read`      |
| `text.replace`         | `text_replace`   |
| `help.index`           | `help_index`     |
| `help.tool`            | `help_tool`      |

### Files to Update

#### 1. Tool Classes (src/Tools/*.php)

**Pattern to fix:**

```php
// BEFORE (INVALID):
protected string $name = 'json.query';

public static function summaryName(): string 
{ 
    return 'json.query'; 
}

// AFTER (VALID):
protected string $name = 'json_query';

public static function summaryName(): string 
{ 
    return 'json_query'; 
}
```

**Files:**
- `src/Tools/JqQueryTool.php`
- `src/Tools/TextSearchTool.php`
- `src/Tools/FileReadTool.php`
- `src/Tools/TextReplaceTool.php`
- `src/Tools/HelpIndexTool.php`
- `src/Tools/HelpToolDetail.php`

#### 2. Config File (config/tool-crate.php)

**BEFORE:**
```php
return [
    'enabled_tools' => [
        'json.query'   => true,
        'text.search'  => true,
        'file.read'    => true,
        'text.replace' => true,
        'help.index'   => true,
        'help.tool'    => true,
    ],
    'priority_tools' => [
        'json.query',
        'text.search',
        'file.read',
    ],
    'categories' => [
        'JSON & Data' => ['json.query', 'table.query'],
        'Text Ops'    => ['text.search', 'text.replace'],
        'Files'       => ['file.read'],
        'Help'        => ['help.index', 'help.tool'],
    ],
];
```

**AFTER:**
```php
return [
    'enabled_tools' => [
        'json_query'   => true,
        'text_search'  => true,
        'file_read'    => true,
        'text_replace' => true,
        'help_index'   => true,
        'help_tool'    => true,
    ],
    'priority_tools' => [
        'json_query',
        'text_search',
        'file_read',
    ],
    'categories' => [
        'JSON & Data' => ['json_query', 'table_query'],
        'Text Ops'    => ['text_search', 'text_replace'],
        'Files'       => ['file_read'],
        'Help'        => ['help_index', 'help_tool'],
    ],
];
```

#### 3. Server Class (src/Servers/ToolCrateServer.php)

Update the `getToolConfigKey()` method's mapping array:

**BEFORE:**
```php
private function getToolConfigKey(string $toolClass): string
{
    $map = [
        JqQueryTool::class => 'json.query',
        TextSearchTool::class => 'text.search',
        FileReadTool::class => 'file.read',
        TextReplaceTool::class => 'text.replace',
        HelpIndexTool::class => 'help.index',
        HelpToolDetail::class => 'help.tool',
    ];

    return $map[$toolClass] ?? '';
}
```

**AFTER:**
```php
private function getToolConfigKey(string $toolClass): string
{
    $map = [
        JqQueryTool::class => 'json_query',
        TextSearchTool::class => 'text_search',
        FileReadTool::class => 'file_read',
        TextReplaceTool::class => 'text_replace',
        HelpIndexTool::class => 'help_index',
        HelpToolDetail::class => 'help_tool',
    ];

    return $map[$toolClass] ?? '';
}
```

#### 4. HelpIndexTool.php Special Case

This tool dynamically references other tools' metadata. Check if there are any hardcoded tool name strings that need updating.

## Quick Fix Script

For a quick bulk replacement (BE CAREFUL - review changes before committing):

```bash
# Replace in tool files
find src/Tools -name "*.php" -type f -exec sed -i '' "s/'json\.query'/'json_query'/g" {} \;
find src/Tools -name "*.php" -type f -exec sed -i '' "s/'text\.search'/'text_search'/g" {} \;
find src/Tools -name "*.php" -type f -exec sed -i '' "s/'file\.read'/'file_read'/g" {} \;
find src/Tools -name "*.php" -type f -exec sed -i '' "s/'text\.replace'/'text_replace'/g" {} \;
find src/Tools -name "*.php" -type f -exec sed -i '' "s/'help\.index'/'help_index'/g" {} \;
find src/Tools -name "*.php" -type f -exec sed -i '' "s/'help\.tool'/'help_tool'/g" {} \;

# Replace in server
sed -i '' "s/'json\.query'/'json_query'/g" src/Servers/ToolCrateServer.php
sed -i '' "s/'text\.search'/'text_search'/g" src/Servers/ToolCrateServer.php
sed -i '' "s/'file\.read'/'file_read'/g" src/Servers/ToolCrateServer.php
sed -i '' "s/'text\.replace'/'text_replace'/g" src/Servers/ToolCrateServer.php
sed -i '' "s/'help\.index'/'help_index'/g" src/Servers/ToolCrateServer.php
sed -i '' "s/'help\.tool'/'help_tool'/g" src/Servers/ToolCrateServer.php

# Replace in config
sed -i '' "s/'json\.query'/'json_query'/g" config/tool-crate.php
sed -i '' "s/'text\.search'/'text_search'/g" config/tool-crate.php
sed -i '' "s/'file\.read'/'file_read'/g" config/tool-crate.php
sed -i '' "s/'text\.replace'/'text_replace'/g" config/tool-crate.php
sed -i '' "s/'help\.index'/'help_index'/g" config/tool-crate.php
sed -i '' "s/'help\.tool'/'help_tool'/g" config/tool-crate.php
```

**Note:** The above uses macOS `sed -i ''` syntax. On Linux, use `sed -i` instead.

## Verification

After making changes:

```bash
# Test that all tool names are valid
echo '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}' | php artisan tool-crate:mcp 2>&1 | python3 -c "
import json, sys, re
data = json.load(sys.stdin)
pattern = re.compile(r'^[a-zA-Z0-9_-]{1,128}$')
for tool in data['result']['tools']:
    name = tool['name']
    if not pattern.match(name):
        print(f'INVALID: {name}')
    else:
        print(f'✓ {name}')
"
```

All tool names should show ✓ (checkmark).

## Release

After fixing, bump version to **v0.2.2** and push:

```bash
# Update version in composer.json or package metadata
git add .
git commit -m "fix: replace dots with underscores in tool names for MCP pattern compliance"
git tag v0.2.2
git push origin main --tags
```

## Priority

**CRITICAL** - This blocks all MCP clients from using the tools. OpenCode/Claude will reject the server connection if any tool name is invalid.

## Related

This fix was applied to the Fragments Engine orchestration tools in commit [reference]. All orchestration tool names were updated from `orchestration.agents.list` format to `orchestration_agents_list` format.
