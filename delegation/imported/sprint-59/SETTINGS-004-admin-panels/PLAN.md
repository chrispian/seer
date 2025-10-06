# SETTINGS-004 Implementation Plan: Admin Configuration Panels

## Phase 1: Backend Foundation (3-4 hours)

### 1.1 Create Admin Configuration Service (2h)
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
            'environment_locks' => $this->getEnvironmentLocks(),
        ];
    }
    
    public function updateConfiguration(string $section, array $config): void
    {
        $currentConfig = $this->getAdminSettings();
        $locks = $this->getEnvironmentLocks();
        
        // Validate against locked settings
        $this->validateAgainstLocks($section, $config, $locks);
        
        // Update configuration
        $currentConfig[$section] = array_merge($currentConfig[$section] ?? [], $config);
        $this->saveAdminSettings($currentConfig);
        
        // Clear relevant caches
        $this->clearConfigurationCache($section);
        
        // Log changes
        $this->logConfigurationChange($section, $config, auth()->user());
    }
    
    private function getEmbeddingsConfig(): array
    {
        $envConfig = config('fragments.embeddings');
        $adminConfig = $this->getAdminSettings()['embeddings'] ?? [];
        
        return [
            'enabled' => $this->getConfigValue('embeddings.enabled', $envConfig['enabled'], $adminConfig['enabled'] ?? false),
            'provider' => $this->getConfigValue('embeddings.provider', $envConfig['provider'], $adminConfig['provider'] ?? 'openai'),
            'model' => $this->getConfigValue('embeddings.model', $envConfig['model'], $adminConfig['model'] ?? 'text-embedding-3-small'),
            'available_providers' => ['openai', 'anthropic', 'ollama'],
            'available_models' => $this->getAvailableEmbeddingModels(),
            'vector_store_status' => $this->checkVectorStoreStatus(),
        ];
    }
    
    private function getToolsConfig(): array
    {
        $envConfig = config('fragments.tools');
        $adminConfig = $this->getAdminSettings()['tools'] ?? [];
        
        return [
            'allowed' => $this->getConfigValue('tools.allowed', $envConfig['allowed'], $adminConfig['allowed'] ?? []),
            'shell' => $this->getShellConfig($envConfig['shell'], $adminConfig['shell'] ?? []),
            'fs' => $this->getFsConfig($envConfig['fs'], $adminConfig['fs'] ?? []),
            'mcp' => $this->getMcpConfig($envConfig['mcp'], $adminConfig['mcp'] ?? []),
            'available_tools' => ['shell', 'fs', 'mcp'],
        ];
    }
    
    private function getEnvironmentLocks(): array
    {
        return [
            'embeddings.enabled' => !is_null(env('EMBEDDINGS_ENABLED')),
            'embeddings.provider' => !is_null(env('EMBEDDINGS_PROVIDER')),
            'embeddings.model' => !is_null(env('OPENAI_EMBEDDING_MODEL')),
            'tools.allowed' => !is_null(env('FRAGMENT_TOOLS_ALLOWED')),
            'tools.shell.enabled' => !is_null(env('FRAGMENT_TOOLS_SHELL_ENABLED')),
            'tools.fs.enabled' => !is_null(env('FRAGMENT_TOOLS_FS_ENABLED')),
            'tools.mcp.enabled' => !is_null(env('FRAGMENT_TOOLS_MCP_ENABLED')),
            'transparency.show_model_info' => !is_null(env('AI_SHOW_MODEL_INFO')),
        ];
    }
    
    private function getConfigValue(string $key, $envValue, $adminValue)
    {
        // Environment takes precedence if set
        return $envValue ?? $adminValue;
    }
}
```

### 1.2 Create Admin Settings Storage (1h)
```php
// Migration for admin_settings table or use JSON column on users table
// app/Models/AdminSetting.php
class AdminSetting extends Model
{
    protected $fillable = ['key', 'value', 'section'];
    protected $casts = ['value' => 'array'];
    
    public static function getSection(string $section): array
    {
        return static::where('section', $section)
            ->pluck('value', 'key')
            ->toArray();
    }
    
    public static function setSection(string $section, array $config): void
    {
        foreach ($config as $key => $value) {
            static::updateOrCreate(
                ['section' => $section, 'key' => $key],
                ['value' => $value]
            );
        }
    }
}

