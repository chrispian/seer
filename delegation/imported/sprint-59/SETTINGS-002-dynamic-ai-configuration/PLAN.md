# SETTINGS-002 Implementation Plan: Dynamic AI Provider Configuration

## Phase 1: Backend Provider Service (4-5 hours)

### 1.1 Create AI Provider Service (2h)
```php
// app/Services/AIProviderService.php
class AIProviderService
{
    public function getAvailableProviders(): array
    {
        // Load from config/fragments.php providers
        // Check API key status for each provider
        // Return with availability status
    }
    
    public function getProviderModels(string $provider): array
    {
        // Get text_models and embedding_models for provider
        // Include metadata (context_length, capabilities)
        // Validate provider exists in config
    }
    
    public function validateConfiguration(array $config): array
    {
        // Validate provider exists
        // Validate model exists for provider
        // Check parameter limits (context_length, etc.)
        // Return validation results with suggestions
    }
    
    public function checkProviderStatus(string $provider): array
    {
        // Check API key presence via config_keys
        // Test connectivity if possible (optional)
        // Return status with missing requirements
    }
}
```

### 1.2 Create API Controller (1h)
```php
// app/Http/Controllers/Api/AIProvidersController.php
class AIProvidersController extends Controller
{
    public function index()                         // GET /api/ai/providers
    public function show(string $provider)         // GET /api/ai/providers/{provider}
    public function validate(ValidateConfigRequest $request) // POST /api/ai/providers/validate
    public function status()                       // GET /api/ai/providers/status
}
```

### 1.3 Add API Routes (30min)
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->prefix('ai')->group(function () {
    Route::get('/providers', [AIProvidersController::class, 'index']);
    Route::get('/providers/{provider}', [AIProvidersController::class, 'show']);
    Route::post('/providers/validate', [AIProvidersController::class, 'validate']);
    Route::get('/providers/status', [AIProvidersController::class, 'status']);
});
```

### 1.4 Create Request Validation (30min)
```php
// app/Http/Requests/ValidateConfigRequest.php
class ValidateConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'provider' => 'required|string',
            'model' => 'required|string',
            'context_length' => 'required|integer|min:1024',
            'streaming' => 'boolean',
            'auto_title' => 'boolean',
        ];
    }
}
```

## Phase 2: Frontend API Integration (3-4 hours)

### 2.1 Create API Hooks (1.5h)
```typescript
// resources/js/hooks/useAIProviders.ts
export const useAIProviders = () => {
  const { data: providers, isLoading, error } = useQuery({
    queryKey: ['ai-providers'],
    queryFn: () => api.get('/ai/providers').then(res => res.data),
  });
  
  return { providers, isLoading, error };
};

// resources/js/hooks/useProviderModels.ts
export const useProviderModels = (provider: string) => {
  return useQuery({
    queryKey: ['provider-models', provider],
    queryFn: () => api.get(`/ai/providers/${provider}`).then(res => res.data),
    enabled: !!provider,
  });
};

// resources/js/hooks/useProviderStatus.ts
export const useProviderStatus = () => {
  return useQuery({
    queryKey: ['provider-status'],
    queryFn: () => api.get('/ai/providers/status').then(res => res.data),
    refetchInterval: 30000, // Check status every 30 seconds
  });
};
```

### 2.2 Create Configuration Validation Hook (1h)
```typescript
// resources/js/hooks/useConfigValidation.ts
export const useConfigValidation = () => {
  const mutation = useMutation({
    mutationFn: (config: AIConfig) => 
      api.post('/ai/providers/validate', config).then(res => res.data),
  });
  
  const validateConfig = useCallback((config: AIConfig) => {
    return mutation.mutateAsync(config);
  }, [mutation]);
  
  return {
    validateConfig,
    isValidating: mutation.isPending,
    validationResult: mutation.data,
    validationError: mutation.error,
  };
};
```

### 2.3 Create Type Definitions (30min)
```typescript
// resources/js/types/ai.ts
interface Provider {
  id: string;
  name: string;
  status: 'available' | 'requires_key' | 'unavailable';
  capabilities: string[];
  requirements: string[];
  models: {
    text: Model[];
    embedding: Model[];
  };
}

