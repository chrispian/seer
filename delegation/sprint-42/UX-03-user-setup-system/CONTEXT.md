# User Setup System Context

## Current Authentication State
- Basic Laravel Auth with placeholder AuthModal component
- AppShellController checks `Auth::check()` and `User::exists()`
- Frontend receives auth state via `__APP_BOOT__` global
- No actual login/registration implementation
- AuthModal shows placeholder text: "(Auth form goes here)"

## Target Vision: Setup-First Experience
- Replace login/auth with one-time setup wizard for new installations
- Single-user focus appropriate for NativePHP desktop application
- Profile-based user management with settings persistence
- Gravatar integration with local caching for offline functionality
- Comprehensive settings management for user preferences

## Technical Architecture Goals
- **Database Enhancement**: Add profile fields (display_name, avatar_path, use_gravatar, profile_settings)
- **Setup Wizard**: Multi-step onboarding using shadcn components
- **Avatar System**: Gravatar integration + custom upload capability
- **Settings Management**: Tabbed settings interface with preference persistence
- **API Layer**: RESTful endpoints for profile and settings operations

## Key Integration Points
- Replace AuthModal with SetupWizard when no users exist
- Integrate with existing AppShell and user context
- Maintain compatibility with current session handling
- Preserve existing chat functionality and user associations
- Support future multi-user capability without breaking changes

## NativePHP Optimization Requirements
- Local file storage for avatars and cached Gravatar images
- Offline-friendly operation with cached data
- Desktop app UX patterns vs. web app patterns
- Secure local file handling and validation
- Performance optimization for desktop environment

## User Experience Flow
1. **First Launch**: Setup wizard collects profile and preferences
2. **Subsequent Launches**: Direct access to application
3. **Settings Access**: Available via sidebar or user menu
4. **Profile Updates**: Real-time preview and validation
5. **Avatar Management**: Toggle between Gravatar and custom upload

## Security Considerations
- File upload validation and sanitization
- Avatar file type restrictions and size limits
- Input validation for all profile fields
- Secure storage of user preferences
- Protection against malicious file uploads

## Success Metrics
- Intuitive setup wizard completion under 2 minutes
- Reliable Gravatar integration with fallback handling
- Settings changes persist across application restarts
- Professional avatar management experience
- Zero authentication-related user confusion