// Or use settings service pattern
class AdminSettingsService
{
    private function getAdminSettings(): array
    {
        return Cache::remember('admin_settings', 3600, function () {
            return AdminSetting::all()
                ->groupBy('section')
                ->map(fn($items) => $items->pluck('value', 'key')->toArray())
                ->toArray();
        });
    }
    
    private function saveAdminSettings(array $settings): void
    {
        foreach ($settings as $section => $config) {
            AdminSetting::setSection($section, $config);
        }
        Cache::forget('admin_settings');
    }
}
```

### 1.3 Create Admin Controller & Middleware (1h)
```php
// app/Http/Middleware/AdminOnly.php
class AdminOnly
{
    public function handle(Request $request, Closure $next, string $permission = 'admin')
    {
        if (!auth()->user()?->canManageSystemSettings()) {
            abort(403, 'Unauthorized access to admin settings');
        }
        
        return $next($request);
    }
}

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
        return response()->json([
            'configuration' => $service->getSystemConfiguration(),
            'user_permissions' => [
                'admin' => auth()->user()->isAdmin(),
                'super_admin' => auth()->user()->isSuperAdmin(),
            ],
        ]);
    }
    
    public function update(AdminConfigurationRequest $request)
    {
        $service = app(AdminConfigurationService::class);
        $service->updateConfiguration(
            $request->input('section'),
            $request->input('config')
        );
        
        return response()->json(['success' => true]);
    }
    
    public function status()
    {
        $service = app(AdminConfigurationService::class);
        return response()->json($service->getSystemStatus());
    }
}

// app/Http/Requests/AdminConfigurationRequest.php
class AdminConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canManageSystemSettings() ?? false;
    }
    
    public function rules(): array
    {
        return [
            'section' => 'required|string|in:embeddings,tools,transparency,security,features',
            'config' => 'required|array',
        ];
    }
}
```

## Phase 2: Frontend Foundation (2-3 hours)

### 2.1 Create Admin Types & Hooks (1h)
```typescript
// resources/js/types/admin.ts
export interface AdminConfiguration {
  embeddings: EmbeddingsConfig;
  tools: ToolsConfig;
  transparency: TransparencyConfig;
  security: SecurityConfig;
  features: FeaturesConfig;
  system_status: SystemStatus;
  environment_locks: Record<string, boolean>;
}

export interface EmbeddingsConfig {
  enabled: boolean;
  provider: string;
  model: string;
  available_providers: string[];
  available_models: string[];
  vector_store_status: 'healthy' | 'warning' | 'error';
}

export interface ToolsConfig {
  allowed: string[];
  shell: ShellToolConfig;
  fs: FsToolConfig;
  mcp: McpToolConfig;
  available_tools: string[];
}

export interface UserPermissions {
  admin: boolean;
  super_admin: boolean;
}

// resources/js/hooks/useAdminConfiguration.ts
export const useAdminConfiguration = () => {
  const { data, isLoading, error } = useQuery({
    queryKey: ['admin-configuration'],
    queryFn: () => api.get('/admin/configuration').then(res => res.data),
    enabled: !!useAuth().user?.permissions?.admin,
  });
  
  const updateConfig = useMutation({
    mutationFn: ({ section, config }: { section: string; config: any }) =>
      api.patch('/admin/configuration', { section, config }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-configuration'] });
    },
  });
  
  return {
    config: data?.configuration as AdminConfiguration | undefined,
    userPermissions: data?.user_permissions as UserPermissions | undefined,
    isLoading,
    error,
    updateConfig: updateConfig.mutate,
    isUpdating: updateConfig.isPending,
  };
};
```

### 2.2 Create Admin Constants (30min)
```typescript
// resources/js/constants/admin.ts
export const ADMIN_SECTIONS = [
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
    permission_required: 'super_admin',
  },
  {
    id: 'features',
    name: 'Feature Flags',
    description: 'Enable experimental and beta features',
    icon: 'flag',
  },
] as const;

