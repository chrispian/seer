<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ToastService
{
    public const VERBOSITY_MINIMAL = 'minimal';

    public const VERBOSITY_NORMAL = 'normal';

    public const VERBOSITY_VERBOSE = 'verbose';

    public const SEVERITY_SUCCESS = 'success';

    public const SEVERITY_ERROR = 'error';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_INFO = 'info';

    /**
     * Determine if a toast should be shown based on user verbosity preference and severity.
     */
    public function shouldShowToast(string $severity, ?User $user = null): bool
    {
        if (! $user) {
            return true; // Show all toasts for unauthenticated users
        }

        $verbosity = $user->toast_verbosity ?? self::VERBOSITY_NORMAL;

        return match ($verbosity) {
            self::VERBOSITY_MINIMAL => in_array($severity, [self::SEVERITY_ERROR, self::SEVERITY_WARNING]),
            self::VERBOSITY_NORMAL => true, // Show all toasts
            self::VERBOSITY_VERBOSE => true, // Show all toasts
            default => true,
        };
    }

    /**
     * Check if a toast is a duplicate within the suppression window.
     */
    public function isDuplicate(string $severity, string $message, ?User $user = null, int $suppressionWindowSeconds = 10): bool
    {
        // Only suppress success toasts to reduce noise
        if ($severity !== self::SEVERITY_SUCCESS) {
            return false;
        }

        $cacheKey = $this->getToastCacheKey($severity, $message, $user);

        if (Cache::has($cacheKey)) {
            return true; // This is a duplicate
        }

        // Store the toast in cache for the suppression window
        Cache::put($cacheKey, true, $suppressionWindowSeconds);

        return false;
    }

    /**
     * Generate a cache key for toast deduplication.
     */
    private function getToastCacheKey(string $severity, string $message, ?User $user = null): string
    {
        $userId = $user?->id ?? 'guest';

        return "toast_suppress:{$userId}:{$severity}:".md5($message);
    }

    /**
     * Get available verbosity options.
     */
    public static function getVerbosityOptions(): array
    {
        return [
            self::VERBOSITY_MINIMAL => 'Minimal (errors and warnings only)',
            self::VERBOSITY_NORMAL => 'Normal (all notifications)',
            self::VERBOSITY_VERBOSE => 'Verbose (all notifications with details)',
        ];
    }
}
