# SETTINGS-001 TODO: Import/Reset Settings Pipeline

## Backend Implementation

### Controllers & Routes
- [ ] Create `app/Http/Controllers/Settings/ImportExportController.php`
  - [ ] `import()` method with file handling
  - [ ] `reset()` method with section selection
  - [ ] `generateResetToken()` for security
- [ ] Add routes to `routes/api.php`:
  - [ ] `POST /api/settings/import`
  - [ ] `POST /api/settings/reset`
  - [ ] `POST /api/settings/reset-token`

### Request Validation
- [ ] Create `app/Http/Requests/Settings/ImportSettingsRequest.php`
  - [ ] File validation (JSON, size limits, MIME type)
  - [ ] Security checks for malicious content
  - [ ] User authorization validation
- [ ] Create `app/Http/Requests/Settings/ResetSettingsRequest.php`
  - [ ] Section validation (profile, preferences, ai)
  - [ ] Confirmation token validation
  - [ ] User authorization checks

### Settings Service
- [ ] Create `app/Services/SettingsService.php`
  - [ ] `importSettings(User $user, array $data)` method
  - [ ] `resetSettings(User $user, array $sections)` method
  - [ ] `validateSettingsData(array $data)` schema validation
  - [ ] `getDefaultSettings()` for reset operations
  - [ ] Individual import methods for each section:
    - [ ] `importProfileData($user, $profileData)`
    - [ ] `importPreferences($user, $preferences)`
    - [ ] `importAiSettings($user, $aiSettings)`

### Schema Validation
- [ ] Create `app/Rules/ValidSettingsSchema.php`
  - [ ] JSON structure validation
  - [ ] Field whitelist validation
  - [ ] Data type checking
  - [ ] Enum value validation (theme, language, etc.)

## Frontend Implementation

### Core Components
- [ ] Enhance `resources/js/islands/Settings/components/ImportExportControls.tsx`
  - [ ] Add file picker for import
  - [ ] Wire up import/reset buttons to new dialogs
  - [ ] Handle loading states during operations
  - [ ] Display success/error feedback

### Import Dialog
- [ ] Create `resources/js/islands/Settings/components/ImportDialog.tsx`
  - [ ] File selection interface with drag-and-drop
  - [ ] Settings preview before confirmation
  - [ ] Overwrite options and warnings
  - [ ] Progress indicators for upload/processing
  - [ ] Error handling with clear messages

### Reset Dialog
- [ ] Create `resources/js/islands/Settings/components/ResetDialog.tsx`
  - [ ] Section selection with checkboxes
  - [ ] Clear warning about permanent reset
  - [ ] Confirmation token generation
  - [ ] Progress indicator during reset
  - [ ] Success summary of what was reset

### Custom Hooks
- [ ] Create `resources/js/hooks/useSettingsImport.ts`
  - [ ] File upload handling
  - [ ] Import API integration
  - [ ] Progress and error state management
  - [ ] Success callback handling
- [ ] Create `resources/js/hooks/useSettingsReset.ts`
  - [ ] Reset token generation
  - [ ] Reset API integration
  - [ ] Section selection state
  - [ ] Confirmation flow management

## Security & Validation

### File Security
- [ ] Implement file upload security measures:
  - [ ] MIME type validation (JSON only)
  - [ ] File size limits (1MB max)
  - [ ] Content scanning for malicious payloads
  - [ ] Temporary file cleanup after processing
- [ ] Add malware scanning if available
- [ ] Implement rate limiting for import operations

### Data Validation
- [ ] Implement comprehensive settings validation:
  - [ ] Whitelist allowed setting keys
  - [ ] Validate enum values (theme: light|dark|system)
  - [ ] Range validation for numeric settings
  - [ ] String sanitization and length limits
- [ ] Cross-validate related settings for consistency

### Audit Logging
- [ ] Add audit logging for all operations:
  - [ ] Log successful imports with change summary
  - [ ] Log reset operations with affected sections
  - [ ] Log failed attempts with error details
  - [ ] Include user context and timestamps

## Testing

### Backend Tests
- [ ] Create `tests/Feature/Settings/ImportExportTest.php`
  - [ ] Test successful import with valid JSON
  - [ ] Test import with invalid file types
  - [ ] Test import with malformed JSON
  - [ ] Test import with invalid settings values
  - [ ] Test reset functionality for each section
  - [ ] Test authorization (users can only modify own settings)
  - [ ] Test audit logging functionality

### Frontend Tests
- [ ] Test import dialog interactions:
  - [ ] File selection and preview
  - [ ] Error handling for invalid files
  - [ ] Success flow with proper feedback
- [ ] Test reset dialog interactions:
  - [ ] Section selection
  - [ ] Confirmation flow
  - [ ] Success feedback
- [ ] Test hook functionality and state management

## Error Handling

### Backend Error Responses
- [ ] Comprehensive error responses for:
  - [ ] Invalid file format/content
  - [ ] Settings validation failures
  - [ ] Authorization errors
  - [ ] System errors (disk space, database)
- [ ] Rollback capability for failed imports
- [ ] Graceful degradation for partial failures

### Frontend Error Display
- [ ] User-friendly error messages for:
  - [ ] File upload errors
  - [ ] Import validation failures
  - [ ] Network/server errors
- [ ] Contextual help for resolving common errors
- [ ] Retry mechanisms where appropriate

## Integration & Polish

### API Integration
- [ ] Integrate with existing settings endpoints
- [ ] Ensure compatibility with current export format
- [ ] Test with real user data and edge cases

### UI/UX Polish
- [ ] Consistent styling with existing settings components
- [ ] Smooth transitions and loading states
- [ ] Accessible dialog implementations
- [ ] Mobile-responsive design

### Documentation
- [ ] Update API documentation for new endpoints
- [ ] Add user guide for import/reset functionality
- [ ] Document settings schema for developers

## Success Criteria Checklist
- [ ] Users can import valid JSON settings files
- [ ] Users can reset specific setting sections
- [ ] All operations require proper confirmation
- [ ] File validation prevents security issues
- [ ] Error handling guides users to resolution
- [ ] Audit trail captures all operations
- [ ] Integration tests cover happy path and errors
- [ ] UI provides clear feedback throughout all flows