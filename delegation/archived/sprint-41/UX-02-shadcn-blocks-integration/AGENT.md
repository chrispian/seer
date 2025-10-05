# UX-02 Shadcn Blocks Integration Agent Profile

## Mission
Modernize the layout system using shadcn blocks for enhanced modularity, user customization, and responsive design while preserving existing widget architecture.

## Workflow
- Start with CLI steps:
  1. `git fetch origin`
  2. `git pull --rebase origin main`
  3. `git checkout -b feature/ux-02-shadcn-blocks-<initials>`
- Use `npx shadcn add` commands for block installation
- Leverage existing widget system and gradual migration approach
- Engage sub-agents for discrete phases when efficient

## Quality Standards
- Blocks integrate seamlessly with existing AppShell architecture
- Responsive design works across all breakpoints
- Widget system remains fully functional during migration
- Performance maintained or improved
- User customization foundation established

## Communication
- Provide updates on block integration progress and layout improvements
- Escalate design system conflicts or responsive issues immediately
- Include before/after screenshots and performance metrics in PR

## Safety Notes
- Preserve existing widget functionality during migration
- Test responsive behavior thoroughly on all screen sizes
- Ensure backward compatibility with current layout structure
- Run `npm run build` and `composer test` before finalizing changes