#!/bin/bash

# Package Vector Extensions for NativePHP Desktop Deployment
# This script downloads and packages the sqlite-vec extension for cross-platform distribution

set -e

echo "Packaging vector extensions for desktop deployment..."

# Configuration
EXTENSIONS_DIR="storage/extensions"
SQLITE_VEC_VERSION="v0.1.1"  # Update as needed
BASE_URL="https://github.com/asg017/sqlite-vec/releases/download"

# Create extensions directory
mkdir -p "$EXTENSIONS_DIR"
cd "$EXTENSIONS_DIR"

# Function to download and extract extension for a platform
download_extension() {
    local platform=$1
    local arch=$2
    local extension=$3
    local url="${BASE_URL}/${SQLITE_VEC_VERSION}/sqlite-vec-${platform}-${arch}.tar.gz"
    
    echo "Downloading sqlite-vec for ${platform}-${arch}..."
    
    if curl -L --fail "$url" -o "sqlite-vec-${platform}-${arch}.tar.gz" 2>/dev/null; then
        tar -xzf "sqlite-vec-${platform}-${arch}.tar.gz"
        
        # Move extension to standard location
        if [ -f "vec${extension}" ]; then
            mv "vec${extension}" "vec-${platform}-${arch}${extension}"
            echo "✓ Downloaded vec-${platform}-${arch}${extension}"
        fi
        
        # Cleanup
        rm -f "sqlite-vec-${platform}-${arch}.tar.gz"
    else
        echo "⚠ Failed to download sqlite-vec for ${platform}-${arch}"
    fi
}

# Download extensions for common platforms
echo "Downloading sqlite-vec extensions..."

# macOS
download_extension "macos" "aarch64" ".dylib"
download_extension "macos" "x86_64" ".dylib"

# Linux  
download_extension "linux" "x86_64" ".so"
download_extension "linux" "aarch64" ".so"

# Windows
download_extension "windows" "x86_64" ".dll"

# Create platform detection script
cat > "../detect-extension.php" << 'EOF'
<?php
/**
 * Detect and return the appropriate sqlite-vec extension path
 * for the current platform and architecture.
 */

function detectSqliteVecExtension(): ?string
{
    $platform = PHP_OS_FAMILY;
    $arch = php_uname('m');
    
    // Normalize architecture names
    $archMap = [
        'x86_64' => 'x86_64',
        'aarch64' => 'aarch64',
        'arm64' => 'aarch64',
        'AMD64' => 'x86_64',
    ];
    
    $normalizedArch = $archMap[$arch] ?? $arch;
    
    // Map platform names to file extensions
    $platformMap = [
        'Darwin' => 'macos',
        'Linux' => 'linux', 
        'Windows' => 'windows',
    ];
    
    $normalizedPlatform = $platformMap[$platform] ?? strtolower($platform);
    
    // Map to file extensions
    $extensionMap = [
        'macos' => '.dylib',
        'linux' => '.so',
        'windows' => '.dll',
    ];
    
    $extension = $extensionMap[$normalizedPlatform] ?? '.so';
    
    $extensionPath = __DIR__ . "/extensions/vec-{$normalizedPlatform}-{$normalizedArch}{$extension}";
    
    if (file_exists($extensionPath)) {
        return $extensionPath;
    }
    
    // Fallback: try common naming patterns
    $fallbacks = [
        __DIR__ . "/extensions/vec{$extension}",
        __DIR__ . "/extensions/sqlite-vec{$extension}",
    ];
    
    foreach ($fallbacks as $fallback) {
        if (file_exists($fallback)) {
            return $fallback;
        }
    }
    
    return null;
}

// Usage example:
if (basename($_SERVER['SCRIPT_NAME']) === 'detect-extension.php') {
    $extensionPath = detectSqliteVecExtension();
    if ($extensionPath) {
        echo "Extension found: {$extensionPath}\n";
    } else {
        echo "No sqlite-vec extension found for this platform\n";
    }
}
EOF

# Create NativePHP configuration template
cat > "../nativephp.env" << 'EOF'
# NativePHP Desktop Configuration Template
# Copy to .env and customize for your deployment

# Database Configuration
DB_CONNECTION=sqlite
DB_DATABASE=database/fragments.sqlite
DB_FOREIGN_KEYS=true

# Vector Store Configuration  
VECTOR_STORE_DRIVER=auto
SQLITE_VEC_AUTO_LOAD=true
SQLITE_VEC_EXTENSION_PATH=storage/extensions/auto-detect

# Hybrid Search Optimization for Desktop
HYBRID_SEARCH_VECTOR_WEIGHT=0.6
HYBRID_SEARCH_TEXT_WEIGHT=0.4
HYBRID_SEARCH_MAX_RESULTS=25
HYBRID_SEARCH_ENABLE_FALLBACK=true

# Performance Settings for Desktop
VECTOR_BATCH_SIZE=50
VECTOR_ENABLE_CONCURRENT=false
VECTOR_MAX_CONCURRENT=2
VECTOR_QUERY_TIMEOUT=15
VECTOR_ENABLE_QUERY_CACHE=true
VECTOR_QUERY_CACHE_TTL=600

# Capability Detection
VECTOR_CACHE_CAPABILITIES=true
VECTOR_CAPABILITIES_CACHE_TTL=1800
VECTOR_RETRY_DETECTION=true

# SQLite Optimization
SQLITE_FTS5_ENABLED=true
SQLITE_FTS5_TOKENIZER=porter

# Logging (disable in production)
VECTOR_LOG_QUERIES=false
VECTOR_LOG_PERFORMANCE=false
VECTOR_ENABLE_DIAGNOSTICS=true

# App-specific
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=single
LOG_LEVEL=warning
EOF

# Create deployment readme
cat > "../DEPLOYMENT_README.md" << 'EOF'
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
EOF

echo ""
echo "✓ Vector extensions packaging complete!"
echo ""
echo "Files created:"
echo "  - storage/extensions/ (platform-specific extensions)"
echo "  - storage/detect-extension.php (platform detection)"
echo "  - storage/nativephp.env (configuration template)"
echo "  - storage/DEPLOYMENT_README.md (deployment guide)"
echo ""
echo "Next steps:"
echo "  1. Test extension detection: php storage/detect-extension.php"
echo "  2. Copy storage/nativephp.env to .env and customize"
echo "  3. Verify deployment: php artisan vector:status --detailed"
EOF