# SETTINGS-004 TODO: Admin Configuration Panels

## Backend Implementation

### Admin Configuration Service
- [ ] Create `app/Services/AdminConfigurationService.php`
  - [ ] `getSystemConfiguration()` - Compile all admin settings with environment locks
  - [ ] `updateConfiguration(string $section, array $config)` - Update specific configuration section
  - [ ] `getEnvironmentLocks()` - Determine which settings are locked by environment variables
  - [ ] `validateAgainstLocks(string $section, array $config, array $locks)` - Prevent updates to locked settings
  - [ ] `getSystemStatus()` - Check health of various system components
  - [ ] Private section getters:
    - [ ] `getEmbeddingsConfig()` - Embeddings and vector store configuration
    - [ ] `getToolsConfig()` - Tool system configuration and allowlists
    - [ ] `getTransparencyConfig()` - AI transparency and visibility settings
    - [ ] `getSecurityConfig()` - Security, rate limiting, and audit settings
    - [ ] `getFeaturesConfig()` - Feature flags and experimental settings
  - [ ] Helper methods:
    - [ ] `getConfigValue(string $key, $envValue, $adminValue)` - Environment precedence logic
    - [ ] `clearConfigurationCache(string $section)` - Cache invalidation
    - [ ] `logConfigurationChange(string $section, array $config, User $user)` - Audit logging

### Admin Settings Storage
- [ ] Design admin settings storage strategy:
  - [ ] Option A: Create `admin_settings` table with `AdminSetting` model
  - [ ] Option B: Add `admin_settings` JSON column to existing table
  - [ ] Option C: Use dedicated settings service with cache
- [ ] Create `app/Models/AdminSetting.php` (if using table approach)
  - [ ] `getSection(string $section)` - Retrieve section configuration
  - [ ] `setSection(string $section, array $config)` - Update section configuration
  - [ ] Proper caching and cache invalidation
- [ ] Create migration for admin settings storage
- [ ] Implement settings caching with TTL and invalidation

### Authentication & Authorization
- [ ] Create `app/Http/Middleware/AdminOnly.php`
  - [ ] Check user admin permissions
  - [ ] Support different permission levels (admin, super-admin)
  - [ ] Return proper error responses for unauthorized access
- [ ] Update `User` model with admin permission methods:
  - [ ] `isAdmin()` - Check basic admin permissions
  - [ ] `isSuperAdmin()` - Check super admin permissions
  - [ ] `canManageSystemSettings()` - Check specific system management permissions
- [ ] Add admin middleware to kernel and route groups

### API Controller
- [ ] Create `app/Http/Controllers/Admin/ConfigurationController.php`
  - [ ] `index()` - GET endpoint returning full admin configuration
  - [ ] `update(AdminConfigurationRequest $request)` - PATCH endpoint for updates
  - [ ] `status()` - GET endpoint for system status checking
  - [ ] Apply admin middleware to all methods
  - [ ] Return user permissions in responses

### Request Validation
- [ ] Create `app/Http/Requests/AdminConfigurationRequest.php`
  - [ ] Authorization check using admin permissions
  - [ ] Validation rules for configuration sections and data
  - [ ] Section-specific validation (embeddings, tools, etc.)
  - [ ] Custom validation for tool allowlists and security settings

### API Routes
- [ ] Add routes to `routes/api.php`:
  - [ ] `GET /api/admin/configuration` - Fetch admin configuration
  - [ ] `PATCH /api/admin/configuration` - Update configuration
  - [ ] `GET /api/admin/status` - System status endpoint
- [ ] Apply admin middleware and rate limiting
- [ ] Group under admin prefix with proper middleware stack

### Audit Logging
- [ ] Create audit logging for admin actions:
  - [ ] Log all configuration changes with before/after values
  - [ ] Include user context (ID, IP, user agent)
  - [ ] Timestamp and action type tracking
  - [ ] Integration with existing audit system if available

## Frontend Implementation

### Type Definitions
- [ ] Create `resources/js/types/admin.ts`
  - [ ] `AdminConfiguration` interface - Complete admin config structure
  - [ ] `EmbeddingsConfig` interface - Embeddings and vector store settings
  - [ ] `ToolsConfig` interface - Tool system configuration
  - [ ] `TransparencyConfig` interface - AI transparency settings
  - [ ] `SecurityConfig` interface - Security and audit settings
  - [ ] `FeaturesConfig` interface - Feature flags and experimental settings
  - [ ] `SystemStatus` interface - System health and status information
  - [ ] `UserPermissions` interface - Admin permission levels

