# SETTINGS-002 TODO: Dynamic AI Provider Configuration

## Backend Implementation

### AI Provider Service
- [ ] Create `app/Services/AIProviderService.php`
  - [ ] `getAvailableProviders()` - Load from config/fragments.php
  - [ ] `getProviderModels(string $provider)` - Get text/embedding models
  - [ ] `validateConfiguration(array $config)` - Validate provider/model/params
  - [ ] `checkProviderStatus(string $provider)` - Check API key status
  - [ ] `getProviderCapabilities(string $provider)` - Return capabilities metadata
  - [ ] Private helper methods:
    - [ ] `loadProviderConfig(string $provider)`
    - [ ] `checkApiKeyPresence(array $configKeys)`
    - [ ] `validateModelForProvider(string $provider, string $model)`
    - [ ] `getContextLengthLimits(string $provider, string $model)`

### API Controller
- [ ] Create `app/Http/Controllers/Api/AIProvidersController.php`
  - [ ] `index()` - GET /api/ai/providers (list all with status)
  - [ ] `show(string $provider)` - GET /api/ai/providers/{provider} (details + models)
  - [ ] `validate(ValidateConfigRequest $request)` - POST /api/ai/providers/validate
  - [ ] `status()` - GET /api/ai/providers/status (API key status for all)

### Request Validation
- [ ] Create `app/Http/Requests/ValidateConfigRequest.php`
  - [ ] Validate provider exists in config
  - [ ] Validate model exists for provider
  - [ ] Validate context_length within model limits
  - [ ] Validate boolean fields (streaming, auto_title)
  - [ ] Custom validation rules for provider-model compatibility

### API Routes
- [ ] Add routes to `routes/api.php`:
  - [ ] `GET /api/ai/providers` 
  - [ ] `GET /api/ai/providers/{provider}`
  - [ ] `POST /api/ai/providers/validate`
  - [ ] `GET /api/ai/providers/status`
- [ ] Add route middleware for authentication
- [ ] Add rate limiting for validation endpoint

## Frontend API Integration

### API Hooks
- [ ] Create `resources/js/hooks/useAIProviders.ts`
  - [ ] Fetch available providers with status
  - [ ] Cache with React Query
  - [ ] Handle loading and error states
  - [ ] Auto-refresh on focus/interval

- [ ] Create `resources/js/hooks/useProviderModels.ts`
  - [ ] Fetch models for specific provider
  - [ ] Enable/disable based on provider selection
  - [ ] Cache per provider
  - [ ] Handle provider changes

- [ ] Create `resources/js/hooks/useProviderStatus.ts`
  - [ ] Fetch API key status for all providers
  - [ ] Periodic refresh (30 seconds)
  - [ ] Real-time status updates
  - [ ] Handle authentication errors

- [ ] Create `resources/js/hooks/useConfigValidation.ts`
  - [ ] Validate configuration against API
  - [ ] Debounce validation requests
  - [ ] Return validation results with errors/warnings
  - [ ] Handle validation in progress state

### Type Definitions
- [ ] Create `resources/js/types/ai.ts`
  - [ ] `Provider` interface with status and capabilities
  - [ ] `Model` interface with metadata and limits
  - [ ] `AIConfig` interface matching backend format
  - [ ] `ValidationResult` interface for validation responses
  - [ ] `ProviderStatus` interface for API key status
  - [ ] Utility types for provider capabilities and model types

## Enhanced UI Components

### Provider Selector
- [ ] Create `resources/js/islands/Settings/components/ProviderSelector.tsx`
  - [ ] Grid layout of provider cards
  - [ ] Status badges (available, requires_key, unavailable)
  - [ ] Capability indicators (streaming, embeddings, etc.)
  - [ ] API key requirement warnings
  - [ ] Selection state management
  - [ ] Hover and focus states

- [ ] Create `ProviderCard` sub-component
  - [ ] Provider name and description
  - [ ] Status indicator with color coding
  - [ ] Capability badges
  - [ ] Setup requirements when not available
  - [ ] Click to select functionality

- [ ] Create `StatusBadge` component
  - [ ] Available (green)
  - [ ] Requires Setup (yellow)
  - [ ] Unavailable (red)
  - [ ] Tooltip with details

### Model Selector
- [ ] Create `resources/js/islands/Settings/components/ModelSelector.tsx`
  - [ ] Dropdown/Select component for models
  - [ ] Model metadata display (context length, cost, performance)
  - [ ] Badge system for model characteristics
  - [ ] Empty state when no models available
  - [ ] Loading state during model fetch
  - [ ] Model details expansion

- [ ] Create `ModelDetails` sub-component
  - [ ] Context length display
  - [ ] Capability list
  - [ ] Cost and performance indicators
  - [ ] Recommendations based on use case

### Parameter Controls
- [ ] Create `resources/js/islands/Settings/components/AIParameterControls.tsx`
  - [ ] Context length slider with dynamic max
  - [ ] Streaming toggle with capability check
  - [ ] Auto-title toggle
  - [ ] Real-time validation feedback
  - [ ] Parameter limit warnings
  - [ ] Capability-based disabling

