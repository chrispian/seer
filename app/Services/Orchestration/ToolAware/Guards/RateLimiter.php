<?php

namespace App\Services\Orchestration\ToolAware\Guards;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimiter
{
    protected int $maxAttemptsPerMinute = 60;
    protected int $maxAttemptsPerHour = 300;

    /**
     * Check if user/agent can make another tool call
     *
     * @param string $identifier User ID, agent ID, or IP
     * @param string $toolId Specific tool being called
     * @return bool True if allowed
     */
    public function allow(string $identifier, string $toolId): bool
    {
        $minuteKey = "rate_limit:tool:{$identifier}:minute";
        $hourKey = "rate_limit:tool:{$identifier}:hour";
        $toolKey = "rate_limit:tool:{$identifier}:{$toolId}:minute";

        // Check per-minute global limit
        $minuteCount = (int) Cache::get($minuteKey, 0);
        if ($minuteCount >= $this->maxAttemptsPerMinute) {
            Log::warning('Rate limit exceeded (per minute)', [
                'identifier' => $identifier,
                'count' => $minuteCount,
                'limit' => $this->maxAttemptsPerMinute,
            ]);
            return false;
        }

        // Check per-hour global limit
        $hourCount = (int) Cache::get($hourKey, 0);
        if ($hourCount >= $this->maxAttemptsPerHour) {
            Log::warning('Rate limit exceeded (per hour)', [
                'identifier' => $identifier,
                'count' => $hourCount,
                'limit' => $this->maxAttemptsPerHour,
            ]);
            return false;
        }

        // Check per-tool per-minute limit (prevent hammering single tool)
        $toolCount = (int) Cache::get($toolKey, 0);
        if ($toolCount >= 10) {
            Log::warning('Rate limit exceeded (per tool)', [
                'identifier' => $identifier,
                'tool_id' => $toolId,
                'count' => $toolCount,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Record a tool call attempt
     */
    public function hit(string $identifier, string $toolId): void
    {
        $minuteKey = "rate_limit:tool:{$identifier}:minute";
        $hourKey = "rate_limit:tool:{$identifier}:hour";
        $toolKey = "rate_limit:tool:{$identifier}:{$toolId}:minute";

        // Increment counters with TTL
        Cache::increment($minuteKey);
        Cache::expire($minuteKey, 60);

        Cache::increment($hourKey);
        Cache::expire($hourKey, 3600);

        Cache::increment($toolKey);
        Cache::expire($toolKey, 60);
    }

    /**
     * Calculate backoff time in seconds
     */
    public function backoffTime(int $attemptNumber): int
    {
        // Exponential backoff: 2^attempt seconds, max 60 seconds
        return min(pow(2, $attemptNumber), 60);
    }

    /**
     * Check if should retry based on error code
     */
    public function shouldRetry(int $statusCode, int $attemptNumber, int $maxRetries = 3): bool
    {
        if ($attemptNumber >= $maxRetries) {
            return false;
        }

        // Retry on rate limit, server errors
        $retryableCodes = [429, 500, 502, 503, 504];
        
        return in_array($statusCode, $retryableCodes, true);
    }
}
