# UX-03-02 Backend Services TODO

## Preparation
- [ ] Review current Laravel service architecture
- [ ] Plan service layer integration with existing codebase
- [ ] Research Gravatar API and caching strategies
- [ ] Create feature branch: `feature/ux-03-02-backend-services`

## AvatarService Implementation
- [ ] Create `app/Services/AvatarService.php`
- [ ] Implement `getGravatarUrl(string $email, int $size = 200): string`
- [ ] Implement `downloadAndCacheGravatar(string $email): string|null`
- [ ] Implement `getUserAvatarUrl(User $user): string`
- [ ] Implement `uploadCustomAvatar(UploadedFile $file, User $user): string`
- [ ] Add Gravatar caching directory creation and management
- [ ] Implement avatar file cleanup and rotation

## UserProfileService Implementation
- [ ] Create `app/Services/UserProfileService.php`
- [ ] Implement `createDefaultUser(array $profileData): User`
- [ ] Implement `updateUserProfile(User $user, array $profileData): User`
- [ ] Implement `completeProfileSetup(User $user): void`
- [ ] Implement `isProfileComplete(User $user): bool`
- [ ] Add profile validation and sanitization methods

## File Upload Security
- [ ] Implement file type validation (jpg, png, webp only)
- [ ] Add file size limits (max 5MB for avatars)
- [ ] Create secure file storage outside web root
- [ ] Implement file content validation (not just extension)
- [ ] Add malware scanning for uploaded files
- [ ] Create unique filename generation to prevent conflicts

## API Controllers
- [ ] Create `app/Http/Controllers/SetupController.php`
- [ ] Implement `createProfile` endpoint for wizard
- [ ] Implement `uploadAvatar` endpoint for file uploads
- [ ] Implement `completeSetup` endpoint for finalization
- [ ] Create `app/Http/Controllers/UserController.php`
- [ ] Implement `getProfile` endpoint for settings page
- [ ] Implement `updateProfile` endpoint for profile updates
- [ ] Implement `updateAvatar` endpoint for avatar changes

## API Routes and Middleware
- [ ] Add setup routes to `routes/api.php`
- [ ] Add user profile routes with authentication middleware
- [ ] Implement rate limiting for avatar upload endpoints
- [ ] Add CSRF protection for form submissions
- [ ] Create API resource classes for clean responses

## Gravatar Integration
- [ ] Implement Gravatar URL generation with fallbacks
- [ ] Create local caching system for Gravatar images
- [ ] Add cache invalidation and refresh mechanisms
- [ ] Implement graceful fallback when Gravatar is unavailable
- [ ] Add cache cleanup for old Gravatar images

## Error Handling and Validation
- [ ] Create comprehensive validation rules for all endpoints
- [ ] Implement proper error responses with helpful messages
- [ ] Add logging for service operations and errors
- [ ] Create exception handling for file operations
- [ ] Implement graceful degradation for service failures

## Testing and Integration
- [ ] Test AvatarService with various email addresses
- [ ] Test file upload with different file types and sizes
- [ ] Validate API endpoints with Postman or similar tool
- [ ] Test Gravatar caching and fallback mechanisms
- [ ] Integration test with existing User model

## Documentation
- [ ] Document AvatarService API and usage
- [ ] Document UserProfileService methods
- [ ] Create API endpoint documentation
- [ ] Document file upload security measures