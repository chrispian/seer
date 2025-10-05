<?php

namespace App\Services\Telemetry;

class CorrelationContext
{
    private static ?string $correlationId = null;

    private static array $context = [];

    /**
     * Set the correlation ID for the current request
     */
    public static function set(string $correlationId): void
    {
        self::$correlationId = $correlationId;
        self::$context = ['correlation_id' => $correlationId];
    }

    /**
     * Get the current correlation ID
     */
    public static function get(): ?string
    {
        return self::$correlationId;
    }

    /**
     * Add additional context to the correlation
     */
    public static function addContext(string $key, mixed $value): void
    {
        self::$context[$key] = $value;
    }

    /**
     * Get the full correlation context
     */
    public static function getContext(): array
    {
        return self::$context;
    }

    /**
     * Clear the correlation context (typically called between requests)
     */
    public static function clear(): void
    {
        self::$correlationId = null;
        self::$context = [];
    }

    /**
     * Get correlation context formatted for logging
     */
    public static function forLogging(): array
    {
        return [
            'correlation_id' => self::$correlationId,
            'timestamp' => now()->toISOString(),
            'context' => self::$context,
        ];
    }

    /**
     * Check if we have an active correlation context
     */
    public static function hasContext(): bool
    {
        return self::$correlationId !== null;
    }
}