### API Integration
- [ ] Create `resources/js/hooks/useAdminConfiguration.ts`
  - [ ] Fetch admin configuration with React Query
  - [ ] Update configuration with mutation
  - [ ] Permission-based query enabling
  - [ ] Optimistic updates and error handling
  - [ ] Cache invalidation after updates

### Constants & Configuration
- [ ] Create `resources/js/constants/admin.ts`
  - [ ] `ADMIN_SECTIONS` - Configuration section metadata
  - [ ] `TOOL_CATEGORIES` - Tool category information and safety levels
  - [ ] `FEATURE_FLAGS` - Available feature flag definitions
  - [ ] `SECURITY_SETTINGS` - Security configuration options
  - [ ] Helper functions for section and category management

## Helper Components

### Common Admin Components
- [ ] Create `resources/js/islands/Settings/components/Admin/helpers.tsx`
  - [ ] `EnvironmentLockBanner` - Warning for environment-controlled settings
  - [ ] `SystemStatusCard` - Display system health with status indicators
  - [ ] `AdminSection` - Role-based section access control wrapper
  - [ ] `DangerousActionConfirm` - Confirmation dialog for dangerous operations
  - [ ] `ConfigurationDiff` - Show before/after changes for updates

### Access Control Components
- [ ] `UnauthorizedAccess` component - Shown when user lacks admin permissions
- [ ] `PermissionGate` component - Conditional rendering based on permissions
- [ ] `AdminLoadingSkeleton` component - Loading state for admin interface

## Configuration Section Components

### Embeddings Configuration
- [ ] Create `resources/js/islands/Settings/components/Admin/EmbeddingsConfiguration.tsx`
  - [ ] Embeddings enable/disable toggle
  - [ ] Provider selection (OpenAI, Anthropic, Ollama)
  - [ ] Model selection based on provider
  - [ ] Vector store status display
  - [ ] Environment lock indicators
  - [ ] Configuration validation and feedback

### Tools Configuration
- [ ] Create `resources/js/islands/Settings/components/Admin/ToolsConfiguration.tsx`
  - [ ] Allowed tools checklist with danger indicators
  - [ ] Shell tools configuration:
    - [ ] Enable/disable toggle
    - [ ] Allowed commands allowlist
    - [ ] Timeout configuration
  - [ ] Filesystem tools configuration:
    - [ ] Enable/disable toggle
    - [ ] File size limits
    - [ ] Directory restrictions
  - [ ] MCP tools configuration:
    - [ ] Enable/disable toggle
    - [ ] Allowed servers list
    - [ ] Server configuration management

### Transparency Configuration
- [ ] Create `resources/js/islands/Settings/components/Admin/TransparencyConfiguration.tsx`
  - [ ] Model info visibility toggles
  - [ ] Toast notification AI attribution
  - [ ] Fragment AI attribution display
  - [ ] Chat session model visibility
  - [ ] Transparency level presets

### Security Configuration
- [ ] Create `resources/js/islands/Settings/components/Admin/SecurityConfiguration.tsx`
  - [ ] Rate limiting configuration
  - [ ] Session timeout settings
  - [ ] Audit logging controls
  - [ ] Authentication requirements
  - [ ] IP whitelisting/blacklisting

### Features Configuration
- [ ] Create `resources/js/islands/Settings/components/Admin/FeaturesConfiguration.tsx`
  - [ ] Feature flag toggles with descriptions
  - [ ] Experimental feature warnings
  - [ ] Beta feature access controls
  - [ ] Feature dependency management

## Tool-Specific Components

### Tool Category Management
- [ ] Create `ToolCategoryCard` component
  - [ ] Category enable/disable with danger warnings
  - [ ] Category-specific configuration controls
  - [ ] Environment lock indicators
  - [ ] Configuration validation feedback

### Tool Allowlist Management
- [ ] Create `AllowedToolsSelector` component
  - [ ] Multi-select for tool categories
  - [ ] Danger indicators for risky tools
  - [ ] Tool capability descriptions
  - [ ] Bulk enable/disable options

### Command Allowlist Editor
- [ ] Create `CommandAllowlistEditor` component
  - [ ] Textarea for command entry
  - [ ] Command validation and suggestions
  - [ ] Dangerous command warnings
  - [ ] Preset allowlist options

## Main Integration

### Admin Configuration Component
- [ ] Create `resources/js/islands/Settings/components/Admin/AdminConfiguration.tsx`
  - [ ] Section-based navigation sidebar
  - [ ] Dynamic content area for active section
  - [ ] Permission checking and access control
  - [ ] Loading states and error handling
  - [ ] Save confirmation and feedback