export const TOOL_CATEGORIES = {
  shell: {
    name: 'Shell Tools',
    description: 'Execute shell commands in controlled environment',
    dangerous: true,
  },
  fs: {
    name: 'Filesystem Tools',
    description: 'Read and write files within restricted directories',
    dangerous: true,
  },
  mcp: {
    name: 'MCP Tools',
    description: 'Model Context Protocol tool integrations',
    dangerous: false,
  },
} as const;
```

### 2.3 Create Helper Components (1h)
```typescript
// resources/js/islands/Settings/components/Admin/helpers.tsx
export const EnvironmentLockBanner = ({ isLocked, message }: any) => {
  if (!isLocked) return null;
  
  return (
    <Alert className="border-orange-200 bg-orange-50 mb-4">
      <Lock className="h-4 w-4" />
      <AlertTitle>Environment Controlled</AlertTitle>
      <AlertDescription>{message}</AlertDescription>
    </Alert>
  );
};

export const SystemStatusCard = ({ title, status, details, onRefresh }: any) => {
  const statusConfig = {
    healthy: { color: 'green', icon: CheckCircle },
    warning: { color: 'yellow', icon: AlertTriangle },
    error: { color: 'red', icon: XCircle },
  };
  
  const { color, icon: Icon } = statusConfig[status] || statusConfig.error;
  
  return (
    <div className="border rounded-lg p-4">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-2">
          <Icon className={`h-4 w-4 text-${color}-600`} />
          <h4 className="font-medium">{title}</h4>
        </div>
        <div className="flex items-center space-x-2">
          <Badge className={`bg-${color}-100 text-${color}-800`}>
            {status}
          </Badge>
          {onRefresh && (
            <Button variant="outline" size="sm" onClick={onRefresh}>
              <RefreshCw className="h-3 w-3" />
            </Button>
          )}
        </div>
      </div>
      {details && (
        <p className="text-sm text-gray-600 mt-2">{details}</p>
      )}
    </div>
  );
};

export const AdminSection = ({ requiredPermission, userPermissions, children }: any) => {
  if (requiredPermission && !userPermissions?.[requiredPermission]) {
    return (
      <div className="border rounded-lg p-8 text-center">
        <Shield className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">
          Additional Permissions Required
        </h3>
        <p className="text-gray-600">
          You need {requiredPermission.replace('_', ' ')} permissions to access this section.
        </p>
      </div>
    );
  }
  
  return children;
};
```

## Phase 3: Section Components (3-4 hours)

### 3.1 Create Embeddings Configuration (1h)
```typescript
// resources/js/islands/Settings/components/Admin/EmbeddingsConfiguration.tsx
interface EmbeddingsConfigurationProps {
  config: EmbeddingsConfig;
  locks: Record<string, boolean>;
  onChange: (updates: Partial<EmbeddingsConfig>) => void;
}

export const EmbeddingsConfiguration = ({ config, locks, onChange }: EmbeddingsConfigurationProps) => {
  return (
    <div className="space-y-6">
      <EnvironmentLockBanner
        isLocked={locks['embeddings.enabled']}
        message="Embeddings settings are controlled by EMBEDDINGS_ENABLED environment variable"
      />
      
      <div className="grid gap-6">
        <div className="flex items-center justify-between p-4 border rounded-lg">
          <div>
            <h3 className="font-medium">Enable Embeddings</h3>
            <p className="text-sm text-gray-600">
              Allow AI content analysis and vector search capabilities
            </p>
          </div>
          <Switch
            checked={config.enabled}
            onCheckedChange={(enabled) => onChange({ enabled })}
            disabled={locks['embeddings.enabled']}
          />
        </div>
        
        {config.enabled && (
          <>
            <div className="p-4 border rounded-lg">
              <label className="block text-sm font-medium mb-2">
                Embedding Provider
              </label>
              <Select
                value={config.provider}
                onValueChange={(provider) => onChange({ provider })}
                disabled={locks['embeddings.provider']}
              >
                {config.available_providers.map(provider => (
                  <SelectItem key={provider} value={provider}>
                    {provider.charAt(0).toUpperCase() + provider.slice(1)}
                  </SelectItem>
                ))}
              </Select>
              {locks['embeddings.provider'] && (
                <p className="text-sm text-orange-600 mt-1">
                  Controlled by EMBEDDINGS_PROVIDER environment variable
                </p>
              )}
            </div>
            
            <div className="p-4 border rounded-lg">
              <label className="block text-sm font-medium mb-2">
                Embedding Model
              </label>
              <Select
                value={config.model}
                onValueChange={(model) => onChange({ model })}
                disabled={locks['embeddings.model']}
              >
                {config.available_models.map(model => (
                  <SelectItem key={model} value={model}>
                    {model}
                  </SelectItem>
                ))}
              </Select>
              {locks['embeddings.model'] && (
                <p className="text-sm text-orange-600 mt-1">
                  Controlled by OPENAI_EMBEDDING_MODEL environment variable
                </p>
              )}
            </div>
          </>
        )}
      </div>
      
      <SystemStatusCard
        title="Vector Store Status"
        status={config.vector_store_status}
        details={getVectorStoreDetails(config.vector_store_status)}
      />
    </div>
  );
};

