# UX-03-04 Avatar System Agent Profile

## Mission
Implement comprehensive avatar management system with Gravatar integration, custom file uploads, and local caching optimized for NativePHP desktop environment.

## Workflow
- Create GravatarPreview component with real-time email-based preview
- Implement AvatarUpload component with drag-and-drop functionality
- Add image processing capabilities (cropping, resizing, validation)
- Implement local caching system for offline Gravatar access
- Integrate avatar components with setup wizard and settings

## Quality Standards
- Real-time Gravatar preview updates as user types email
- Secure file upload handling with comprehensive validation
- Professional image cropping and resizing interface
- Reliable local caching for offline functionality
- Fallback handling when Gravatar or uploads fail
- Optimized performance for desktop environment

## Deliverables
- `GravatarPreview.tsx` - Real-time Gravatar preview component
- `AvatarUpload.tsx` - Drag-and-drop file upload component
- `hooks/useGravatarPreview.ts` - Gravatar preview logic
- `hooks/useAvatarUpload.ts` - File upload management
- Image processing utilities for cropping and resizing
- Local caching system for Gravatar images

## Avatar System Features
- **Gravatar Integration**: Real-time preview based on email input
- **Custom Upload**: Drag-and-drop with image validation
- **Image Processing**: Crop, resize, and optimize uploaded images
- **Local Caching**: Store Gravatar images for offline access
- **Fallback System**: Default avatars when other options fail
- **Format Support**: JPG, PNG, WebP with size validation

## Required Shadcn Components
- `avatar` - Avatar display component
- `button` - Upload triggers and actions
- `card` - Avatar option containers
- `switch` - Toggle between Gravatar and upload
- File upload utilities

## Safety Notes
- Validate uploaded files for type, size, and content security
- Implement proper file storage outside web-accessible directories
- Sanitize file names and prevent path traversal attacks
- Rate limit Gravatar API requests to prevent abuse
- Handle large file uploads gracefully with progress indication

## Communication
- Report avatar system development progress and component integration
- Include screenshots of avatar selection and upload interfaces
- Document image processing capabilities and file validation rules
- Provide testing results for various image formats and sizes