interface Model {
  id: string;
  name: string;
  context_length: number;
  capabilities?: string[];
  metadata?: {
    cost?: 'low' | 'medium' | 'high';
    performance?: 'fast' | 'balanced' | 'quality';
  };
}

interface AIConfig {
  provider: string;
  model: string;
  context_length: number;
  streaming: boolean;
  auto_title: boolean;
}

interface ValidationResult {
  valid: boolean;
  errors: string[];
  warnings: string[];
  suggestions: string[];
}
```

## Phase 3: Enhanced UI Components (4-5 hours)

### 3.1 Create Provider Selector Component (2h)
```typescript
// resources/js/islands/Settings/components/ProviderSelector.tsx
interface ProviderSelectorProps {
  providers: Provider[];
  selected: string;
  onSelect: (provider: string) => void;
  providerStatus: Record<string, any>;
}

export const ProviderSelector = ({ providers, selected, onSelect, providerStatus }: ProviderSelectorProps) => {
  return (
    <div className="space-y-3">
      <label className="text-sm font-medium">AI Provider</label>
      <div className="grid gap-3">
        {providers.map(provider => (
          <ProviderCard
            key={provider.id}
            provider={provider}
            selected={selected === provider.id}
            status={providerStatus[provider.id]}
            onSelect={() => onSelect(provider.id)}
          />
        ))}
      </div>
    </div>
  );
};