const getVectorStoreDetails = (status: string) => {
  switch (status) {
    case 'healthy':
      return 'Vector store is operational and accepting queries';
    case 'warning':
      return 'Vector store is operational but experiencing performance issues';
    case 'error':
      return 'Vector store is not responding or misconfigured';
    default:
      return 'Vector store status unknown';
  }
};
```

### 3.2 Create Tools Configuration (1.5h)
```typescript
// resources/js/islands/Settings/components/Admin/ToolsConfiguration.tsx
interface ToolsConfigurationProps {
  config: ToolsConfig;
  locks: Record<string, boolean>;
  onChange: (updates: Partial<ToolsConfig>) => void;
}

export const ToolsConfiguration = ({ config, locks, onChange }: ToolsConfigurationProps) => {
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
          category="shell"
          title={TOOL_CATEGORIES.shell.name}
          description={TOOL_CATEGORIES.shell.description}
          config={config.shell}
          locks={locks}
          onChange={(shell) => onChange({ shell })}
          dangerous={TOOL_CATEGORIES.shell.dangerous}
        />
        
        <ToolCategoryCard
          category="fs"
          title={TOOL_CATEGORIES.fs.name}
          description={TOOL_CATEGORIES.fs.description}
          config={config.fs}
          locks={locks}
          onChange={(fs) => onChange({ fs })}
          dangerous={TOOL_CATEGORIES.fs.dangerous}
        />
        
        <ToolCategoryCard
          category="mcp"
          title={TOOL_CATEGORIES.mcp.name}
          description={TOOL_CATEGORIES.mcp.description}
          config={config.mcp}
          locks={locks}
          onChange={(mcp) => onChange({ mcp })}
          dangerous={TOOL_CATEGORIES.mcp.dangerous}
        />
      </div>
    </div>
  );
};

const AllowedToolsSelector = ({ allowed, available, onChange, disabled }: any) => (
  <div className="p-4 border rounded-lg">
    <EnvironmentLockBanner
      isLocked={disabled}
      message="Tool allowlist is controlled by FRAGMENT_TOOLS_ALLOWED environment variable"
    />
    
    <h3 className="font-medium mb-4">Allowed Tool Categories</h3>
    <div className="grid grid-cols-2 gap-4">
      {available.map((tool: string) => (
        <label key={tool} className="flex items-center space-x-2">
          <Checkbox
            checked={allowed.includes(tool)}
            onCheckedChange={(checked) => {
              const newAllowed = checked
                ? [...allowed, tool]
                : allowed.filter((t: string) => t !== tool);
              onChange(newAllowed);
            }}
            disabled={disabled}
          />
          <span className="text-sm font-medium">{tool}</span>
          {TOOL_CATEGORIES[tool as keyof typeof TOOL_CATEGORIES]?.dangerous && (
            <Badge variant="destructive" className="text-xs">Dangerous</Badge>
          )}
        </label>
      ))}
    </div>
  </div>
);

const ToolCategoryCard = ({ category, title, description, config, locks, onChange, dangerous }: any) => {
  const isLocked = locks[`tools.${category}.enabled`];
  
  return (
    <div className="border rounded-lg">
      <div className="p-4 border-b">
        <div className="flex items-center justify-between">
          <div>
            <div className="flex items-center space-x-2">
              <h4 className="font-medium">{title}</h4>
              {dangerous && (
                <Badge variant="destructive" className="text-xs">Dangerous</Badge>
              )}
            </div>
            <p className="text-sm text-gray-600">{description}</p>
          </div>
          <Switch
            checked={config.enabled}
            onCheckedChange={(enabled) => onChange({ ...config, enabled })}
            disabled={isLocked}
          />
        </div>
        {isLocked && (
          <p className="text-sm text-orange-600 mt-2">
            Controlled by FRAGMENT_TOOLS_{category.toUpperCase()}_ENABLED environment variable
          </p>
        )}
      </div>
      
      {config.enabled && (
        <div className="p-4">
          <ToolSpecificControls
            category={category}
            config={config}
            locks={locks}
            onChange={onChange}
          />
        </div>
      )}
    </div>
  );
};