- [ ] Create parameter validation sub-components:
  - [ ] Context length with model limits
  - [ ] Feature toggles with support indicators
  - [ ] Warning alerts for unsupported combinations

### Configuration Health
- [ ] Create `ConfigurationHealth` component
  - [ ] Health score calculation (0-100)
  - [ ] Visual health indicator (color-coded)
  - [ ] Recommendation list
  - [ ] Quick fix suggestions
  - [ ] Configuration completeness check

## Main AIConfiguration Component

### Enhanced AIConfiguration
- [ ] Update `resources/js/islands/Settings/components/AIConfiguration.tsx`
  - [ ] Integrate all new sub-components
  - [ ] State management for configuration
  - [ ] Real-time validation integration
  - [ ] Loading states for all async operations
  - [ ] Error handling and user feedback
  - [ ] Save configuration functionality

### State Management
- [ ] Configuration state with proper typing
- [ ] Provider change handling (reset model selection)
- [ ] Model change handling (update parameter limits)
- [ ] Parameter validation on change
- [ ] Debounced auto-save or explicit save

### User Experience Enhancements
- [ ] Progressive disclosure (show relevant options)
- [ ] Guided setup flow for new users
- [ ] Contextual help and tooltips
- [ ] Error recovery suggestions
- [ ] Success feedback on save

## Validation & Error Handling

### Backend Validation
- [ ] Provider existence validation
- [ ] Model-provider compatibility checks
- [ ] Parameter range validation
- [ ] API key requirement validation
- [ ] Configuration coherence validation

### Frontend Validation
- [ ] Real-time field validation
- [ ] Cross-field dependency validation
- [ ] Visual validation indicators
- [ ] Clear error messaging
- [ ] Validation summary display

### Error States
- [ ] API communication errors
- [ ] Configuration validation errors
- [ ] Provider unavailability errors
- [ ] Model loading failures
- [ ] Save operation failures

## Testing

### Backend Tests
- [ ] Create `tests/Feature/AI/ProviderServiceTest.php`
  - [ ] Test provider loading from config
  - [ ] Test model retrieval for each provider
  - [ ] Test configuration validation logic
  - [ ] Test API key status checking
  - [ ] Test error handling for invalid providers

- [ ] Create `tests/Feature/AI/ProvidersControllerTest.php`
  - [ ] Test all API endpoints
  - [ ] Test authentication requirements
  - [ ] Test validation request handling
  - [ ] Test error responses
  - [ ] Test rate limiting

### Frontend Tests
- [ ] Component interaction tests
  - [ ] Provider selection behavior
  - [ ] Model selection updates
  - [ ] Parameter control functionality
  - [ ] Validation feedback display

- [ ] Hook tests
  - [ ] API data fetching
  - [ ] State management
  - [ ] Error handling
  - [ ] Cache behavior

- [ ] Integration tests
  - [ ] End-to-end configuration flow
  - [ ] Validation and save process
  - [ ] Error recovery flows

## Integration & Polish

### Settings Integration
- [ ] Integrate with existing settings persistence
- [ ] Maintain backward compatibility
- [ ] Handle migration of existing configurations
- [ ] Coordinate with other settings sections

### Performance Optimization
- [ ] Implement proper caching strategies
- [ ] Optimize re-renders with React.memo
- [ ] Debounce expensive operations
- [ ] Lazy load model details

### Accessibility
- [ ] Keyboard navigation support
- [ ] Screen reader compatibility
- [ ] ARIA labels and descriptions
- [ ] Focus management
- [ ] Color contrast compliance

### Mobile Responsiveness
- [ ] Touch-friendly interface elements
- [ ] Responsive layout for small screens
- [ ] Appropriate text sizes
- [ ] Mobile-optimized interactions

## Documentation & Polish

### API Documentation
- [ ] Document new API endpoints
- [ ] Add request/response examples
- [ ] Document validation rules
- [ ] Add error code reference

### User Documentation
- [ ] Provider setup guides
- [ ] Configuration best practices
- [ ] Troubleshooting common issues
- [ ] Feature capability matrix

### Developer Documentation
- [ ] Component API documentation
- [ ] Hook usage examples
- [ ] Extension points for new providers
- [ ] Configuration schema documentation

## Success Criteria Checklist
- [ ] Providers load dynamically from config/fragments.php
- [ ] Model selection reflects actual provider capabilities
- [ ] API key status clearly visible and actionable
- [ ] Parameter limits enforced based on model specifications
- [ ] Configuration validation prevents invalid combinations
- [ ] User receives clear guidance for setup issues
- [ ] Settings persist correctly with existing infrastructure
- [ ] Provider status updates in real-time
- [ ] Mobile experience is smooth and usable
- [ ] All interactions are accessible to screen readers
- [ ] Performance is smooth with proper caching
- [ ] Error states provide clear recovery paths