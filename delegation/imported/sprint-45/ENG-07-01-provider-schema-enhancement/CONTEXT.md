# ENG-07-01: Provider Schema Enhancement - Context

## Current System Architecture

### Database Structure
**AICredential Model** (`app/Models/AICredential.php`):
```php
- id (primary key)
- provider (string, indexed)
- credential_type (string, default: 'api_key') 
- encrypted_credentials (text) - JSON encrypted
- metadata (json, nullable)
- expires_at (timestamp, nullable)
- is_active (boolean, default: true)
- timestamps
- unique(['provider', 'credential_type'])
```

**Migration**: `2025_09_20_233602_create_a_i_credentials_table.php`

### Configuration System
**Provider Catalog** (`config/fragments.php`):
- Static provider definitions with capabilities
- Text/embedding model listings
- Configuration key requirements
- No enable/disable controls at provider level

**Runtime Config** (`config/prism.php`):
- Environment-based API keys and URLs
- Provider-specific settings

### Integration Points
**ModelSelectionService** (`app/Services/AI/ModelSelectionService.php`):
- Line 254: `if (! isset($this->providers[$provider]))`
- Line 291: `return ! empty($this->providers[$provider][$modelType] ?? [])`
- Relies on provider availability checking

**Console Commands**:
- `ai:credentials:set` - stores credentials with is_active flag
- `ai:credentials:list` - shows active/inactive status  
- `ai:health` - tests provider connectivity

## Requirements

### Schema Enhancements Needed
1. **Provider-level Enable/Disable**
   - Add `enabled` field to AICredential or separate provider config table
   - Maintain granular control: credential active + provider enabled
   
2. **Enhanced Metadata**
   - Track UI configuration preferences
   - Store provider capabilities and limits
   - Add usage statistics tracking fields

3. **Performance Optimization**
   - Proper indexing for UI queries
   - Efficient provider status checking
   - Optimized credential lookup

### Backward Compatibility
- CLI commands must continue working
- Existing encrypted credentials preserved
- ModelSelectionService integration maintained
- Config structure remains functional

## Technical Considerations

### Security Requirements
- Maintain encryption of sensitive credentials
- Ensure proper field validation
- Prevent credential leakage in metadata
- Secure provider status API endpoints

### Performance Implications
- Provider availability checks in UI
- Bulk operations on multiple providers
- Real-time status monitoring
- Efficient credential validation

### Migration Strategy
- Zero-downtime deployment
- Rollback capabilities
- Data preservation
- Index optimization