const ToolSpecificControls = ({ category, config, locks, onChange }: any) => {
  switch (category) {
    case 'shell':
      return (
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">
              Allowed Commands (comma-separated)
            </label>
            <Textarea
              value={config.allowlist?.join(', ') || ''}
              onChange={(e) => onChange({
                ...config,
                allowlist: e.target.value.split(',').map(s => s.trim()).filter(Boolean)
              })}
              disabled={locks[`tools.shell.allowlist`]}
              placeholder="ls, pwd, cat, grep, find"
            />
          </div>
          <div>
            <label className="block text-sm font-medium mb-2">
              Timeout (seconds)
            </label>
            <Input
              type="number"
              value={config.timeout_seconds}
              onChange={(e) => onChange({ ...config, timeout_seconds: parseInt(e.target.value) })}
              disabled={locks[`tools.shell.timeout`]}
              min={1}
              max={300}
            />
          </div>
        </div>
      );
      
    case 'fs':
      return (
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">
              Max File Size (bytes)
            </label>
            <Input
              type="number"
              value={config.max_file_size}
              onChange={(e) => onChange({ ...config, max_file_size: parseInt(e.target.value) })}
              disabled={locks[`tools.fs.max_file_size`]}
            />
          </div>
        </div>
      );
      
    case 'mcp':
      return (
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">
              Allowed Servers (comma-separated)
            </label>
            <Textarea
              value={config.allowed_servers?.join(', ') || ''}
              onChange={(e) => onChange({
                ...config,
                allowed_servers: e.target.value.split(',').map(s => s.trim()).filter(Boolean)
              })}
              disabled={locks[`tools.mcp.allowed_servers`]}
              placeholder="server1, server2"
            />
          </div>
        </div>
      );
      
    default:
      return null;
  }
};
```

### 3.3 Create Other Configuration Sections (1h)
```typescript
// resources/js/islands/Settings/components/Admin/TransparencyConfiguration.tsx
export const TransparencyConfiguration = ({ config, locks, onChange }: any) => {
  const transparencyOptions = [
    {
      key: 'show_model_info',
      name: 'Show Model Information',
      description: 'Display AI model details in the interface',
    },
    {
      key: 'show_in_toasts',
      name: 'Show in Toast Notifications',
      description: 'Include model info in notification toasts',
    },
    {
      key: 'show_in_fragments',
      name: 'Show in Fragments',
      description: 'Display model attribution in fragment content',
    },
    {
      key: 'show_in_chat_sessions',
      name: 'Show in Chat Sessions',
      description: 'Display model info in chat interfaces',
    },
  ];
  
  return (
    <div className="space-y-4">
      {transparencyOptions.map(option => (
        <div key={option.key} className="flex items-center justify-between p-4 border rounded-lg">
          <div>
            <h4 className="font-medium">{option.name}</h4>
            <p className="text-sm text-gray-600">{option.description}</p>
          </div>
          <Switch
            checked={config[option.key]}
            onCheckedChange={(checked) => onChange({ [option.key]: checked })}
            disabled={locks[`transparency.${option.key}`]}
          />
        </div>
      ))}
    </div>
  );
};

