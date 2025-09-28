# CHAT-M1 Shell Solidification Agent

## Mission
Align the new React + shadcn shell with the legacy Livewire layout (see `reference-01.html`) by replacing placeholder “Hello” blocks with proper card-based scaffolding in the ribbon, left nav, chat header, and right rail while preserving the neutral theme.

## Getting Started
1. `git fetch origin`
2. `git checkout -b feature/chat-m1-shell`
3. Install dependencies if missing: `composer install`, `npm install`
4. Run Vite dev server (`npm run dev`) and Laravel (`php artisan serve`) to confirm the new shell renders.

## Key Context
- Layout entry: `resources/views/app/chat.blade.php` mounts the islands defined in `resources/js/boot.tsx`.
- Target components for refinement:
  - `resources/js/islands/shell/Ribbon.tsx`
  - `resources/js/islands/shell/LeftNav.tsx`
  - `resources/js/islands/shell/ChatHeader.tsx`
  - `resources/js/islands/shell/RightRail.tsx`
- Visual reference: `/Users/chrispian/Projects/seer/reference-01.html` mirrors the previous Livewire UI.
- Styling approach: Tailwind v4 utility classes + shadcn Card, Button, Tabs, etc. Keep palette neutral (white background, system font, black accents, subtle shadows).

## Deliverables
- Each target component renders a shadcn Card (or composed primitives) that matches the structure from `reference-01.html` (sections, headings, placeholder lists) without wiring live data.
- Responsive layout respects three-column grid; center column reserved for chat transcript/composer.
- Ensure theme tokens are centralized (e.g., wrapper class on container) to simplify later theming work.
- No regressions to existing chat functionality; streaming should still work.

## Definition of Done
- Visual shell closely mirrors the reference layout with production-ready scaffolding.
- Neutral theme applied consistently; no stray placeholder text.
- Storybook/docs update optional but include screenshots or notes in PR.
- Run `npm run build` (to catch Tailwind issues) and `composer test` before requesting review.
