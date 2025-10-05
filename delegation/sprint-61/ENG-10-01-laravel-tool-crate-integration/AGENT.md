# ENG-10-01: Laravel Tool Crate Integration

## Agent Profile
**Type**: Backend Engineering Specialist
**Expertise**: Laravel package management, Composer configuration, MCP server setup
**Focus**: Package integration, configuration management, service registration

## Mission
Integrate the laravel-tool-crate package into Fragments Engine and configure the MCP server to enable tool execution from the chat interface.

## Current Context
- Laravel MCP 0.2.0 is already installed
- laravel-tool-crate exists in /Users/chrispian/Projects/seer/laravel-tool-crate
- No MCP servers currently registered in the application
- Chat infrastructure is operational and ready for integration

## Skills Required
- Composer package management
- Laravel service provider configuration
- MCP server registration and setup
- Configuration file management
- Package publishing and asset management

## Success Metrics
- laravel-tool-crate package successfully installed via Composer
- MCP server registered and accessible in routes/ai.php
- Tool-crate configuration published and customized
- All tool-crate tools discoverable via MCP protocol
- No breaking changes to existing functionality

## Deliverables

### 1. Composer Integration
- Add laravel-tool-crate as path repository
- Update composer.json with package requirement
- Run composer update to install package
- Verify package autoloading

### 2. MCP Server Configuration
- Create routes/ai.php if it doesn't exist
- Register tool-crate MCP server
- Configure server options and settings
- Verify server registration

### 3. Configuration Setup
- Publish tool-crate configuration
- Customize settings for Fragments Engine
- Configure tool priorities and categories
- Set up tool access permissions

### 4. Verification
- Test MCP server availability
- Verify tool discovery works
- Check help.index tool functionality
- Document any configuration requirements

## Technical Approach

### Step 1: Package Setup
```json
{
  "repositories": [
    { 
      "type": "path", 
      "url": "laravel-tool-crate", 
      "options": { "symlink": true } 
    }
  ],
  "require-dev": {
    "hollis-labs/laravel-tool-crate": "*"
  }
}
```

### Step 2: Server Registration
```php
// routes/ai.php
use Laravel\Mcp\Facades\Mcp;
use HollisLabs\ToolCrate\Servers\ToolCrateServer;

Mcp::local('tool-crate', ToolCrateServer::class);
```

### Step 3: Configuration
- Publish config: `php artisan vendor:publish --tag=laravel-tool-crate-config`
- Customize config/tool-crate.php for project needs

## Testing Plan
1. Verify package installation with `composer show`
2. Test MCP server registration with artisan commands
3. Execute help.index tool to verify functionality
4. Check all tools are discoverable
5. Ensure no regression in existing features

## Dependencies
- Laravel MCP 0.2.0
- Existing Laravel application structure
- Composer package manager
- PHP 8.2+

## Time Estimate
2-3 hours total:
- 30 min: Composer setup and installation
- 45 min: MCP server configuration
- 45 min: Configuration and customization
- 30 min: Testing and verification
- 30 min: Documentation and cleanup