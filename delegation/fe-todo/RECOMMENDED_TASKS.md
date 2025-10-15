# Recommended Tasks for v2 UI System Enhancement

Based on the review of the fe-todo delegation files, here are recommended tasks to incorporate into the v2 UI system:

## Priority 1: Type System Integration

### Task: Implement Types Codegen Command
**From:** code-gen-and-adapters.md  
**Description:** Create artisan commands for automatic code generation from Type schemas
```bash
php artisan types:codegen {TypeName} --force
php artisan types:migrate {TypeName} --dry-run
```

**Benefits:**
- Automatic Model, Migration, FormRequest generation
- Policy and Resource scaffolding
- Consistent code structure
- Reduced manual coding

**Implementation Steps:**
1. Create `App\Console\Commands\TypesCodegen` command
2. Add pluggable stubs in `resources/stubs/types/*.stub`
3. Implement schema diff with safe migration support
4. Add --dry-run support for safety

## Priority 2: Generic CRUD with Sushi Adapters

### Task: Implement TypesCrudController with Adapter System
**From:** crud-and-sushi.md  
**Description:** Generic CRUD controller that works with any generated type

**Key Components:**
- `App\Http\Controllers\TypesCrudController.php` - Generic CRUD
- `App\Services\Types\GeneratedTypeLocator.php` - Resolves Model/Resource/Request
- `App\Services\Types\Adapters\SushiAdapter.php` - For static/API datasets
- `App\Services\Types\AdapterManager.php` - Manages adapters

**REST Endpoints:**
```
GET    /api/v2/types/{alias}        # index with q/sort/per_page
POST   /api/v2/types/{alias}        # create
GET    /api/v2/types/{alias}/{id}   # show
PUT    /api/v2/types/{alias}/{id}   # update
DELETE /api/v2/types/{alias}/{id}   # destroy
```

**Benefits:**
- Unified CRUD interface for all types
- Support for Sushi models (no database tables)
- Automatic pagination and filtering
- Consistent API structure

## Priority 3: Feature Flag Authorization with ADR Audit

### Task: Implement Flag-Based Type Authorization
**From:** type-flags-and-adr.md  
**Description:** Policy-based authorization using feature flags with audit logging

**Components:**
- `App\Policies\Concerns\ChecksFeatureFlags.php` - Flag checking trait
- `App\Policies\FlagAwareTypePolicy.php` - Gates by feature flags
- `App\Models\FeAdrAudit.php` - Audit log model
- `App\Services\AdrAuditLogger.php` - Audit service

**Flag Convention:**
```
types.{TypeAlias}.{ability}
e.g., types.Invoice.create, types.Invoice.update
```

**ADR Audit Features:**
- Track all CRUD operations
- Link to ADR references via `X-ADR-Ref` header
- Automatic decision logging
- Compliance reporting

**Benefits:**
- Fine-grained permission control
- Complete audit trail
- ADR (Architecture Decision Record) integration
- Toggle features without code changes

## Priority 4: Soft Deletes and Advanced Routes

### Task: Add Soft Delete Support with Trash Management
**From:** type-policy-and-routes.md  
**Description:** Comprehensive soft delete support with restore capabilities

**New Endpoints:**
```
GET    /api/v2/types/{alias}/trash        # list soft-deleted
POST   /api/v2/types/{alias}/{id}/restore # restore deleted
DELETE /api/v2/types/{alias}/{id}/force   # permanent delete
```

**Implementation:**
- Add `SoftDeletes` trait to generated models
- Implement trash/restore/forceDelete in controller
- Policy gates for each operation
- UI components for trash management

## Implementation Roadmap

### Phase 1 (Week 1-2)
1. Implement TypesCodegen command
2. Create stub templates
3. Test with existing types

### Phase 2 (Week 2-3)
1. Build TypesCrudController
2. Implement adapter system
3. Add Sushi model support
4. Create demo Sushi models

### Phase 3 (Week 3-4)
1. Add feature flag authorization
2. Implement ADR audit logging
3. Create flag management UI
4. Add audit reporting

### Phase 4 (Week 4-5)
1. Add soft delete support
2. Build trash management UI
3. Implement restore functionality
4. Add force delete with confirmation

## Integration Points with v2 UI System

### DataSource Enhancement
```php
// Extend DataSourceManager to use TypeResolver
class TypeAwareDataSourceManager extends DataSourceManager {
    public function resolveFromType(string $typeAlias) {
        $resolver = new TypeResolverEx();
        return $resolver->resolve($typeAlias);
    }
}
```

### Component Config Extension
```json
{
  "type": "data-table",
  "dataSource": {
    "type": "type",
    "alias": "Invoice",
    "adapter": "sushi"
  }
}
```

### Action System Integration
```json
{
  "actions": {
    "delete": {
      "type": "crud",
      "operation": "soft-delete",
      "requiresFlag": "types.Invoice.delete",
      "auditRef": "ADR-2025-001"
    }
  }
}
```

## Benefits Summary

1. **Reduced Development Time**
   - Automatic code generation
   - Generic CRUD operations
   - Reusable adapters

2. **Enhanced Security**
   - Feature flag authorization
   - Complete audit trail
   - Policy-based access control

3. **Improved Flexibility**
   - Sushi models for external data
   - Adapter pattern for extensibility
   - Soft deletes with recovery

4. **Better Compliance**
   - ADR integration
   - Audit logging
   - Decision tracking

## Recommended Next Steps

1. **Immediate (This Sprint)**
   - Review and approve implementation plan
   - Set up TypesCodegen command structure
   - Create initial stub templates

2. **Short Term (Next Sprint)**
   - Implement CRUD controller
   - Add Sushi adapter
   - Create demo implementations

3. **Medium Term (Sprint +2)**
   - Add feature flag system
   - Implement audit logging
   - Build management UI

## Questions to Address

1. Should type definitions drive UI component generation?
2. How should Sushi models integrate with existing DataSources?
3. What level of audit detail is required for compliance?
4. Should feature flags be database or config driven?
5. How to handle type migrations in production?

## Conclusion

These enhancements from the fe-todo files would significantly improve the v2 UI system by adding:
- Automatic code generation
- Generic CRUD with adapters
- Feature flag authorization
- Complete audit trails
- Soft delete management

The implementation is modular and can be adopted incrementally without disrupting existing functionality.