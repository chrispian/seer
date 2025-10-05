# Vector Extensions for Desktop Deployment

This directory contains sqlite-vec extensions for cross-platform desktop deployment with NativePHP.

## Files

- `extensions/` - Platform-specific sqlite-vec extensions
- `detect-extension.php` - Automatic platform detection
- `nativephp.env` - Configuration template for desktop apps

## Usage

1. Copy `nativephp.env` to `.env`
2. Customize settings for your deployment
3. The application will automatically detect and load the appropriate extension

## Platform Support

- macOS (Intel & Apple Silicon)
- Linux (x86_64 & ARM64)  
- Windows (x86_64)

## Fallback Behavior

If sqlite-vec is not available, the application falls back to:
1. SQLite FTS5 for text search
2. Basic LIKE queries as final fallback

## Testing

Verify your deployment with:
```bash
php artisan vector:status --detailed
php artisan vector:config validate
```
