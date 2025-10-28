# TypesController Proxy Removal

**Date:** October 28, 2025  
**Version:** 2.1.0 (breaking change from 2.0.0)

## What Changed

Removed the confusing `TypesController` proxy that was creating ambiguity about which system was the source of truth.

## Files Modified

### Added/Modified in `vendor/hollis-labs/ui-builder/`

1. **src/Http/Controllers/DataSourceController.php**
   - Added `show(alias, id)` method - Get single record
   - Added `update(alias, id, data)` method - Update record
   - Added `destroy(alias, id)` method - Delete record
   
2. **routes/api.php**
   - Added full CRUD routes to `/api/ui/datasources/{alias}/{id}`
   - Removed TypesController import and routes
   - Removed `/api/ui/types/*` routes entirely

3. **database/seeders/UiBuilderPagesSeeder.php**
   - Changed page list modal URL from `/api/ui/types/UiPage/{{row.id}}` to `/api/ui/datasources/UiPage/{{row.id}}`
   - Changed component list modal URL from `/api/ui/types/UiComponent/{{row.id}}` to `/api/ui/datasources/UiComponent/{{row.id}}`

### Deleted

- `src/Http/Controllers/TypesController.php` - Entire file removed

## API Route Changes

### Before (v2.0.0)
```
GET /api/ui/types/{alias}/query     -> TypesController@query
GET /api/ui/types/{alias}/{id}      -> TypesController@show
```

### After (v2.1.0)
```
GET    /api/ui/datasources/{alias}           -> DataSourceController@query
POST   /api/ui/datasources/{alias}           -> DataSourceController@store
GET    /api/ui/datasources/{alias}/{id}      -> DataSourceController@show
PUT    /api/ui/datasources/{alias}/{id}      -> DataSourceController@update
PATCH  /api/ui/datasources/{alias}/{id}      -> DataSourceController@update
DELETE /api/ui/datasources/{alias}/{id}      -> DataSourceController@destroy
GET    /api/ui/datasources/{alias}/capabilities -> DataSourceController@capabilities
```

## Why This Change?

After removing the FeType system in v2.0.0, the TypesController became a confusing proxy that:
1. Made it unclear which controller/system was the source of truth
2. Maintained backward compatibility we don't need (nothing in production yet)
3. Used legacy route naming (`types` instead of `datasources`)

Since we're at v2.x already and nothing is in production, this is the perfect time to eliminate the confusion and have a single, clear API structure.

## Breaking Changes

Any code calling:
- `/api/ui/types/{alias}/query` → Change to `/api/ui/datasources/{alias}`
- `/api/ui/types/{alias}/{id}` → Change to `/api/ui/datasources/{alias}/{id}`

## Verification

```bash
# Test the new endpoint
curl http://seer.test/api/ui/datasources/UiPage/1

# Should return 200 with page data
```

Tested: ✅ GET `/api/ui/datasources/UiPage/1` returns 200 with correct data

## Git Commands for Repository Sync

```bash
# In the actual UI Builder repository:

# Delete old controller
git rm src/Http/Controllers/TypesController.php

# Add modified files
git add src/Http/Controllers/DataSourceController.php
git add routes/api.php
git add database/seeders/UiBuilderPagesSeeder.php

# Commit
git commit -m "refactor: Remove TypesController proxy, use DataSource routes directly

**Breaking Change - v2.1.0**

Eliminates confusing TypesController proxy in favor of direct DataSource routes.

Changes:
- Add show(), update(), destroy() methods to DataSourceController
- Add full CRUD routes to /api/ui/datasources/{alias}/{id}
- Update seeded pages to use /api/ui/datasources URLs
- Delete TypesController entirely
- Remove /api/ui/types routes

Breaking:
Old: GET /api/ui/types/{alias}/{id}
New: GET /api/ui/datasources/{alias}/{id}

Why: TypesController was a confusing proxy creating ambiguity about which system is the source of truth."

# Tag new version
git tag v2.1.0
git push origin main --tags
```

## Summary

**Before:** TypesController proxying to DataSourceResolver  
**After:** DataSourceController with direct implementation

This eliminates confusion and creates a single, clear API surface.
