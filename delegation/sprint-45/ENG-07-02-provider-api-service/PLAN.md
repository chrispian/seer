# ENG-07-02: Provider API Integration Service - Implementation Plan

## Phase 1: Service Layer Architecture (2-3 hours)

### 1.1 Core Service Classes
**Create**: `app/Services/ProviderManagementService.php`
- Provider CRUD operations
- Health status management
- Credential lifecycle management
- Configuration synchronization

**Create**: `app/Services/ProviderTestingService.php`  
- Credential validation and testing
- Provider connectivity checks
- Health monitoring integration
- Timeout and error handling

### 1.2 API Resource Classes
**Create**: `app/Http/Resources/ProviderResource.php`
- Provider data transformation
- Secure credential masking
- Status and capability formatting

**Create**: `app/Http/Resources/CredentialResource.php`
- Credential metadata (no raw credentials)
- Status and expiration handling
- UI-specific data formatting

## Phase 2: API Controllers (2-3 hours)

### 2.1 Provider Controller
**Create**: `app/Http/Controllers/Api/ProviderController.php`
```php
- index() - GET /api/providers
- show($provider) - GET /api/providers/{provider}  
- update($provider) - PUT /api/providers/{provider}
- toggle($provider) - POST /api/providers/{provider}/toggle
- test($provider) - POST /api/providers/{provider}/test
- health($provider) - GET /api/providers/{provider}/health
```

### 2.2 Credential Controller  
**Create**: `app/Http/Controllers/Api/CredentialController.php`
```php
- index($provider) - GET /api/providers/{provider}/credentials
- store($provider) - POST /api/providers/{provider}/credentials
- update($provider, $credential) - PUT /api/providers/{provider}/credentials/{id}
- destroy($provider, $credential) - DELETE /api/providers/{provider}/credentials/{id}
```

### 2.3 Model Controller
**Create**: `app/Http/Controllers/Api/ModelController.php`
```php  
- index() - GET /api/models (all available)
- providerModels($provider) - GET /api/providers/{provider}/models
```

## Phase 3: Request Validation (1-2 hours)

### 3.1 Form Request Classes
**Create**: `app/Http/Requests/StoreCredentialRequest.php`
- Provider-specific validation rules
- Credential format validation
- Security input sanitization

**Create**: `app/Http/Requests/UpdateProviderRequest.php`
- Provider configuration validation
- UI preference validation
- Capability update rules

### 3.2 Custom Validation Rules
**Create**: `app/Rules/ValidCredentialFormat.php`
- Provider-specific credential validation
- API key format checking
- URL validation for base URLs

## Phase 4: API Routes & Middleware (1 hour)

### 4.1 Route Definition
**Update**: `routes/api.php`
```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('providers', ProviderController::class);
    Route::post('providers/{provider}/toggle', [ProviderController::class, 'toggle']);
    Route::post('providers/{provider}/test', [ProviderController::class, 'test']);
    Route::get('providers/{provider}/health', [ProviderController::class, 'health']);
    
    Route::apiResource('providers.credentials', CredentialController::class);
    Route::get('models', [ModelController::class, 'index']);
    Route::get('providers/{provider}/models', [ModelController::class, 'providerModels']);
});
```

### 4.2 Rate Limiting & Security
**Add**: Rate limiting for test endpoints
**Add**: Input sanitization middleware
**Add**: CORS configuration for frontend

## Phase 5: Integration & Testing (1-2 hours)

### 5.1 Service Integration
**Integrate**: ModelSelectionService for model data
**Integrate**: AIProviderManager for health checks
**Integrate**: Enhanced models from ENG-07-01
**Test**: All service integrations

### 5.2 API Testing
**Create**: Feature tests for all endpoints
**Test**: Authentication and authorization
**Test**: Error handling and edge cases
**Validate**: Response formats and status codes

## Success Criteria

### Functional Requirements
- ✅ Complete CRUD API for providers and credentials
- ✅ Secure credential handling with no exposure of raw data
- ✅ Health check and testing integration
- ✅ Model information and availability endpoints

### Technical Requirements
- ✅ Proper validation and error handling
- ✅ Optimized performance for UI operations
- ✅ Security best practices implemented
- ✅ Clean API design following Laravel conventions

### Integration Requirements
- ✅ Works with enhanced database schema
- ✅ Integrates with existing AI services
- ✅ Maintains CLI command functionality
- ✅ Ready for React frontend consumption

## Dependencies
- **Prerequisite**: ENG-07-01 (Enhanced database schema)
- **Parallel**: None (API foundation)
- **Enables**: UX-06-01, UX-06-02 (React components)

## Security Considerations
- Never return raw credentials in API responses
- Validate all inputs thoroughly
- Use proper HTTP status codes
- Log security events appropriately
- Implement proper rate limiting

## Performance Optimization
- Cache provider configurations
- Optimize database queries with proper eager loading
- Use async processing for slow operations (health checks)
- Implement pagination for large datasets