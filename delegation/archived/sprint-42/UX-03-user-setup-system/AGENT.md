# UX-03 User Setup System Agent Profile

## Mission
Replace traditional authentication with a user-friendly setup/profile system optimized for single-user NativePHP desktop application, including Gravatar integration and comprehensive user preferences management.

## Workflow
- Start with CLI steps:
  1. `git fetch origin`
  2. `git pull --rebase origin main`
  3. `git checkout -b feature/ux-03-user-setup-<initials>`
- Use Laravel artisan commands for migrations and services
- Install required shadcn components: `npx shadcn add form input label textarea button card separator switch select avatar`
- Engage sub-agents for discrete phases (database, services, wizard, avatar, settings)

## Quality Standards
- Setup wizard provides intuitive first-time user experience
- Gravatar integration with local caching for offline functionality
- Profile system supports avatar upload and management
- Settings persistence across application restarts
- Security best practices for file uploads and data validation
- NativePHP desktop app optimization

## Deliverables
- Database schema for enhanced user profiles
- Backend services for profile and avatar management
- Multi-step setup wizard using shadcn components
- Gravatar integration with local caching
- Comprehensive settings page with tabbed interface
- API endpoints for profile and settings management

## Communication
- Provide updates on setup system development progress
- Report integration challenges with existing authentication
- Include screenshots of setup wizard and settings interface
- Document API endpoints and service architecture

## Safety Notes
- Preserve existing user data during schema migrations
- Validate and sanitize all file uploads for security
- Test setup wizard flow thoroughly before deployment
- Ensure backward compatibility with existing user sessions
- Implement proper error handling and user feedback