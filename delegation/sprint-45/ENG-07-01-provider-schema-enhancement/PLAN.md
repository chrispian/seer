# ENG-07-01: Provider Schema Enhancement - Implementation Plan

## Phase 1: Database Schema Design (1-2 hours)

### 1.1 Provider Configuration Model
**Create**: `app/Models/ProviderConfig.php`
- Separate table for provider-level settings
- Relationships with AICredential
- Enable/disable controls per provider

### 1.2 AICredential Model Enhancement  
**Enhance**: `app/Models/AICredential.php`
- Add provider_config relationship
- Enhanced metadata structure
- UI-specific methods

### 1.3 Migration Planning
**Create**: New migration for provider configs
**Plan**: AICredential table modifications
**Strategy**: Backward compatible changes

## Phase 2: Migration Implementation (1-2 hours)

### 2.1 Provider Config Migration
```php
Schema::create('provider_configs', function (Blueprint $table) {
    $table->id();
    $table->string('provider')->unique();
    $table->boolean('enabled')->default(true);
    $table->json('ui_preferences')->nullable();
    $table->json('capabilities')->nullable();
    $table->integer('usage_count')->default(0);
    $table->timestamp('last_health_check')->nullable();
    $table->timestamps();
});
```

### 2.2 AICredential Enhancement Migration
```php
Schema::table('a_i_credentials', function (Blueprint $table) {
    $table->json('ui_metadata')->nullable()->after('metadata');
    $table->index(['provider', 'is_active']);
    $table->index('created_at');
});
```

### 2.3 Data Migration
- Populate provider_configs from existing credentials
- Set default enabled status
- Migrate metadata where applicable

## Phase 3: Model Updates (1-2 hours)

### 3.1 ProviderConfig Model
**Implement**:
- Provider availability checking
- UI preference management
- Health status tracking
- Usage analytics

### 3.2 AICredential Model Enhancement
**Add Methods**:
- `getProviderConfig()` relationship
- `isProviderEnabled()` status check
- `getUIMetadata()` for frontend
- `updateUsageStats()` tracking

### 3.3 Model Relationships
**Define**:
- ProviderConfig hasMany AICredentials
- AICredential belongsTo ProviderConfig
- Proper eager loading

## Phase 4: Integration Updates (1 hour)

### 4.1 ModelSelectionService Integration
**Update**: Provider availability logic
**Add**: Enable/disable status checking
**Optimize**: Provider lookup performance

### 4.2 Console Command Updates
**Enhance**: `ai:credentials:set` to handle provider config
**Update**: `ai:health` to use new schema
**Maintain**: Backward compatibility

### 4.3 Configuration Sync
**Create**: Provider config sync from fragments.php
**Implement**: Auto-population of capabilities
**Add**: Migration commands

## Success Criteria

### Functional Requirements
- ✅ Provider-level enable/disable functionality
- ✅ Enhanced metadata storage for UI preferences
- ✅ Backward compatibility with existing CLI tools
- ✅ Performance optimized for UI operations

### Technical Requirements  
- ✅ Zero-downtime migration
- ✅ Proper indexing for performance
- ✅ Secure credential handling maintained
- ✅ Rollback capabilities

### Integration Requirements
- ✅ ModelSelectionService works with new schema
- ✅ Console commands remain functional
- ✅ Health check system updated
- ✅ Ready for React UI integration

## Dependencies
- **Prerequisite**: Current AICredential system understanding
- **Parallel**: None (foundation task)
- **Enables**: ENG-07-02 (API Service), UX-06-01 (React Management)

## Risk Mitigation
- **Data Loss**: Comprehensive backup strategy
- **Performance**: Proper indexing and query optimization
- **Compatibility**: Extensive testing of existing features
- **Security**: Maintain encryption standards