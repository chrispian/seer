# SETTINGS-002 Context: Dynamic AI Provider Configuration

## Current AI Settings Architecture

### Provider Catalog in config/fragments.php
The configuration already includes a comprehensive provider catalog at `config/fragments.php:76-131`:

```php
'providers' => [
    'openai' => [
        'name' => 'OpenAI',
        'text_models' => [
            'gpt-4o' => ['name' => 'GPT-4o', 'context_length' => 128000],
            'gpt-4o-mini' => ['name' => 'GPT-4o Mini', 'context_length' => 128000],
            // ... more models
        ],
        'embedding_models' => [ /* ... */ ],
        'config_keys' => ['OPENAI_API_KEY'],
    ],
    'anthropic' => [ /* ... */ ],
    'ollama' => [ /* ... */ ],
    'openrouter' => [ /* ... */ ],
]
```

### Current Storage Format
AI settings are stored in `profile_settings.ai` JSON column with structure:
```json
{
  "provider": "openai",
  "model": "gpt-4o-mini", 
  "context_length": 128000,
  "streaming": true,
  "auto_title": false
}
```

### Current Frontend Components
```typescript
// Settings components structure
/resources/js/islands/Settings/
├── SettingsLayout.tsx
├── PreferencesTab.tsx          // Contains AI settings section
└── components/
    ├── AIConfiguration.tsx     // Current static AI form
    └── ...
```

## Implementation Requirements

### Backend API Enhancement

#### Provider Catalog Service
```php
// app/Services/AIProviderService.php
class AIProviderService
{
    public function getAvailableProviders(): array
    public function getProviderModels(string $provider): array
    public function validateConfiguration(array $config): array
    public function checkProviderStatus(string $provider): array
    public function getProviderCapabilities(string $provider): array
}
```

#### API Endpoints
```php
// New endpoints needed
GET  /api/ai/providers          // List available providers
GET  /api/ai/providers/{id}     // Provider details and models  
POST /api/ai/providers/validate // Validate configuration
GET  /api/ai/providers/status   // Check provider API key status
```

### Frontend Component Enhancement

#### Dynamic AI Configuration
```typescript
// Enhanced AIConfiguration component
interface AIConfiguration {
  // Provider selection with metadata
  providers: Provider[];
  selectedProvider: string;
  
  // Dynamic model selection
  availableModels: Model[];
  selectedModel: string;
  
  // Dynamic parameter limits
  contextLengthLimits: { min: number; max: number };
  
  // Status indicators
  providerStatus: ProviderStatus;
  configurationHealth: ConfigHealth;
}

interface Provider {
  id: string;
  name: string;
  description?: string;
  capabilities: string[];
  requirements: string[];
  status: 'available' | 'requires_key' | 'unavailable';
  models: {
    text: Model[];
    embedding: Model[];
  };
}

interface Model {
  id: string;
  name: string;
  contextLength: number;
  capabilities: string[];
  cost?: 'low' | 'medium' | 'high';
  performance?: 'fast' | 'balanced' | 'quality';
}
```

### Configuration Validation

#### API Key Detection
```php
// Check for required environment variables
private function checkApiKeyStatus(string $provider): array
{
    $configKeys = $this->getProviderConfigKeys($provider);
    $status = [];
    
    foreach ($configKeys as $key) {
        $status[$key] = [
            'present' => !empty(env($key)),
            'valid' => $this->validateApiKey($provider, $key),
        ];
    }
    
    return $status;
}
```

#### Model Compatibility
```php
// Validate model selection against provider
private function validateModelSelection(string $provider, string $model): bool
{
    $providerConfig = config("fragments.models.providers.{$provider}");
    
    return isset($providerConfig['text_models'][$model]) ||
           isset($providerConfig['embedding_models'][$model]);
}
```

### UI Components Structure

#### Provider Selector
```typescript
// Provider selection with status indicators
const ProviderSelector = ({ providers, selected, onChange }) => {
  return (
    <div className="space-y-3">
      {providers.map(provider => (
        <ProviderCard
          key={provider.id}
          provider={provider}
          selected={selected === provider.id}
          onSelect={() => onChange(provider.id)}
        />
      ))}
    </div>
  );
};

const ProviderCard = ({ provider, selected, onSelect }) => {
  return (
    <div className={`border rounded-lg p-4 ${selected ? 'border-blue-500' : 'border-gray-200'}`}>
      <div className="flex items-center justify-between">
        <div>
          <h3 className="font-medium">{provider.name}</h3>
          <p className="text-sm text-gray-600">{provider.description}</p>
        </div>
        <ProviderStatusBadge status={provider.status} />
      </div>
      
      {provider.status === 'requires_key' && (
        <div className="mt-2 p-2 bg-yellow-50 rounded text-sm">
          Missing API key: {provider.requirements.join(', ')}
        </div>
      )}
    </div>
  );
};
```

#### Model Selector
```typescript
// Model selection with metadata
const ModelSelector = ({ models, selected, onChange }) => {
  return (
    <Select value={selected} onValueChange={onChange}>
      {models.map(model => (
        <SelectItem key={model.id} value={model.id}>
          <div className="flex items-center justify-between w-full">
            <span>{model.name}</span>
            <div className="flex items-center space-x-2">
              <Badge variant="outline">
                {(model.contextLength / 1000).toFixed(0)}k context
              </Badge>
              {model.cost && (
                <Badge variant={model.cost === 'low' ? 'success' : 'warning'}>
                  {model.cost} cost
                </Badge>
              )}
            </div>
          </div>
        </SelectItem>
      ))}
    </Select>
  );
};
```

#### Parameter Controls
```typescript
// Dynamic parameter controls based on model capabilities
const ParameterControls = ({ model, values, onChange }) => {
  const contextLimit = model?.contextLength || 4096;
  
  return (
    <div className="space-y-4">
      <div>
        <label>Context Length</label>
        <Slider
          value={[values.contextLength]}
          onValueChange={([value]) => onChange({ contextLength: value })}
          max={contextLimit}
          min={1024}
          step={1024}
        />
        <div className="text-sm text-gray-600">
          Max: {contextLimit.toLocaleString()} tokens
        </div>
      </div>
      
      <div className="flex items-center space-x-2">
        <Switch
          checked={values.streaming}
          onCheckedChange={(streaming) => onChange({ streaming })}
        />
        <label>Enable Streaming</label>
        {!model?.capabilities?.includes('streaming') && (
          <Badge variant="outline">Not supported</Badge>
        )}
      </div>
    </div>
  );
};
```

## Integration Points

### Existing Settings Flow
- Integrate with current `PreferencesController` for persistence
- Maintain compatibility with existing `profile_settings.ai` format
- Preserve user configurations during provider catalog updates

### Configuration Management
- Use existing Laravel configuration patterns
- Cache provider metadata for performance
- Invalidate cache when config files change

### Error Handling
- Graceful degradation when providers unavailable
- Clear messaging for configuration issues
- Fallback to working configurations when possible

## Security Considerations

### API Key Validation
- Never expose actual API keys in responses
- Only return boolean status of key presence/validity
- Rate limit validation requests to prevent abuse

### Provider Validation
- Whitelist allowed providers from configuration
- Validate all provider/model combinations
- Sanitize user inputs for security

### Configuration Persistence
- Validate all settings before storage
- Maintain audit trail of configuration changes
- Prevent privilege escalation through settings manipulation