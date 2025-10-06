# SETTINGS-004 Context: Admin Configuration Panels

## Current Environment Configuration

### Environment Variables from config/fragments.php
```php
// System-level configuration that needs admin panels
'embeddings' => [
    'enabled' => env('EMBEDDINGS_ENABLED', false),
    'provider' => env('EMBEDDINGS_PROVIDER', 'openai'),
    'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'version' => env('EMBEDDINGS_VERSION', '1'),
],

'tools' => [
    'allowed' => env('FRAGMENT_TOOLS_ALLOWED') ? explode(',', env('FRAGMENT_TOOLS_ALLOWED')) : [],
    'shell' => [
        'enabled' => env('FRAGMENT_TOOLS_SHELL_ENABLED', false),
        'allowlist' => env('FRAGMENT_TOOLS_SHELL_ALLOWLIST') ? explode(',', env('FRAGMENT_TOOLS_SHELL_ALLOWLIST')) : [...],
        'timeout_seconds' => env('FRAGMENT_TOOLS_SHELL_TIMEOUT', 15),
    ],
    'fs' => [
        'enabled' => env('FRAGMENT_TOOLS_FS_ENABLED', false),
        'max_file_size' => env('FRAGMENT_TOOLS_FS_MAX_FILE_SIZE', 1024 * 1024),
    ],
    'mcp' => [
        'enabled' => env('FRAGMENT_TOOLS_MCP_ENABLED', false),
        'allowed_servers' => env('FRAGMENT_TOOLS_MCP_ALLOWED_SERVERS') ? explode(',', env('FRAGMENT_TOOLS_MCP_ALLOWED_SERVERS')) : [],
    ],
],

'ui' => [
    'show_model_info' => env('AI_SHOW_MODEL_INFO', true),
    'show_in_toasts' => env('AI_SHOW_IN_TOASTS', true),
    'show_in_fragments' => env('AI_SHOW_IN_FRAGMENTS', true),
    'show_in_chat_sessions' => env('AI_SHOW_IN_CHAT_SESSIONS', true),
],
```

### Authorization Requirements
```php
// User model should have admin roles
class User extends Model
{
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super-admin');
    }
    
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }
    
    public function canManageSystemSettings(): bool
    {
        return $this->isAdmin();
    }
}
```

## Admin Configuration Schema

### Admin Settings Structure
```php
// New admin_settings table or JSON column
"admin_settings": {
    "embeddings": {
        "enabled": true,           // Override env if not locked
        "provider": "openai",      // Override env if not locked  
        "model": "text-embedding-3-small",
        "locked_by_env": true,     // Indicates env vars take precedence
    },
    "tools": {
        "allowed": ["shell", "fs"], // Override env if not locked
        "shell": {
            "enabled": false,
            "allowlist": ["ls", "pwd", "cat"],
            "timeout_seconds": 15,
            "locked_by_env": false,
        },
        "fs": {
            "enabled": false,
            "max_file_size": 1048576,
            "locked_by_env": true,
        },
        "mcp": {
            "enabled": false,
            "allowed_servers": [],
            "locked_by_env": false,
        }
    },
    "transparency": {
        "show_model_info": true,
        "show_in_toasts": true, 
        "show_in_fragments": true,
        "show_in_chat_sessions": true,
        "locked_by_env": false,
    },
    "security": {
        "audit_logging": true,
        "rate_limiting": {
            "enabled": true,
            "requests_per_minute": 60,
        },
        "session_timeout": 120, // minutes
        "locked_by_env": false,
    },
    "features": {
        "experimental_ui": false,
        "beta_tools": false,
        "advanced_fragments": false,
        "locked_by_env": false,
    }
}
```

### Configuration Hierarchy
1. **Environment Variables** (highest priority, locks admin settings)
2. **Admin Database Settings** (configurable when not locked)
3. **Application Defaults** (fallback values)

## Implementation Architecture

### Backend Admin Service
```php
// app/Services/AdminConfigurationService.php
class AdminConfigurationService
{
    public function getSystemConfiguration(): array
    {
        return [
            'embeddings' => $this->getEmbeddingsConfig(),
            'tools' => $this->getToolsConfig(),
            'transparency' => $this->getTransparencyConfig(),
            'security' => $this->getSecurityConfig(),
            'features' => $this->getFeaturesConfig(),
            'system_status' => $this->getSystemStatus(),
        ];
    }
    
    public function updateConfiguration(array $config): void
    {
        // Validate against locked settings
        // Update admin_settings
        // Clear relevant caches
        // Log configuration changes
    }
    
    public function getEnvironmentLocks(): array
    {
        // Check which settings are locked by environment variables
        return [
            'embeddings.enabled' => !is_null(env('EMBEDDINGS_ENABLED')),
            'tools.shell.enabled' => !is_null(env('FRAGMENT_TOOLS_SHELL_ENABLED')),
            // ... other environment locks
        ];
    }
    
    private function getSystemStatus(): array
    {
        return [
            'database_connection' => $this->checkDatabaseConnection(),
            'vector_store_status' => $this->checkVectorStore(),
            'ai_providers_status' => $this->checkAIProviders(),
            'tool_capabilities' => $this->checkToolCapabilities(),
        ];
    }
}
```