// Similar components for SecurityConfiguration and FeaturesConfiguration
```

## Phase 4: Main Admin Component (1-2 hours)

### 4.1 Create Main Admin Configuration Component (1.5h)
```typescript
// resources/js/islands/Settings/components/Admin/AdminConfiguration.tsx
export const AdminConfiguration = () => {
  const { config, userPermissions, isLoading, updateConfig, isUpdating } = useAdminConfiguration();
  const [activeSection, setActiveSection] = useState('embeddings');
  
  if (!userPermissions?.admin) {
    return <UnauthorizedAccess />;
  }
  
  if (isLoading) {
    return <AdminLoadingSkeleton />;
  }
  
  const handleSectionUpdate = (section: string, updates: any) => {
    updateConfig({ section, config: updates });
  };
  
  return (
    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <AdminSidebar
        sections={ADMIN_SECTIONS}
        activeSection={activeSection}
        onSectionChange={setActiveSection}
        userPermissions={userPermissions}
      />
      
      <div className="lg:col-span-3">
        <AdminSectionHeader 
          section={ADMIN_SECTIONS.find(s => s.id === activeSection)} 
        />
        
        <AdminSection
          requiredPermission={ADMIN_SECTIONS.find(s => s.id === activeSection)?.permission_required}
          userPermissions={userPermissions}
        >
          {activeSection === 'embeddings' && (
            <EmbeddingsConfiguration
              config={config?.embeddings}
              locks={config?.environment_locks}
              onChange={(updates) => handleSectionUpdate('embeddings', updates)}
            />
          )}
          
          {activeSection === 'tools' && (
            <ToolsConfiguration
              config={config?.tools}
              locks={config?.environment_locks}
              onChange={(updates) => handleSectionUpdate('tools', updates)}
            />
          )}
          
          {activeSection === 'transparency' && (
            <TransparencyConfiguration
              config={config?.transparency}
              locks={config?.environment_locks}
              onChange={(updates) => handleSectionUpdate('transparency', updates)}
            />
          )}
          
          {/* Other sections... */}
        </AdminSection>
        
        {isUpdating && (
          <div className="fixed bottom-4 right-4">
            <Alert className="bg-blue-50 border-blue-200">
              <Loader className="h-4 w-4 animate-spin" />
              <AlertDescription>Saving configuration...</AlertDescription>
            </Alert>
          </div>
        )}
      </div>
    </div>
  );
};

const AdminSidebar = ({ sections, activeSection, onSectionChange, userPermissions }: any) => (
  <div className="space-y-2">
    {sections.map((section: any) => (
      <button
        key={section.id}
        onClick={() => onSectionChange(section.id)}
        disabled={section.permission_required && !userPermissions?.[section.permission_required]}
        className={`w-full text-left p-3 rounded-lg border transition-colors ${
          activeSection === section.id
            ? 'bg-blue-50 border-blue-200'
            : 'hover:bg-gray-50'
        } ${
          section.permission_required && !userPermissions?.[section.permission_required]
            ? 'opacity-50 cursor-not-allowed'
            : ''
        }`}
      >
        <div className="flex items-center space-x-3">
          <div className="h-4 w-4">{/* Icon */}</div>
          <div>
            <div className="font-medium text-sm">{section.name}</div>
            <div className="text-xs text-gray-600">{section.description}</div>
          </div>
        </div>
      </button>
    ))}
  </div>
);
```

### 4.2 Integrate with Settings Layout (30min)
```typescript
// Update resources/js/islands/Settings/SettingsLayout.tsx
const SettingsLayout = () => {
  const { user } = useAuth();
  
  const tabs = [
    { id: 'profile', name: 'Profile', component: ProfileTab },
    { id: 'preferences', name: 'Preferences', component: PreferencesTab },
    { id: 'appearance', name: 'Appearance', component: AppearanceTab },
    ...(user?.permissions?.admin ? [
      { id: 'admin', name: 'Administration', component: AdminConfiguration }
    ] : []),
  ];
  
  // ... rest of component
};
```

## Phase 5: Testing & Polish (1-2 hours)

### 5.1 Backend Tests (1h)
```php
// tests/Feature/Admin/ConfigurationTest.php
class ConfigurationTest extends TestCase
{
    public function test_admin_can_access_configuration()
    public function test_non_admin_cannot_access_configuration()
    public function test_can_update_non_locked_settings()
    public function test_cannot_update_environment_locked_settings()
    public function test_configuration_changes_are_logged()
    public function test_system_status_is_accurate()
}
```

### 5.2 Frontend Tests & Polish (1h)
```typescript
// Component tests for admin interface
// Access control verification
// Environment lock handling
// Configuration update flows
```

## Success Metrics
- [ ] Admin users can access system-level configuration
- [ ] Environment-locked settings display as read-only with clear indication
- [ ] Configuration changes validate and persist correctly
- [ ] Role-based access controls prevent unauthorized access
- [ ] System status accurately reflects configuration state
- [ ] All admin actions are logged for audit purposes
- [ ] UI clearly distinguishes between user and admin settings

## Dependencies
- User role/permission system
- Environment variable configuration
- Existing settings infrastructure
- Audit logging system
- Admin middleware and authorization