### Admin Sidebar Navigation
- [ ] Section list with icons and descriptions
- [ ] Active section highlighting
- [ ] Permission-based section visibility
- [ ] Responsive mobile navigation
- [ ] Section-specific status indicators

### Section Header
- [ ] Dynamic section title and description
- [ ] Section-specific help documentation links
- [ ] Quick action buttons (save, reset, etc.)
- [ ] Configuration status indicators

## Settings Integration

### Settings Layout Updates
- [ ] Update `resources/js/islands/Settings/SettingsLayout.tsx`
  - [ ] Add admin tab for users with admin permissions
  - [ ] Conditional tab rendering based on user roles
  - [ ] Consistent styling with existing settings tabs
  - [ ] Mobile-responsive admin navigation

### Route & Navigation
- [ ] Add admin settings route handling
- [ ] Update settings navigation to include admin section
- [ ] Breadcrumb navigation for admin sections
- [ ] Deep linking to specific admin sections

## Validation & Error Handling

### Frontend Validation
- [ ] Real-time validation of configuration values
- [ ] Cross-section dependency validation
- [ ] Environment lock conflict detection
- [ ] Security setting validation (rate limits, timeouts)

### Error States
- [ ] Network error handling with retry options
- [ ] Validation error display with specific guidance
- [ ] Permission error handling with clear messaging
- [ ] Configuration conflict resolution suggestions

### Loading States
- [ ] Section-specific loading indicators
- [ ] Progressive loading of configuration data
- [ ] Optimistic updates with rollback on error
- [ ] Smooth transitions between loading states

## Testing

### Backend Tests
- [ ] Create `tests/Feature/Admin/ConfigurationTest.php`
  - [ ] Admin access control verification
  - [ ] Configuration retrieval with proper locks
  - [ ] Configuration updates with validation
  - [ ] Environment lock enforcement
  - [ ] Audit logging verification
  - [ ] System status accuracy

- [ ] Create `tests/Unit/Services/AdminConfigurationServiceTest.php`
  - [ ] Service method testing in isolation
  - [ ] Environment lock detection logic
  - [ ] Configuration merging and precedence
  - [ ] Validation logic for different sections

### Frontend Tests
- [ ] Component interaction testing
  - [ ] Section navigation and content loading
  - [ ] Configuration updates and persistence
  - [ ] Permission-based access control
  - [ ] Environment lock handling

- [ ] Integration testing
  - [ ] End-to-end admin configuration flow
  - [ ] Permission enforcement across components
  - [ ] Error handling and recovery
  - [ ] Mobile responsive behavior

### Security Testing
- [ ] Authorization bypass attempts
- [ ] Environment lock circumvention tests
- [ ] Audit logging completeness
- [ ] Input validation and sanitization

## Performance & Polish

### Performance Optimization
- [ ] Efficient configuration loading and caching
- [ ] Minimize re-renders with React.memo
- [ ] Debounced configuration updates
- [ ] Lazy loading of admin sections

### User Experience
- [ ] Smooth animations and transitions
- [ ] Clear visual hierarchy and information architecture
- [ ] Contextual help and documentation
- [ ] Keyboard navigation support

### Accessibility
- [ ] Screen reader compatibility
- [ ] ARIA labels and descriptions
- [ ] Focus management in dialogs
- [ ] High contrast mode support
- [ ] Keyboard-only navigation

### Mobile Responsiveness
- [ ] Touch-friendly interface elements
- [ ] Responsive layout for small screens
- [ ] Mobile-optimized navigation
- [ ] Appropriate text and button sizes

## Documentation

### User Documentation
- [ ] Admin settings guide with screenshots
- [ ] Security implications of various settings
- [ ] Troubleshooting common configuration issues
- [ ] Best practices for system administration

### Developer Documentation
- [ ] Admin API documentation
- [ ] Extension points for new admin sections
- [ ] Environment variable configuration guide
- [ ] Security considerations for admin features

### Deployment Documentation
- [ ] Environment variable setup guide
- [ ] Permission configuration instructions
- [ ] Security hardening recommendations
- [ ] Monitoring and alerting setup

## Success Criteria Checklist
- [ ] Admin users can access system-level configuration
- [ ] Environment-locked settings display as read-only with clear indication
- [ ] Configuration changes validate correctly and persist
- [ ] Role-based access controls prevent unauthorized access
- [ ] System status accurately reflects current configuration
- [ ] All admin actions are logged for audit purposes
- [ ] UI clearly distinguishes admin settings from user preferences
- [ ] Dangerous operations require confirmation
- [ ] Mobile experience is fully functional
- [ ] All interactions are accessible to screen readers
- [ ] Performance is smooth with proper caching and optimization