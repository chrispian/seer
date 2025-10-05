# UX-03-01 Database Schema TODO

## Preparation
- [ ] Review current users table structure and existing data
- [ ] Analyze existing User model methods and relationships
- [ ] Plan migration strategy to preserve existing user data
- [ ] Create feature branch: `feature/ux-03-01-database-schema`

## Migration Creation
- [ ] Create migration: `php artisan make:migration add_profile_fields_to_users_table`
- [ ] Add `display_name` column (string, nullable, after 'name')
- [ ] Add `avatar_path` column (string, nullable, after 'email')
- [ ] Add `use_gravatar` column (boolean, default true, after 'avatar_path')
- [ ] Add `profile_settings` column (json, nullable, after 'use_gravatar')
- [ ] Add `profile_completed_at` column (timestamp, nullable, after 'profile_settings')

## User Model Enhancement
- [ ] Add new fields to fillable array
- [ ] Implement profile_settings JSON casting
- [ ] Add profile completion check method: `isProfileComplete()`
- [ ] Add avatar URL accessor: `getAvatarUrlAttribute()`
- [ ] Add display name accessor with fallback to name
- [ ] Add profile settings accessor with default values

## Validation Rules
- [ ] Create validation rules for display_name (max 50 chars, alphanumeric + spaces)
- [ ] Add avatar_path validation (valid file path format)
- [ ] Implement profile_settings JSON schema validation
- [ ] Add email format validation for Gravatar compatibility

## Profile Settings Schema
- [ ] Define default preferences structure
- [ ] Create settings for default_ai_provider
- [ ] Add notification preferences (toast_duration, show_success, show_errors)
- [ ] Include theme preferences (system, light, dark)
- [ ] Add onboarding tracking (completed_steps, version)

## Database Testing
- [ ] Test migration on fresh database
- [ ] Test migration with existing user data
- [ ] Verify rollback functionality works correctly
- [ ] Test JSON validation for profile_settings
- [ ] Confirm proper indexing and performance

## Data Integrity
- [ ] Ensure existing users get proper default values
- [ ] Test constraint validation for new fields
- [ ] Verify foreign key relationships remain intact
- [ ] Test database performance with new columns

## Documentation
- [ ] Document profile_settings JSON schema
- [ ] Create migration notes and considerations
- [ ] Document User model method additions
- [ ] Update database schema documentation