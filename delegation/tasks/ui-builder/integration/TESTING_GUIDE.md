# UI Builder v2 - Integration Testing Guide

## Quick Start

### 1. Run Migrations & Seed
```bash
cd /Users/chrispian/Projects/seer

# Run migrations (if not already done)
php artisan migrate

# Seed the demo page
php artisan db:seed --class=V2UiBuilderSeeder
```

**Expected Output:**
```
Seeding UI Builder v2 demo pages...
✓ Seeded page: page.agent.table.modal
  Version: 1
  Hash: 678a9c5e5bbe0ae722f8bc4d29cb638dddafbbcdb4c48c504557b8a5d5947753

Demo page available at:
  → /v2/pages/page.agent.table.modal
```

### 2. Build Assets
```bash
npm run build
```

**Expected Output:**
```
✓ built in ~4s
public/build/assets/v2-app-Dm51YYja.js   2.93 kB │ gzip: 1.33 kB
```

### 3. Start Development Server
```bash
composer run dev
```

This starts Laravel server + queue worker + Vite HMR.

## Test URLs

### Web Routes (SSR + CSR)
- **Demo Page**: http://localhost:8000/v2/pages/page.agent.table.modal
  - Expected: Modal overlay opens
  - Expected: Shows "Unknown component type" (until FE primitives implemented)
  - Auth: Required (EnsureDefaultUser middleware)

### API Routes (JSON)
- **Page Config**: http://localhost:8000/api/v2/ui/pages/page.agent.table.modal
  - Method: GET
  - Expected: JSON with `id`, `key`, `config`, `hash`, `version`

- **DataSource Query**: http://localhost:8000/api/v2/ui/datasource/Agent/query
  - Method: POST
  - Body: `{"search": "test", "filters": {}, "pagination": {"page": 1, "perPage": 20}}`
  - Expected: Paginated Agent list (when implemented)

- **Action Execute**: http://localhost:8000/api/v2/ui/action
  - Method: POST
  - Body: `{"type": "command", "command": "/orch-agent", "params": {"id": 1}}`
  - Expected: Action result (when implemented)

## Manual Testing Checklist

### Phase 1: Integration Layer (Current State)
- [ ] Navigate to `/v2/pages/page.agent.table.modal`
- [ ] Page loads without 404
- [ ] Auth is enforced (redirect to login if not authenticated)
- [ ] Modal/Dialog component renders
- [ ] Page title "Agents" displays
- [ ] See "Unknown component type: search.bar" message
- [ ] See "Unknown component type: table" message
- [ ] No console errors (warnings OK)

### Phase 2: With FE Primitives (After FE Agent)
- [ ] Search bar component renders
- [ ] Table component renders
- [ ] Toolbar with "New Agent" button renders
- [ ] Search input accepts text
- [ ] Search triggers datasource query
- [ ] Table displays Agent rows
- [ ] Row click dispatches command
- [ ] "New Agent" button dispatches command
- [ ] Modal closes on backdrop click or ESC

### Phase 3: Full E2E Flow
- [ ] Create new Agent via modal
- [ ] Search filters results
- [ ] Click Agent row opens detail
- [ ] All CRUD operations work
- [ ] No data leaks between sessions
- [ ] Error states handled gracefully

## Current State vs. Expected State

| Component | Current | Expected (with FE primitives) |
|-----------|---------|-------------------------------|
| Route `/v2/pages/{key}` | ✅ Works | ✅ Works |
| API `/api/v2/ui/pages/{key}` | ✅ Returns JSON | ✅ Returns JSON |
| Auth middleware | ✅ Applied | ✅ Applied |
| Modal overlay | ✅ Renders | ✅ Renders |
| Page title | ✅ "Agents" | ✅ "Agents" |
| Search bar | ⚠️ "Unknown component" | ✅ Functional input |
| Table | ⚠️ "Unknown component" | ✅ Displays data |
| Toolbar button | ⚠️ "Unknown component" | ✅ Clickable |

## Debugging

### Check if page exists in database
```bash
php artisan tinker
>>> App\Models\FeUiPage::where('key', 'page.agent.table.modal')->first()
```

### Check Vite manifest
```bash
cat public/build/manifest.json | grep v2
```

Expected:
```json
"resources/js/v2-app.tsx": {
  "file": "assets/v2-app-Dm51YYja.js",
  "name": "v2-app"
}
```

### Check route registration
```bash
php artisan route:list --path=v2
```

Expected:
```
GET|HEAD  v2/pages/{key} ... V2ShellController@show
```

### Check browser console
Open DevTools → Console. Expected logs:
```
Fetching page config from /api/v2/ui/pages/page.agent.table.modal
```

### Check Network tab
1. Navigate to `/v2/pages/page.agent.table.modal`
2. Open DevTools → Network
3. Look for:
   - `v2-app-Dm51YYja.js` (200 OK)
   - `/api/v2/ui/pages/page.agent.table.modal` (200 OK, JSON response)

## Known Issues

### Issue: "Unknown component type: table"
**Status**: Expected  
**Reason**: FE primitives not implemented yet  
**Fix**: FE agent needs to create and register components

### Issue: Page returns 404
**Cause**: Seeder not run or page key mismatch  
**Fix**: Run `php artisan db:seed --class=V2UiBuilderSeeder`

### Issue: Auth redirect loop
**Cause**: No default user in database  
**Fix**: Run setup wizard or create user manually

### Issue: Vite asset not found
**Cause**: Build not run after adding v2-app.tsx  
**Fix**: Run `npm run build`

## Next Steps After Testing

1. **If integration layer works**: Handoff to FE agent for primitives
2. **If API endpoints fail**: Check BE agent deliverables
3. **If auth fails**: Check middleware configuration
4. **If build fails**: Check Vite config syntax

## Success Criteria

**Integration is complete when:**
- ✅ `/v2/pages/{key}` route accessible
- ✅ Auth middleware enforced
- ✅ Page config fetched from API
- ✅ Modal/sheet/page overlay renders
- ✅ Component renderer shows graceful degradation
- ✅ No 404 or 500 errors
- ✅ Build produces v2-app artifact

**E2E is complete when:**
- ⏳ All component types render (blocked on FE primitives)
- ⏳ Search/filter/sort work (blocked on FE primitives)
- ⏳ Actions dispatch correctly (blocked on FE primitives)
- ⏳ Data flows end-to-end (blocked on FE primitives)

---

**Status**: Integration layer COMPLETE, ready for FE primitives  
**Last Updated**: 2025-10-15  
**Agent**: Integration Agent
