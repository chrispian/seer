# Context: NativePHP Packaging & Testing

## Current Challenge
- NativePHP desktop builds need embedded SQLite vector search
- sqlite-vec extension must be bundled and loaded automatically
- Cross-platform compatibility required (Windows/macOS/Linux)
- Current vector system requires external PostgreSQL+pgvector

## Target Solution
- sqlite-vec extension bundled with NativePHP builds
- Automatic extension loading in desktop environment
- Fallback to text-only search if extension fails to load
- Complete vector search functionality in standalone desktop app

## Implementation Requirements
- Bundle sqlite-vec extension for each platform
- Configure automatic extension loading in NativePHP context
- Test vector functionality in packaged desktop app
- Document deployment and troubleshooting procedures
- Validate performance in desktop environment

## Success Criteria
- Desktop app loads with vector search enabled
- Search functionality works offline
- Cross-platform builds working
- Performance acceptable for desktop usage
- Comprehensive deployment documentation
