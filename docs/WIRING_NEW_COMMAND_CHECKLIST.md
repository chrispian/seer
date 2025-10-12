# Wiring Up a New Command Checklist

**Based on**: SecurityDashboardModal implementation (2025-10-12)

This checklist covers what's needed to wire up an existing modal component that has a handler but no command registration.

---

## Prerequisites

- [ ] Modal component exists in `resources/js/components/`
- [ ] Handler class exists in `app/Commands/`
- [ ] Handler returns data in correct format

---

## Step 1: Update the Handler

**File**: `app/Commands/YourModule/YourCommand.php`

### Old Format (Don't Use)
```php
public function handle(): array
{
    $data = $this->getData();
    
    return [
        'type' => 'your-type',
        'component' => 'YourModal',
        'data' => $data,
    ];
}
```

### New Format (Use This)
```php
public function handle(): array
{
    $data = $this->getData();
    
    return $this->respond([
        'your_data_key' => $data,
    ]);
}
```

**Example** (SecurityDashboardModal):
```php
return $this->respond([
    'approval_requests' => $approvalRequests,
    'stats' => $stats,
]);
```

---

## Step 2: Add Default Values to Modal Props

**File**: `resources/js/components/your-module/YourModal.tsx`

Prevent "Cannot read properties of undefined" errors:

```typescript
export function YourModal({
  isOpen,
  onClose,
  your_data = [],              // Add default empty array
  your_stats = {               // Add default empty object
    count: 0,
    // ... other fields
  },
  loading = false,
  error = null,
  onRefresh
}: YourModalProps) {
```

**Example** (SecurityDashboardModal):
```typescript
export function SecurityDashboardModal({
  isOpen,
  onClose,
  approval_requests = [],      // Default empty array
  stats = {                    // Default empty object
    pending_count: 0,
    approved_today: 0,
    rejected_today: 0,
    timed_out_count: 0,
    high_risk_pending: 0
  },
  loading = false,
  error = null,
  onRefresh
}: SecurityDashboardModalProps) {
```

---

## Step 3: Register Modal in COMPONENT_MAP

**File**: `resources/js/islands/chat/CommandResultModal.tsx`

### Add Import
```typescript
import { YourModal } from '@/components/your-module/YourModal'
```

**Example**:
```typescript
import { SecurityDashboardModal } from '@/components/security/SecurityDashboardModal'
```

### Add to COMPONENT_MAP
```typescript
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  'DataManagementModal': DataManagementModal,
  // ... other modals
  'YourModal': YourModal,
}
```

**Example**:
```typescript
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  // ...
  'SecurityDashboardModal': SecurityDashboardModal,
  // ...
}
```

---

## Step 4: Add Command to Seeder

**File**: `database/seeders/CommandsSeeder.php`

Add to the `$commands` array:

```php
[
    'command' => '/your-command',
    'name' => 'Your Command Name',
    'description' => 'Description of what it does',
    'category' => 'YourCategory',
    'type_slug' => null,  // or 'your-type' if using types
    'handler_class' => 'App\\Commands\\YourModule\\YourCommand',
    'available_in_slash' => true,
    'available_in_cli' => false,
    'available_in_mcp' => false,
    'ui_modal_container' => 'YourModal',
    'ui_layout_mode' => null,
    'ui_card_component' => null,
    'ui_detail_component' => null,
    'filters' => null,
    'default_sort' => null,
    'pagination_default' => 100,
    'is_active' => true,
],
```

**Example** (SecurityDashboardModal):
```php
[
    'command' => '/security',
    'name' => 'Security Dashboard',
    'description' => 'View approval requests, risk scores, and security status',
    'category' => 'Security',
    'type_slug' => null,
    'handler_class' => 'App\\Commands\\Security\\DashboardCommand',
    'available_in_slash' => true,
    'available_in_cli' => false,
    'available_in_mcp' => false,
    'ui_modal_container' => 'SecurityDashboardModal',
    'ui_layout_mode' => null,
    'ui_card_component' => null,
    'ui_detail_component' => null,
    'filters' => null,
    'default_sort' => null,
    'pagination_default' => 100,
    'is_active' => true,
],
```

---

## Step 5: Run Database Seeder

```bash
php artisan db:seed --class=CommandsSeeder
```

Output should show:
```
✅ Seeded 11 commands and cleared cache
```

---

## Step 6: Clear Cache

```bash
php artisan cache:clear
```

Output should show:
```
INFO  Application cache cleared successfully.
```

---

## Step 7: Build Frontend

**IMPORTANT**: Hot reload doesn't work in this project. Always build after frontend changes.

```bash
npm run build
```

Wait for:
```
✓ built in 4.xx s
```

---

## Step 8: Test the Command

In the app, type:
```
/your-command
```

**Expected**:
- Modal opens
- Data displays (or shows empty state if no data)
- No console errors

**If you see errors**:
- "Cannot read properties of undefined" → Check Step 2 (default values)
- "Component not found" → Check Step 3 (COMPONENT_MAP)
- "Command not found" → Check Step 4 & 5 (seeder)
- Blank modal → Check Step 1 (handler response format)

---

## Common Issues

### Modal Shows Blank/Empty
**Cause**: Handler not using `respond()` or wrong data key
**Fix**: Update handler to use `$this->respond(['key' => $data])`

### "Cannot read properties of undefined"
**Cause**: Modal expects props but receives undefined
**Fix**: Add default values to modal props (Step 2)

### "Component not found in registry"
**Cause**: Modal not registered in COMPONENT_MAP
**Fix**: Import and add to COMPONENT_MAP (Step 3)

### Command Not Found in Chat
**Cause**: Not seeded or cache not cleared
**Fix**: Run seeder and clear cache (Steps 5-6)

### Changes Don't Appear
**Cause**: Frontend not rebuilt (hot reload doesn't work)
**Fix**: Always run `npm run build` after frontend changes (Step 7)

---

## Data Extraction Pattern

The system automatically extracts data from `result.data` and passes it as props:

**Backend Returns**:
```php
return $this->respond([
    'approval_requests' => [...],
    'stats' => {...},
]);
```

**Frontend Receives**:
```typescript
<SecurityDashboardModal
  approval_requests={[...]}
  stats={{...}}
  isOpen={true}
  onClose={...}
/>
```

The config system handles this automatically via `navigation_config` or by spreading `result.data` for non-list modals.

---

## Verification Checklist

After completing all steps:

- [ ] Handler uses `$this->respond()`
- [ ] Modal has default prop values
- [ ] Modal imported in CommandResultModal
- [ ] Modal added to COMPONENT_MAP
- [ ] Command added to CommandsSeeder
- [ ] Seeder run successfully
- [ ] Cache cleared
- [ ] Frontend built with `npm run build`
- [ ] Command works in chat (no errors)
- [ ] Modal displays data or empty state correctly

---

## Example: Complete SecurityDashboardModal Wiring

### Commits
1. `feat: wire up /security command with SecurityDashboardModal` - Handler, seeder, registration
2. `fix: add default values for SecurityDashboardModal props` - Prevent undefined errors

### Files Changed
- `app/Commands/Security/DashboardCommand.php` - Updated to use `respond()`
- `resources/js/components/security/SecurityDashboardModal.tsx` - Added default values
- `resources/js/islands/chat/CommandResultModal.tsx` - Import + COMPONENT_MAP
- `database/seeders/CommandsSeeder.php` - Added `/security` command

### Test Command
```
/security
```

### Expected Result
Security dashboard opens showing approval requests and stats (or empty state if no data).

---

**Reference**: See `feature/config-driven-navigation-v2` branch commits `408ac55` and `34f9660`