### Admin Authorization Middleware
```php
// app/Http/Middleware/AdminOnly.php
class AdminOnly
{
    public function handle(Request $request, Closure $next, string $permission = 'admin')
    {
        if (!auth()->user()?->canManageSystemSettings()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return $next($request);
    }
}
```

### API Controller
```php
// app/Http/Controllers/Admin/ConfigurationController.php
class ConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }
    
    public function index()
    {
        $service = app(AdminConfigurationService::class);
        return response()->json($service->getSystemConfiguration());
    }
    
    public function update(AdminConfigurationRequest $request)
    {
        $service = app(AdminConfigurationService::class);
        $service->updateConfiguration($request->validated());
        
        return response()->json(['success' => true]);
    }
    
    public function status()
    {
        $service = app(AdminConfigurationService::class);
        return response()->json($service->getSystemStatus());
    }
}
```

## Frontend Admin Interface

### Admin Settings Navigation
```typescript
// Admin-only tab in settings or separate admin panel
interface AdminConfiguration {
  embeddings: EmbeddingsConfig;
  tools: ToolsConfig;
  transparency: TransparencyConfig;
  security: SecurityConfig;
  features: FeaturesConfig;
  system_status: SystemStatus;
  environment_locks: Record<string, boolean>;
}

interface ConfigSection {
  id: string;
  name: string;
  description: string;
  icon: string;
  permission_required?: string;
}

const ADMIN_SECTIONS: ConfigSection[] = [
  {
    id: 'embeddings',
    name: 'Embeddings & Vector Store',
    description: 'Configure embedding models and vector database',
    icon: 'database',
  },
  {
    id: 'tools',
    name: 'Tool System',
    description: 'Manage shell, filesystem, and MCP tool access',
    icon: 'tool',
  },
  {
    id: 'transparency',
    name: 'AI Transparency',
    description: 'Control visibility of AI model information',
    icon: 'eye',
  },
  {
    id: 'security',
    name: 'Security & Audit',
    description: 'Authentication, rate limiting, and audit settings',
    icon: 'shield',
    permission_required: 'super-admin',
  },
  {
    id: 'features',
    name: 'Feature Flags',
    description: 'Enable experimental and beta features',
    icon: 'flag',
  },
];
```

### Admin Configuration Components
```typescript
// Main admin configuration interface
const AdminConfiguration = () => {
  const { config, isLoading, updateConfig } = useAdminConfiguration();
  const [activeSection, setActiveSection] = useState('embeddings');
  
  if (!config?.user_permissions?.admin) {
    return <UnauthorizedAccess />;
  }
  
  return (
    <div className="grid grid-cols-4 gap-6">
      <AdminSidebar
        sections={ADMIN_SECTIONS}
        activeSection={activeSection}
        onSectionChange={setActiveSection}
        userPermissions={config.user_permissions}
      />
      
      <div className="col-span-3">
        <AdminSectionHeader section={activeSection} />
        
        {activeSection === 'embeddings' && (
          <EmbeddingsConfiguration
            config={config.embeddings}
            locks={config.environment_locks}
            onChange={(updates) => updateConfig('embeddings', updates)}
          />
        )}
        
        {activeSection === 'tools' && (
          <ToolsConfiguration
            config={config.tools}
            locks={config.environment_locks}
            onChange={(updates) => updateConfig('tools', updates)}
          />
        )}
        
        {/* Other sections... */}
      </div>
    </div>
  );
};
```

