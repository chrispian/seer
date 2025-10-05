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
