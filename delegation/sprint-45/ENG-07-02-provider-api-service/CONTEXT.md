# ENG-07-02: Provider API Integration Service - Context

## System Architecture

### Enhanced Database Schema (from ENG-07-01)
**ProviderConfig Model**:
```php
- id, provider (unique), enabled
- ui_preferences, capabilities  
- usage_count, last_health_check
- timestamps
```

**Enhanced AICredential Model**:
```php
- id, provider, credential_type
- encrypted_credentials, metadata, ui_metadata
- expires_at, is_active, timestamps
- relationships: belongsTo ProviderConfig
```

### Existing Services Integration
**ModelSelectionService** (`app/Services/AI/ModelSelectionService.php`):
- Provider availability checking
- Model capability validation
- Fallback provider logic

**AIProviderManager** (`app/Services/AI/AIProviderManager.php`):
- Provider initialization
- Health check orchestration
- Provider operation support

**Console Commands**:
- `ai:health` - provider connectivity testing
- `ai:credentials:set/list/remove` - CLI management

## API Requirements

### Core Endpoints Needed
1. **Provider Management**
   - GET `/api/providers` - list all providers with status
   - GET `/api/providers/{provider}` - get specific provider details
   - PUT `/api/providers/{provider}` - update provider configuration
   - POST `/api/providers/{provider}/toggle` - enable/disable provider

2. **Credential Management**
   - GET `/api/providers/{provider}/credentials` - list provider credentials
   - POST `/api/providers/{provider}/credentials` - add new credentials
   - PUT `/api/providers/{provider}/credentials/{id}` - update credentials
   - DELETE `/api/providers/{provider}/credentials/{id}` - remove credentials

3. **Health & Testing**
   - POST `/api/providers/{provider}/test` - test provider connectivity
   - GET `/api/providers/{provider}/health` - get health status
   - POST `/api/providers/health-check` - bulk health check

4. **Model Information**
   - GET `/api/providers/{provider}/models` - available models
   - GET `/api/models` - all available models across providers

### Security Requirements
- **Authentication**: API must be authenticated (likely session-based)
- **Authorization**: Proper access controls for provider management
- **Validation**: Strict input validation for all endpoints
- **Credential Security**: Never expose raw credentials in responses
- **Rate Limiting**: Prevent abuse of testing endpoints

### Response Format Standards
```json
{
  "data": { /* response data */ },
  "meta": { /* pagination, counts */ },
  "status": "success|error",
  "message": "Human readable message"
}
```

## Integration Points

### Frontend Integration
**React Components Will Need**:
- Provider listing with real-time status
- Add/edit credential forms with validation
- Health status monitoring
- Model selection interfaces
- Bulk operations support

### Backend Services Integration
**Must Work With**:
- Enhanced ProviderConfig/AICredential models
- Existing ModelSelectionService for capabilities
- AIProviderManager for health checks
- Console command functionality (maintain CLI support)

### Configuration Integration
**Provider Catalog** (`config/fragments.php`):
- Provider capabilities and model listings
- Configuration requirements
- UI display information

## Technical Considerations

### Performance Requirements
- Provider list should load quickly (< 200ms)
- Health checks may be slow (5-10s) - need async handling
- Credential testing should timeout appropriately
- Bulk operations need progress tracking

### Caching Strategy
- Provider configurations (rarely change)
- Health check results (TTL: 5-10 minutes)
- Model availability (TTL: 1 hour)
- Avoid caching credentials (security)

### Error Handling
- Network timeouts for provider testing
- Invalid credential formats
- Provider service unavailability  
- Database constraint violations
- Validation failures

### Security Patterns
- Mask credentials in all responses
- Validate credential formats before storage
- Sanitize all user inputs
- Use proper HTTP status codes
- Log security-relevant events