### Configuration Section Components
```typescript
// Embeddings configuration panel
const EmbeddingsConfiguration = ({ config, locks, onChange }: any) => {
  const isLocked = locks['embeddings.enabled'];
  
  return (
    <div className="space-y-6">
      <EnvironmentLockBanner 
        isLocked={isLocked}
        message="Embeddings settings are controlled by environment variables"
      />
      
      <div className="grid gap-4">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="font-medium">Enable Embeddings</h3>
            <p className="text-sm text-gray-600">
              Allow AI content analysis and vector search
            </p>
          </div>
          <Switch
            checked={config.enabled}
            onCheckedChange={(enabled) => onChange({ enabled })}
            disabled={isLocked}
          />
        </div>
        
        {config.enabled && (
          <>
            <ProviderSelector
              providers={config.available_providers}
              selected={config.provider}
              onSelect={(provider) => onChange({ provider })}
              disabled={locks['embeddings.provider']}
            />
            
            <ModelSelector
              models={config.available_models}
              selected={config.model}
              onSelect={(model) => onChange({ model })}
              disabled={locks['embeddings.model']}
            />
          </>
        )}
      </div>
      
      <SystemStatusCard
        title="Vector Store Status"
        status={config.vector_store_status}
        details={config.vector_store_details}
      />
    </div>
  );
};

// Tools configuration panel
const ToolsConfiguration = ({ config, locks, onChange }: any) => {
  return (
    <div className="space-y-6">
      <AllowedToolsSelector
        allowed={config.allowed}
        available={config.available_tools}
        onChange={(allowed) => onChange({ allowed })}
        disabled={locks['tools.allowed']}
      />
      
      <div className="grid gap-6">
        <ToolCategoryCard
          title="Shell Tools"
          description="Execute shell commands in controlled environment"
          config={config.shell}
          locks={locks}
          onChange={(shell) => onChange({ shell })}
        />
        
        <ToolCategoryCard
          title="Filesystem Tools"
          description="Read and write files within restricted directories"
          config={config.fs}
          locks={locks}
          onChange={(fs) => onChange({ fs })}
        />
        
        <ToolCategoryCard
          title="MCP Tools"
          description="Model Context Protocol tool integrations"
          config={config.mcp}
          locks={locks}
          onChange={(mcp) => onChange({ mcp })}
        />
      </div>
    </div>
  );
};
```

### Helper Components
```typescript
// Environment lock banner
const EnvironmentLockBanner = ({ isLocked, message }: any) => {
  if (!isLocked) return null;
  
  return (
    <Alert className="border-orange-200 bg-orange-50">
      <Lock className="h-4 w-4" />
      <AlertTitle>Environment Controlled</AlertTitle>
      <AlertDescription>{message}</AlertDescription>
    </Alert>
  );
};

// System status indicator
const SystemStatusCard = ({ title, status, details }: any) => {
  const statusColor = status === 'healthy' ? 'green' : 
                     status === 'warning' ? 'yellow' : 'red';
  
  return (
    <div className="border rounded-lg p-4">
      <div className="flex items-center justify-between">
        <h4 className="font-medium">{title}</h4>
        <Badge className={`bg-${statusColor}-100 text-${statusColor}-800`}>
          {status}
        </Badge>
      </div>
      {details && (
        <p className="text-sm text-gray-600 mt-1">{details}</p>
      )}
    </div>
  );
};

// Tool category configuration
const ToolCategoryCard = ({ title, description, config, locks, onChange }: any) => {
  const isLocked = locks[`tools.${config.category}.enabled`];
  
  return (
    <div className="border rounded-lg p-4">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h4 className="font-medium">{title}</h4>
          <p className="text-sm text-gray-600">{description}</p>
        </div>
        <Switch
          checked={config.enabled}
          onCheckedChange={(enabled) => onChange({ ...config, enabled })}
          disabled={isLocked}
        />
      </div>
      
      {config.enabled && (
        <ToolSpecificControls
          category={config.category}
          config={config}
          locks={locks}
          onChange={onChange}
        />
      )}
    </div>
  );
};
```

## Security & Audit

### Admin Action Logging
```php
// Log all admin configuration changes
class AdminConfigurationService
{
    private function logConfigurationChange(string $section, array $changes, User $user): void
    {
        AdminAuditLog::create([
            'user_id' => $user->id,
            'action' => 'configuration_update',
            'section' => $section,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
    }
}
```

### Access Control
```typescript
// Role-based component rendering
const AdminSection = ({ requiredPermission, children }: any) => {
  const { user } = useAuth();
  
  if (requiredPermission && !user.permissions.includes(requiredPermission)) {
    return <UnauthorizedSection requiredPermission={requiredPermission} />;
  }
  
  return children;
};
```

## Integration Points

### Settings Integration
- Admin tab appears only for admin users
- Separate from user preferences but consistent UI
- Share common components and patterns

### System Health Monitoring
- Real-time status indicators
- Configuration validation
- Dependency checking

### Audit & Compliance
- Complete change logging
- User action tracking
- Configuration history