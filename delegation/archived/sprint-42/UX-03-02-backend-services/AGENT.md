# UX-03-02 Backend Services Agent Profile

## Mission
Develop robust backend services for avatar management, user profile operations, and API endpoints to support the setup wizard and settings functionality.

## Workflow
- Create AvatarService for Gravatar integration and file uploads
- Implement UserProfileService for profile management operations
- Build API controllers (SetupController, UserController)
- Add comprehensive file upload validation and security
- Create API routes with proper middleware and validation

## Quality Standards
- Services follow Laravel best practices and dependency injection
- Comprehensive error handling and validation
- Secure file upload handling with type and size restrictions
- Gravatar integration with proper caching and fallback mechanisms
- API endpoints follow RESTful conventions with proper HTTP status codes

## Deliverables
- `app/Services/AvatarService.php` - Avatar and Gravatar management
- `app/Services/UserProfileService.php` - Profile operations
- `app/Http/Controllers/SetupController.php` - Setup wizard APIs
- `app/Http/Controllers/UserController.php` - User settings APIs
- API routes in `routes/api.php`
- File upload validation and security measures

## Service Architecture
```php
AvatarService:
- getGravatarUrl(string $email, int $size = 200): string
- downloadAndCacheGravatar(string $email): string|null
- getUserAvatarUrl(User $user): string
- uploadCustomAvatar(UploadedFile $file, User $user): string

UserProfileService:
- createDefaultUser(array $profileData): User
- updateUserProfile(User $user, array $profileData): User
- completeProfileSetup(User $user): void
- isProfileComplete(User $user): bool
```

## Safety Notes
- Validate all file uploads for security (type, size, content)
- Sanitize user input and implement proper validation rules
- Use secure file storage locations outside web root
- Implement rate limiting for Gravatar requests
- Ensure proper authorization for all API endpoints

## Communication
- Report service implementation progress and API endpoint creation
- Document security measures implemented for file uploads
- Provide API documentation with request/response examples
- Highlight any integration considerations with existing codebase