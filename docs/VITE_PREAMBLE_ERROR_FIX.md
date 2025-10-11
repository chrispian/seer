# Vite Preamble Error - White Screen Fix

## Problem
**Error**: `Uncaught Error: @vitejs/plugin-react can't detect preamble. Something is wrong.`
**Symptom**: White screen on `/` route or any page with React components
**Location**: Browser console, typically at `dialog.tsx:19:3` or similar component file

## Root Cause (UPDATED)
This error occurs **ONLY in dev server (HMR) mode**, NOT in production builds.

The `@vitejs/plugin-react` Fast Refresh feature requires `window.$RefreshReg$` to be set up before any React components load. When:
1. **Agents make rapid file changes** during development
2. **HMR state gets corrupted** from malformed intermediate edits
3. **Module load order changes** and a component loads before the preamble

The Fast Refresh preamble fails to inject, causing the white screen error.

**Key Discovery**: Production builds (`npm run build`) work fine. Only `npm run dev` breaks.

## Why It Keeps Happening
This error recurs during development because:
1. **Agents make rapid file changes** - When agents edit multiple files quickly, HMR state gets corrupted
2. **Intermediate broken states** - Agents sometimes save files mid-edit, breaking the module graph
3. **Fast Refresh preamble race condition** - Component loads before `window.$RefreshReg$` is set up
4. **HMR state persistence** - Once corrupted, the bad state persists until Vite cache is cleared

**This is why agents using `npm run dev` to test often causes white screens, but `npm run build` always works.**

## Affected Files (Current)
As of last check, these files are missing the React import:
- `/resources/js/islands/chat/tiptap/extensions/FileUpload.tsx`
- `/resources/js/components/ui/ToastContainer.tsx`
- `/resources/js/components/ui/tag-input.tsx`
- `/resources/js/components/types/TypeDetailModal.tsx`
- `/resources/js/components/types/TypeManagementModal.tsx`
- `/resources/js/components/fragments/FragmentListModal.tsx`
- `/resources/js/components/security/SecurityDashboardModal.tsx`
- `/resources/js/components/projects/ProjectListModal.tsx`
- `/resources/js/components/type-system/TypePackList.tsx`
- `/resources/js/components/type-system/TypePackEditor.tsx`
- `/resources/js/components/type-system/TypePackManagement.tsx`
- `/resources/js/components/type-system/SchemaEditor.tsx`
- `/resources/js/components/unified/UnifiedListModal.tsx`
- `/resources/js/components/unified/UnifiedDetailModal.tsx`
- `/resources/js/components/agents/AgentMiniCard.tsx`
- `/resources/js/components/agents/AgentProfileMiniCard.tsx`
- `/resources/js/components/agents/AgentProfileEditor.tsx`
- `/resources/js/components/agents/AgentEditor.tsx`
- `/resources/js/components/bookmarks/BookmarkListModal.tsx`
- `/resources/js/components/vaults/VaultListModal.tsx`
- `/resources/js/components/orchestration/TaskActivityTimeline.tsx`
- `/resources/js/components/orchestration/TaskDetailModal.tsx`
- `/resources/js/components/orchestration/AgentProfileListModal.tsx`
- `/resources/js/components/orchestration/AgentProfileGridModal.tsx`
- `/resources/js/components/orchestration/TaskContentEditor.tsx`
- `/resources/js/components/routing/RoutingInfoModal.tsx`
- `/resources/js/components/channels/ChannelListModal.tsx`
- `/resources/js/hooks/useFullScreenModal.tsx`
- `/resources/js/hooks/useTodoData.tsx`
- `/resources/js/lib/icons.tsx`
- `/resources/js/pages/AgentProfileDashboard.tsx`
- `/resources/js/pages/AgentDashboard.tsx`

## Quick Fix (Emergency)

### Option 1: Use Production Build (Fastest)
```bash
# Stop dev server
pkill -f "vite"

# Build production assets
npm run build

# Reload browser (assets will load from public/build/ instead of dev server)
# Site should work immediately
```

### Option 2: Clear HMR Cache and Restart Dev Server
```bash
# Stop dev server
pkill -f "vite"

# Clear Vite's HMR cache
rm -rf node_modules/.vite

# Start fresh dev server
npm run dev

# Hard refresh browser (Cmd+Shift+R or Ctrl+Shift+R)
```

### Option 3: Nuclear Option (if above don't work)
```bash
rm -rf node_modules package-lock.json node_modules/.vite
npm install
npm run build
```

## Permanent Fix (Prevent Recurrence)

### 1. Vite Configuration (APPLIED)
Updated `vite.config.ts` to make HMR more resilient:
- Disabled error overlay (`overlay: false`) - prevents white screen on HMR errors
- Added watch ignore patterns for large directories
- Optimized dependency pre-bundling

```ts
// vite.config.ts
server: {
    hmr: {
        overlay: false, // Don't block UI on HMR errors
    },
    watch: {
        ignored: ['**/node_modules/**', '**/storage/**', '**/vendor/**'],
    },
},
```

### 2. All .tsx Files Have React Imports (COMPLETED)
All 146 `.tsx` files now have proper React imports at the top.
This isn't strictly required with automatic JSX runtime, but helps with compatibility.

### 2. Create Pre-commit Hook
Add to `.husky/pre-commit` or `scripts/check-react-imports.sh`:
```bash
#!/bin/bash
# Check all .tsx files have React import
missing=$(find resources/js -name "*.tsx" -type f -exec sh -c '
  head -10 "$1" | grep -q "^import.*React" || echo "$1"
' _ {} \;)

if [ -n "$missing" ]; then
  echo "ERROR: The following .tsx files are missing React import:"
  echo "$missing"
  exit 1
fi
```

### 3. ESLint Rule (Recommended)
Add to `.eslintrc.json`:
```json
{
  "rules": {
    "react/react-in-jsx-scope": "error"
  }
}
```

### 4. Update Component Templates
When creating new components, always use this template:
```tsx
import React from 'react'

export function ComponentName() {
  return (
    <div>
      {/* component content */}
    </div>
  )
}
```

## Diagnostic Commands

### Find files missing React import:
```bash
find resources/js -name "*.tsx" -type f -exec sh -c 'head -10 "$1" | grep -q "^import.*React" || echo "$1"' _ {} \;
```

### Check Vite build:
```bash
npm run build
# Should complete without errors
```

### Check dev server:
```bash
npm run dev
# Should start without preamble errors
```

## Prevention Checklist

### For Developers
- [ ] Use `npm run build` to test changes (more reliable than dev server)
- [ ] If using dev server breaks, clear cache: `rm -rf node_modules/.vite && npm run dev`
- [ ] Hard refresh browser after restarting dev server
- [ ] Check browser console for exact file causing preamble error

### For Agents
- [ ] **Prefer `npm run build` over `npm run dev`** for testing
- [ ] Don't leave files in broken intermediate states
- [ ] Complete all edits to a file before moving to the next
- [ ] If HMR errors occur, recommend clearing Vite cache instead of reinstalling node_modules

## Related Issues
- Settings page blank white screen → Fixed by adding React imports
- Modal components not rendering → Fixed by adding React imports
- HMR breaking during development → Fixed by ensuring all files have imports

## Technical Details
- **Vite Version**: 6.2.4
- **@vitejs/plugin-react Version**: 4.7.0
- **React Version**: 19.1.1
- The plugin uses AST parsing to detect JSX and needs the import as a marker
- Without the import, the plugin skips transformation, breaking JSX at runtime
