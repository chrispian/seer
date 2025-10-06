# Database Schema Enhancement Context

## Current User Table Structure
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
```

## Current User Model
- Basic Laravel Authenticatable user
- `$guarded = []` (all fields mass assignable)
- Password hashing with `password` cast
- Basic factory and notification traits
- No profile-specific functionality

## Target Schema Enhancement
Add profile-focused fields to support setup wizard and settings system:
- **display_name**: User's preferred short name for UI display
- **avatar_path**: Local path to custom uploaded avatar
- **use_gravatar**: Boolean preference for Gravatar vs custom avatar
- **profile_settings**: JSON field for comprehensive user preferences
- **profile_completed_at**: Timestamp tracking setup completion

## Profile Settings JSON Structure
```json
{
  "preferences": {
    "theme": "system|light|dark",
    "density": "compact|comfortable|spacious",
    "notifications": {
      "toast_duration": 5000,
      "show_success": true,
      "show_errors": true,
      "sound_enabled": false
    },
    "ai": {
      "default_provider": "openai",
      "default_model": "gpt-4",
      "context_length": 4000
    }
  },
  "onboarding": {
    "completed_steps": ["profile", "avatar", "preferences"],
    "version": "1.0",
    "completed_at": "2024-01-01T00:00:00Z"
  }
}
```

## Migration Requirements
- **Backward Compatibility**: Existing users must continue working
- **Default Values**: New fields need sensible defaults
- **Data Integrity**: Proper constraints and validation
- **Rollback Safety**: Migration must be safely reversible
- **Performance**: Consider indexing needs for new fields

## User Model Enhancements Needed
```php
// New methods to add:
public function isProfileComplete(): bool
public function getAvatarUrlAttribute(): string
public function getDisplayNameAttribute(): string
public function getDefaultSettings(): array
public function updateProfileSettings(array $settings): void
```

## Integration Points
- **Setup Wizard**: Uses profile_completed_at to determine if setup is needed
- **Avatar System**: Uses avatar_path and use_gravatar for display
- **Settings Page**: Reads/writes profile_settings JSON
- **User Context**: Display name used throughout UI
- **Future Features**: Settings structure extensible for new preferences

## Security Considerations
- Validate JSON structure for profile_settings
- Sanitize display_name input
- Validate avatar_path for security
- Ensure profile_settings size limits
- Protect against JSON injection attacks