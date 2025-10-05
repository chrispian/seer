# SETTINGS-004: Admin Configuration Panels

## Role
You are a Laravel + React developer implementing admin-only configuration panels for environment-driven settings with proper role-based access controls and read-only states.

## Context
Admin users need access to system-level configuration that affects all users, including environment-driven flags, tool allowlists, transparency toggles, and system capabilities. These settings should be clearly separated from user preferences and show read-only state when locked by environment variables.

## Current State
- No admin-specific settings interface
- Environment configuration managed only via `.env` files
- No visibility into system-level configuration status
- Missing admin controls for features like embeddings enablement, tool allowlists
- No transparency controls for AI model visibility

## Task Scope
Create comprehensive admin configuration system:

### Environment Configuration Display
- Show current environment variable values (sanitized)
- Indicate which settings are environment-locked vs configurable
- Display system capability status (embeddings, tools, vector store)
- Show configuration inheritance and override hierarchy

### Admin-Only Controls
- **Embeddings Configuration**: Enable/disable, provider selection, model configuration
- **Tool System**: Allowlist management, shell/filesystem/MCP controls, timeout settings
- **AI Transparency**: Model info visibility, toast notifications, fragment attribution
- **Security Settings**: Rate limiting, authentication requirements, audit logging
- **Feature Flags**: Experimental features, beta functionality toggles

### Access Control
- Role-based panel visibility (admin, super-admin hierarchies)
- Permission checking for individual setting categories
- Audit logging for all admin configuration changes
- Confirmation dialogs for destructive or system-wide changes

### User Experience
- Clear distinction between user and admin settings
- Visual indicators for environment-locked settings
- Contextual help explaining impact of changes
- Validation and warnings for configuration conflicts
- Status indicators showing system health and capability

## Success Criteria
- [ ] Admin users can access system-level configuration
- [ ] Environment-locked settings display as read-only
- [ ] Configuration changes are validated and logged
- [ ] Role-based access controls prevent unauthorized changes
- [ ] System status and capabilities are clearly visible
- [ ] Changes provide immediate feedback and validation
- [ ] Admin panel integrates seamlessly with user settings

## Technical Constraints
- Must respect existing environment variable patterns
- Coordinate with Laravel's authorization system
- Follow existing settings API patterns
- Use React patterns consistent with other settings
- Ensure changes don't break existing functionality