const ProviderCard = ({ provider, selected, status, onSelect }: any) => {
  const isAvailable = status?.available ?? provider.status === 'available';
  
  return (
    <div 
      className={`border rounded-lg p-4 cursor-pointer transition-colors ${
        selected 
          ? 'border-blue-500 bg-blue-50' 
          : isAvailable 
            ? 'border-gray-200 hover:border-gray-300' 
            : 'border-red-200 bg-red-50'
      }`}
      onClick={onSelect}
    >
      <div className="flex items-center justify-between">
        <div>
          <h3 className="font-medium">{provider.name}</h3>
          <div className="flex items-center space-x-2 mt-1">
            <StatusBadge status={isAvailable ? 'available' : 'requires_setup'} />
            {provider.capabilities.map(cap => (
              <Badge key={cap} variant="outline" className="text-xs">
                {cap}
              </Badge>
            ))}
          </div>
        </div>
      </div>
      
      {!isAvailable && provider.requirements.length > 0 && (
        <div className="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-sm">
          <p className="font-medium text-yellow-800">Setup Required:</p>
          <ul className="mt-1 text-yellow-700">
            {provider.requirements.map(req => (
              <li key={req}>• {req}</li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
};
```

### 3.2 Create Model Selector Component (1.5h)
```typescript
// resources/js/islands/Settings/components/ModelSelector.tsx
interface ModelSelectorProps {
  models: Model[];
  selected: string;
  onSelect: (model: string) => void;
  disabled?: boolean;
}

export const ModelSelector = ({ models, selected, onSelect, disabled }: ModelSelectorProps) => {
  if (!models.length) {
    return (
      <div className="text-sm text-gray-500">
        No models available for selected provider
      </div>
    );
  }
  
  return (
    <div className="space-y-2">
      <label className="text-sm font-medium">Model</label>
      <Select value={selected} onValueChange={onSelect} disabled={disabled}>
        <SelectTrigger>
          <SelectValue placeholder="Select a model" />
        </SelectTrigger>
        <SelectContent>
          {models.map(model => (
            <SelectItem key={model.id} value={model.id}>
              <div className="flex items-center justify-between w-full">
                <span>{model.name}</span>
                <div className="flex items-center space-x-2 ml-4">
                  <Badge variant="outline" className="text-xs">
                    {(model.context_length / 1000).toFixed(0)}k
                  </Badge>
                  {model.metadata?.cost && (
                    <Badge 
                      variant={
                        model.metadata.cost === 'low' ? 'success' : 
                        model.metadata.cost === 'medium' ? 'warning' : 'destructive'
                      }
                      className="text-xs"
                    >
                      {model.metadata.cost}
                    </Badge>
                  )}
                </div>
              </div>
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      
      {selected && models.find(m => m.id === selected) && (
        <ModelDetails model={models.find(m => m.id === selected)!} />
      )}
    </div>
  );
};

const ModelDetails = ({ model }: { model: Model }) => (
  <div className="p-3 bg-gray-50 rounded-lg text-sm">
    <div className="flex justify-between items-center">
      <span>Context Length:</span>
      <span className="font-medium">{model.context_length.toLocaleString()} tokens</span>
    </div>
    {model.capabilities && (
      <div className="mt-2">
        <span>Capabilities:</span>
        <div className="flex flex-wrap gap-1 mt-1">
          {model.capabilities.map(cap => (
            <Badge key={cap} variant="outline" className="text-xs">
              {cap}
            </Badge>
          ))}
        </div>
      </div>
    )}
  </div>
);
```

### 3.3 Create Parameter Controls Component (1h)
```typescript
// resources/js/islands/Settings/components/AIParameterControls.tsx
interface AIParameterControlsProps {
  config: AIConfig;
  model: Model | null;
  onChange: (updates: Partial<AIConfig>) => void;
  validationResult?: ValidationResult;
}

export const AIParameterControls = ({ config, model, onChange, validationResult }: AIParameterControlsProps) => {
  const maxContextLength = model?.context_length || 4096;
  const supportsStreaming = model?.capabilities?.includes('streaming') ?? true;
  
  return (
    <div className="space-y-4">
      <div>
        <label className="text-sm font-medium">Context Length</label>
        <div className="mt-2">
          <Slider
            value={[config.context_length]}
            onValueChange={([value]) => onChange({ context_length: value })}
            max={maxContextLength}
            min={1024}
            step={1024}
            className="w-full"
          />
          <div className="flex justify-between text-xs text-gray-600 mt-1">
            <span>1,024</span>
            <span className="font-medium">
              {config.context_length.toLocaleString()} / {maxContextLength.toLocaleString()}
            </span>
          </div>
        </div>
        
        {validationResult?.warnings.some(w => w.includes('context')) && (
          <Alert className="mt-2">
            <AlertTriangle className="h-4 w-4" />
            <AlertDescription>
              {validationResult.warnings.find(w => w.includes('context'))}
            </AlertDescription>
          </Alert>
        )}
      </div>
      
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-2">
          <Switch
            checked={config.streaming}
            onCheckedChange={(streaming) => onChange({ streaming })}
            disabled={!supportsStreaming}
          />
          <label className="text-sm font-medium">Enable Streaming</label>
        </div>
        {!supportsStreaming && (
          <Badge variant="outline" className="text-xs">
            Not supported
          </Badge>
        )}
      </div>
      
      <div className="flex items-center space-x-2">
        <Switch
          checked={config.auto_title}
          onCheckedChange={(auto_title) => onChange({ auto_title })}
        />
        <label className="text-sm font-medium">Auto-generate Titles</label>
      </div>
    </div>
  );
};
```

## Phase 4: Integration & Enhanced AIConfiguration (2-3 hours)

### 4.1 Update Main AIConfiguration Component (2h)
```typescript
// resources/js/islands/Settings/components/AIConfiguration.tsx
export const AIConfiguration = () => {
  const { providers, isLoading: providersLoading } = useAIProviders();
  const { data: providerStatus } = useProviderStatus();
  const { validateConfig, isValidating, validationResult } = useConfigValidation();
  
  const [config, setConfig] = useState<AIConfig>({
    provider: '',
    model: '',
    context_length: 4096,
    streaming: true,
    auto_title: false,
  });
  
  const { data: selectedProviderModels } = useProviderModels(config.provider);
  const selectedModel = selectedProviderModels?.text.find(m => m.id === config.model);
  
  // Load current settings
  const { data: currentSettings } = useQuery({
    queryKey: ['user-settings'],
    queryFn: () => api.get('/settings').then(res => res.data),
  });
  
  useEffect(() => {
    if (currentSettings?.ai) {
      setConfig(currentSettings.ai);
    }
  }, [currentSettings]);
  
  // Validate configuration when it changes
  useEffect(() => {
    if (config.provider && config.model) {
      validateConfig(config);
    }
  }, [config, validateConfig]);
  
  const handleConfigChange = useCallback((updates: Partial<AIConfig>) => {
    setConfig(prev => ({ ...prev, ...updates }));
  }, []);
  
  const handleProviderChange = useCallback((provider: string) => {
    setConfig(prev => ({
      ...prev,
      provider,
      model: '', // Reset model when provider changes
    }));
  }, []);
  
  if (providersLoading) {
    return <div>Loading AI providers...</div>;
  }
  
  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium">AI Configuration</h3>
        <p className="text-sm text-gray-600 mt-1">
          Configure your preferred AI provider and model settings.
        </p>
      </div>
      
      <ProviderSelector
        providers={providers || []}
        selected={config.provider}
        onSelect={handleProviderChange}
        providerStatus={providerStatus || {}}
      />
      
      {config.provider && selectedProviderModels && (
        <ModelSelector
          models={selectedProviderModels.text}
          selected={config.model}
          onSelect={(model) => handleConfigChange({ model })}
        />
      )}
      
      {config.model && selectedModel && (
        <AIParameterControls
          config={config}
          model={selectedModel}
          onChange={handleConfigChange}
          validationResult={validationResult}
        />
      )}
      
      {validationResult && !validationResult.valid && (
        <Alert variant="destructive">
          <AlertTriangle className="h-4 w-4" />
          <AlertTitle>Configuration Issues</AlertTitle>
          <AlertDescription>
            <ul className="list-disc list-inside space-y-1">
              {validationResult.errors.map((error, index) => (
                <li key={index}>{error}</li>
              ))}
            </ul>
          </AlertDescription>
        </Alert>
      )}
      
      <div className="flex justify-end">
        <Button
          onClick={() => {/* Save configuration */}}
          disabled={isValidating || !validationResult?.valid}
        >
          {isValidating ? 'Validating...' : 'Save Configuration'}
        </Button>
      </div>
    </div>
  );
};
```

### 4.2 Add Configuration Health Indicators (1h)
```typescript
// Add health scoring and recommendations
const useConfigurationHealth = (config: AIConfig, validationResult?: ValidationResult) => {
  return useMemo(() => {
    let score = 0;
    const recommendations = [];
    
    if (config.provider && config.model) score += 40;
    if (validationResult?.valid) score += 30;
    if (config.streaming) score += 15; // Streaming generally better UX
    if (config.context_length > 8192) score += 15; // Reasonable context size
    
    if (!config.provider) recommendations.push('Select an AI provider');
    if (!config.model) recommendations.push('Choose a model');
    if (validationResult?.suggestions) recommendations.push(...validationResult.suggestions);
    
    return {
      score: Math.min(score, 100),
      level: score >= 80 ? 'excellent' : score >= 60 ? 'good' : score >= 40 ? 'fair' : 'poor',
      recommendations,
    };
  }, [config, validationResult]);
};
```

## Phase 5: Testing & Polish (2-3 hours)

### 5.1 Backend Tests (1.5h)
```php
// tests/Feature/AI/ProviderServiceTest.php
class ProviderServiceTest extends TestCase
{
    public function test_gets_available_providers()
    public function test_gets_provider_models()
    public function test_validates_configuration()
    public function test_checks_provider_status()
    public function test_handles_missing_providers()
}

// tests/Feature/AI/ProvidersControllerTest.php
class ProvidersControllerTest extends TestCase
{
    public function test_lists_providers()
    public function test_shows_provider_details()
    public function test_validates_configuration()
    public function test_returns_provider_status()
}
```

### 5.2 Frontend Tests (1h)
```typescript
// Test provider selection and configuration flow
// Test validation and error handling
// Test API integration and state management
```

### 5.3 Error Handling & Polish (30min)
```typescript
// Enhanced error boundaries
// Loading states
// Accessibility improvements
// Mobile responsiveness
```

## Success Metrics
- [ ] Provider selection loads from config/fragments.php
- [ ] Model selection updates based on provider choice
- [ ] API key status visible for each provider  
- [ ] Parameter limits enforced based on model capabilities
- [ ] Configuration validation prevents invalid states
- [ ] Status indicators guide users to working setup
- [ ] Settings persist and load correctly
- [ ] Integration tests cover all scenarios

## Dependencies
- config/fragments.php provider catalog (already exists)
- Existing settings API endpoints for persistence
- React Query for API state management
- UI components (Select, Switch, Slider, Alert, Badge)
- Current AIConfiguration component location in PreferencesTab