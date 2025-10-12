# Vite Preamble Error - Root Cause & Fix

## Error
```
Uncaught Error: @vitejs/plugin-react can't detect preamble. Something is wrong.
    at dialog.tsx:19:3
```

## Root Cause
**Corrupted `node_modules` directory** - specifically the `@vitejs/plugin-react` Fast Refresh runtime was not properly setting up `window.$RefreshReg$`.

## Technical Details
- The error occurred because `window.$RefreshReg$` was undefined
- This global is set by Vite's React Fast Refresh preamble
- It's injected by `@vitejs/plugin-react` when transforming React components
- A corrupted or incomplete `node_modules` installation prevented the preamble from being injected

## The Fix
```bash
rm -rf node_modules
npm install
npm run build
```

## How We Found It
1. Tested multiple git commits - ALL showed white screen
2. Build succeeded without errors every time
3. Vite dev server connected successfully
4. Checked transformed code from Vite - saw the error checking for `window.$RefreshReg$`
5. Realized the issue wasn't in our code but in the Vite transform pipeline
6. Fresh `node_modules` install resolved it immediately

## Testing Checkpoint
- **Working commit**: `8eca2ec` (refactor(sidebar): wire VaultSelector, UserMenu, SidebarHeader into AppSidebar)
- **Date**: 2025-10-10
- **Verification**: Page loads, no white screen, Vite HMR works

## Next Steps
Apply this fix to checkpoint branch `be25453` to verify it works there too.
