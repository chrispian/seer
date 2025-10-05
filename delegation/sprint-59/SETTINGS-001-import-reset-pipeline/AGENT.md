# SETTINGS-001: Import/Reset Settings Pipeline

## Role
You are a Laravel + React developer implementing complete settings import/export/reset functionality to finish the settings management card.

## Context
The `/settings` page currently has export, import, and reset controls visible in the UI, but only export is wired. Import and reset need backend endpoints, client flows, and comprehensive error handling to complete the settings management experience.

## Current State
- Export functionality works via existing endpoint
- Import/reset buttons render but have no functionality
- Settings persist via `/settings/preferences` endpoint for most data
- Profile data uses `/settings/profile` endpoint
- AI configuration stored under `profile_settings.ai`

## Task Scope
Implement complete import/reset pipelines with:

### Backend Requirements
- `POST /api/settings/import` endpoint with file validation
- `POST /api/settings/reset` endpoint with confirmation token
- Support JSON settings format from export
- Validate imported settings against current schema
- Preserve required fields during reset operations
- Audit logging for import/reset actions

### Frontend Requirements
- File picker dialog for settings import
- Confirmation dialogs for both import and reset
- Progress indicators during operations
- Error handling with clear user feedback
- Success states with summary of changes
- Rollback capability if import fails

### Security & Validation
- File type validation (JSON only)
- Settings schema validation
- User authorization checks
- Sanitization of imported values
- Protection against malicious payloads

## Success Criteria
- [ ] Import flow accepts valid JSON files and updates settings
- [ ] Reset flow restores default values with confirmation
- [ ] Error cases handled gracefully with user feedback
- [ ] Settings validation prevents invalid configurations
- [ ] Audit trail captures import/reset operations
- [ ] UI provides clear feedback during all operations
- [ ] Integration tests cover happy path and error scenarios

## Technical Constraints
- Must preserve existing settings structure
- Coordinate with existing `/settings/preferences` and `/settings/profile` endpoints
- Follow Laravel validation patterns
- Use React patterns consistent with other settings sections
- Ensure compatibility with current user preference schema