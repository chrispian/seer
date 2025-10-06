# SETTINGS-001 Context: Import/Reset Settings Pipeline

## Current Settings Architecture

### Backend Structure
```php
// Existing endpoints
POST /api/settings/preferences  // General preferences, theme, notifications
POST /api/settings/profile      // User profile data
GET  /api/settings/export       // Current export functionality

// Data storage
- User model: name, email, display_name
- profile_settings JSON column: ai config, preferences
- Preference categories: theme, layout, notifications, AI
```

### Frontend Components
```typescript
// Current settings structure
/resources/js/islands/Settings/
├── SettingsLayout.tsx          // Tab navigation
├── ProfileTab.tsx              // Profile management
├── PreferencesTab.tsx          // Theme, notifications, layout
├── AppearanceTab.tsx           // Visual settings
└── components/
    ├── ImportExportControls.tsx // Has export, import, reset buttons
    └── ...other components
```

### Current Export Format
The existing export creates JSON with structure:
```json
{
  "profile": {
    "name": "string",
    "display_name": "string", 
    "email": "string"
  },
  "preferences": {
    "theme": "light|dark|system",
    "language": "en",
    "timezone": "UTC",
    "notifications": {
      "email": boolean,
      "desktop": boolean,
      "sound": boolean
    },
    "layout": {
      "sidebar_collapsed": boolean,
      "compact_mode": boolean
    }
  },
  "ai": {
    "provider": "string",
    "model": "string",
    "context_length": number,
    "streaming": boolean,
    "auto_title": boolean
  }
}
```

## Implementation Requirements

### New Backend Endpoints

#### Import Endpoint
```php
POST /api/settings/import
Content-Type: multipart/form-data

Request:
- file: uploaded JSON file
- confirm_overwrite: boolean (optional)

Response:
{
  "success": true,
  "changes": {
    "profile": ["name", "email"],
    "preferences": ["theme", "notifications.email"],
    "ai": ["provider", "model"]
  },
  "skipped": ["invalid_field"],
  "warnings": ["field_deprecated"]
}
```

#### Reset Endpoint
```php
POST /api/settings/reset
Content-Type: application/json

Request:
{
  "sections": ["profile", "preferences", "ai"], // optional, defaults to all
  "confirm_token": "uuid" // required for safety
}

Response:
{
  "success": true,
  "reset_sections": ["preferences", "ai"],
  "preserved": ["profile"] // if not included in sections
}
```

### Frontend Dialogs

#### Import Dialog Flow
1. **File Selection**: Native file picker (JSON only)
2. **Preview**: Show changes that would be made
3. **Confirmation**: Accept/reject with overwrite options
4. **Progress**: Upload and processing indicators
5. **Results**: Success summary or error details

#### Reset Dialog Flow
1. **Section Selection**: Choose what to reset (checkboxes)
2. **Confirmation**: "This will permanently reset..." warning
3. **Progress**: Reset operation indicator
4. **Results**: Success confirmation with what was reset

## Integration Points

### Existing Controllers
- `app/Http/Controllers/Settings/ProfileController.php`
- `app/Http/Controllers/Settings/PreferencesController.php`
- Need new `app/Http/Controllers/Settings/ImportExportController.php`

### Validation Rules
- Reuse existing validation from ProfileController and PreferencesController
- Add file validation for import
- Schema validation for JSON structure

### Error Handling
- File validation errors (size, type, structure)
- Settings validation errors (invalid values)
- Authorization errors (user can't modify certain settings)
- System errors (disk space, database issues)

## Security Considerations

### File Upload Security
- Restrict to JSON files only
- File size limits (e.g., 1MB max)
- Scan for malicious content
- Temporary file cleanup

### Settings Validation
- Whitelist allowed setting keys
- Validate enum values (theme, language, etc.)
- Range validation for numeric settings
- Sanitize string values

### Authorization
- Users can only import/reset their own settings
- Admin settings require admin role
- Audit log all import/reset operations

## Testing Strategy

### Unit Tests
- Settings validation logic
- Import file parsing
- Reset logic with section selection
- Error handling scenarios

### Integration Tests
- Full import flow with valid/invalid files
- Reset flow with confirmation
- Error responses for various failure modes
- Audit logging verification

### Frontend Tests
- Dialog interactions and state management
- File upload handling
- Progress indicator behavior
- Error message display