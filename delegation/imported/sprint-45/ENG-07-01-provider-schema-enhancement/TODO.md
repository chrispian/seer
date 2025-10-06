# ENG-07-01: Provider Schema Enhancement - Task Checklist

## ✅ Phase 1: Database Schema Design

### Provider Configuration Model
- [ ] Create `app/Models/ProviderConfig.php` with proper structure
- [ ] Define fillable fields: provider, enabled, ui_preferences, capabilities
- [ ] Add usage tracking fields: usage_count, last_health_check
- [ ] Implement model validation and casting
- [ ] Add provider availability methods

### AICredential Model Enhancement
- [ ] Review existing `app/Models/AICredential.php` structure
- [ ] Plan ui_metadata field addition
- [ ] Design relationship with ProviderConfig
- [ ] Plan indexing strategy for performance

### Migration Strategy
- [ ] Design provider_configs table schema
- [ ] Plan AICredential table modifications
- [ ] Create data migration strategy for existing records
- [ ] Plan rollback procedures

## ✅ Phase 2: Migration Implementation

### Create Provider Config Migration
- [ ] Generate migration: `php artisan make:migration create_provider_configs_table`
- [ ] Implement up() method with proper schema
- [ ] Add indexes for provider, enabled status
- [ ] Implement down() method for rollback
- [ ] Add foreign key constraints where needed

### Enhance AICredential Migration
- [ ] Generate migration: `php artisan make:migration enhance_ai_credentials_table`
- [ ] Add ui_metadata json field
- [ ] Add compound indexes for performance
- [ ] Ensure backward compatibility
- [ ] Test migration rollback

### Data Migration Script
- [ ] Create command: `php artisan make:command AI/SyncProviderConfigs`
- [ ] Implement provider config population from fragments.php
- [ ] Handle existing credential provider mapping
- [ ] Add data validation and error handling
- [ ] Test with production-like data

## ✅ Phase 3: Model Implementation

### ProviderConfig Model Development
- [ ] Implement basic CRUD operations
- [ ] Add `isEnabled()` method for status checking
- [ ] Create `updateCapabilities()` from config
- [ ] Add `recordUsage()` for analytics
- [ ] Implement `checkHealth()` status tracking
- [ ] Add `getUIPreferences()` for frontend

### AICredential Model Enhancement
- [ ] Add `providerConfig()` relationship method
- [ ] Implement `isProviderEnabled()` convenience method
- [ ] Create `getUIMetadata()` for frontend needs
- [ ] Add `updateUIMetadata()` for preferences
- [ ] Enhance `getActiveCredential()` with provider status
- [ ] Add scopes for enabled providers only

### Model Relationships
- [ ] Define ProviderConfig hasMany AICredentials
- [ ] Add AICredential belongsTo ProviderConfig  
- [ ] Test eager loading performance
- [ ] Add relationship constraints and cascading

## ✅ Phase 4: Integration & Testing

### ModelSelectionService Integration
- [ ] Update provider availability checking in `getAvailableProviders()`
- [ ] Modify `isProviderAvailable()` to include enabled status
- [ ] Optimize provider lookup queries
- [ ] Test fallback provider logic with disabled providers

### Console Command Updates
- [ ] Update `ai:credentials:set` to create/update provider config
- [ ] Enhance `ai:health` to update provider health status
- [ ] Modify `ai:credentials:list` to show provider enabled status
- [ ] Test all existing command functionality

### Configuration Sync Implementation
- [ ] Create provider config sync from fragments.php
- [ ] Implement capability auto-population
- [ ] Add validation for config consistency
- [ ] Create sync command for deployment

## ✅ Phase 5: Validation & Documentation

### Testing & Validation
- [ ] Run existing test suite to ensure no regressions
- [ ] Test migration on development database
- [ ] Validate CLI command functionality
- [ ] Test provider enable/disable functionality
- [ ] Verify performance with multiple providers

### Documentation Updates
- [ ] Update model documentation
- [ ] Document new migration procedures
- [ ] Update CLI command help text
- [ ] Create provider config management docs

### Performance Validation
- [ ] Test provider lookup performance
- [ ] Validate index effectiveness
- [ ] Check query optimization
- [ ] Measure UI response times

## 🔧 Implementation Notes

### Security Considerations
- Ensure ui_metadata never contains sensitive credentials
- Validate all JSON field inputs
- Maintain encryption standards for credential data
- Add proper access controls for provider configs

### Performance Optimizations
- Index provider + enabled status combinations
- Use eager loading for provider config relationships
- Cache frequently accessed provider configurations
- Optimize health check query patterns

### Backward Compatibility
- Ensure existing AICredential methods still work
- Maintain CLI command interfaces
- Preserve ModelSelectionService behavior
- Keep migration rollback capability

## 📋 Completion Criteria
- [ ] All migrations run successfully and are reversible
- [ ] ProviderConfig and enhanced AICredential models work correctly
- [ ] CLI commands maintain full functionality
- [ ] ModelSelectionService integrates seamlessly
- [ ] Performance meets or exceeds current benchmarks
- [ ] Ready for API service layer development (ENG-07-02)