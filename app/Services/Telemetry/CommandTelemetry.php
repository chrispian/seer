<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;

/**
 * TELEMETRY-004: Command & DSL Execution Metrics
 *
 * Provides comprehensive observability for the command execution system,
 * tracking performance, usage patterns, and errors across the entire pipeline.
 */
class CommandTelemetry
{
    private const LOG_CHANNEL = 'command-telemetry';

    /**
     * Log command execution start
     */
    public static function logCommandStart(string $command, array $arguments, array $context = []): void
    {
        if (! self::isEnabled()) {
            return;
        }

        if (! self::shouldSample('command_execution')) {
            return;
        }

        $sanitizedArguments = self::sanitizeArguments($arguments);

        self::log('command.execution.started', [
            'command' => $command,
            'arguments' => $sanitizedArguments,
            'source_type' => $context['source_type'] ?? 'unknown', // 'hardcoded' or 'dsl'
            'dry_run' => $context['dry_run'] ?? false,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }

    /**
     * Log command execution completion
     */
    public static function logCommandComplete(
        string $command,
        array $arguments,
        float $durationMs,
        bool $success,
        array $metrics = [],
        ?string $error = null
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        if (! self::shouldSample('command_execution')) {
            return;
        }

        $sanitizedArguments = self::sanitizeArguments($arguments);
        $performanceCategory = self::categorizeCommandPerformance($durationMs);

        $logData = [
            'command' => $command,
            'arguments' => $sanitizedArguments,
            'duration_ms' => $durationMs,
            'success' => $success,
            'performance_category' => $performanceCategory,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        if ($metrics) {
            $logData['metrics'] = $metrics;
        }

        if (! $success && $error) {
            $logData['error'] = $error;
            $logData['error_category'] = self::categorizeError($error);
        }

        // Alert on slow commands
        if ($durationMs > config('command-telemetry.performance.alert_thresholds.slow_command', 5000)) {
            self::logPerformanceAlert('slow_command', $command, $durationMs);
        }

        self::log('command.execution.completed', $logData);
    }

    /**
     * Log DSL step execution start
     */
    public static function logStepStart(string $stepType, string $stepId, array $config = []): void
    {
        if (! self::isEnabled()) {
            return;
        }

        if (! self::shouldSample('step_execution')) {
            return;
        }

        $sanitizedConfig = self::sanitizeStepConfig($stepType, $config);

        self::log('command.step.started', [
            'step_type' => $stepType,
            'step_id' => $stepId,
            'config' => $sanitizedConfig,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }

    /**
     * Log DSL step execution completion
     */
    public static function logStepComplete(
        string $stepType,
        string $stepId,
        float $durationMs,
        bool $success,
        array $metrics = [],
        ?string $error = null
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        if (! self::shouldSample('step_execution')) {
            return;
        }

        $performanceCategory = self::categorizeStepPerformance($durationMs);

        $logData = [
            'step_type' => $stepType,
            'step_id' => $stepId,
            'duration_ms' => $durationMs,
            'success' => $success,
            'performance_category' => $performanceCategory,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ];

        if ($metrics) {
            $logData['metrics'] = $metrics;
        }

        if (! $success && $error) {
            $logData['error'] = $error;
            $logData['error_category'] = self::categorizeError($error);
        }

        // Alert on slow steps
        if ($durationMs > config('command-telemetry.performance.alert_thresholds.slow_step', 3000)) {
            self::logPerformanceAlert('slow_step', $stepType, $durationMs, ['step_id' => $stepId]);
        }

        self::log('command.step.completed', $logData);
    }

    /**
     * Log template rendering performance
     */
    public static function logTemplateRendering(
        string $template,
        float $durationMs,
        bool $cacheHit = false,
        array $stats = []
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        if (! self::shouldSample('template_rendering')) {
            return;
        }

        $performanceCategory = self::categorizeTemplatePerformance($durationMs);

        $logData = [
            'template_hash' => md5($template),
            'template_length' => strlen($template),
            'duration_ms' => $durationMs,
            'cache_hit' => $cacheHit,
            'performance_category' => $performanceCategory,
        ];

        if ($stats) {
            $logData['stats'] = $stats;
        }

        // Alert on slow template rendering
        if ($durationMs > config('command-telemetry.performance.alert_thresholds.template_rendering', 500)) {
            self::logPerformanceAlert('slow_template', 'template_rendering', $durationMs, [
                'template_length' => strlen($template),
                'cache_hit' => $cacheHit,
            ]);
        }

        self::log('command.template.rendered', $logData);
    }

    /**
     * Log condition evaluation
     */
    public static function logConditionEvaluation(
        string $condition,
        bool $result,
        float $durationMs,
        ?string $branchExecuted = null
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        if (! self::shouldSample('condition_evaluation')) {
            return;
        }

        self::log('command.condition.evaluated', [
            'condition_hash' => md5($condition),
            'condition_length' => strlen($condition),
            'result' => $result,
            'branch_executed' => $branchExecuted,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Log AI generation metrics
     */
    public static function logAiGeneration(
        int $promptLength,
        float $durationMs,
        bool $success,
        array $metrics = []
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        $logData = [
            'prompt_length' => $promptLength,
            'duration_ms' => $durationMs,
            'success' => $success,
        ];

        if ($metrics) {
            $logData = array_merge($logData, $metrics);
        }

        self::log('command.ai.generation', $logData);
    }

    /**
     * Log command chain execution
     */
    public static function logCommandChain(array $commands, float $totalDurationMs): void
    {
        if (! self::isEnabled()) {
            return;
        }

        self::log('command.chain.executed', [
            'commands' => $commands,
            'total_duration_ms' => $totalDurationMs,
            'command_count' => count($commands),
            'avg_command_duration_ms' => count($commands) > 0 ? round($totalDurationMs / count($commands), 2) : 0,
        ]);
    }

    /**
     * Log performance alert
     */
    public static function logPerformanceAlert(
        string $alertType,
        string $subject,
        float $durationMs,
        array $context = []
    ): void {
        if (! config('command-telemetry.features.performance_alerts', true)) {
            return;
        }

        self::log('command.performance.alert', [
            'alert_type' => $alertType,
            'subject' => $subject,
            'duration_ms' => $durationMs,
            'context' => $context,
        ]);
    }

    /**
     * Log error with categorization
     */
    public static function logError(
        string $context,
        string $error,
        array $additionalData = []
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        self::log('command.error', [
            'context' => $context,
            'error' => $error,
            'error_category' => self::categorizeError($error),
            'additional_data' => $additionalData,
        ]);
    }

    /**
     * Log command popularity and usage patterns
     */
    public static function logUsagePattern(string $command, array $pattern): void
    {
        if (! config('command-telemetry.metrics.usage_patterns', true)) {
            return;
        }

        self::log('command.usage.pattern', [
            'command' => $command,
            'pattern' => $pattern,
        ]);
    }

    /**
     * Core logging method with correlation context and enrichment
     */
    protected static function log(string $event, array $data): void
    {
        $logData = [
            'event' => $event,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ],
        ];

        // Add correlation context if available
        if (CorrelationContext::hasContext()) {
            $logData['correlation'] = CorrelationContext::forLogging();
        }

        // Add request ID if available
        if (config('command-telemetry.context.include_request_id', true) && request()?->header('X-Request-ID')) {
            $logData['meta']['request_id'] = request()->header('X-Request-ID');
        }

        // Add git commit if configured
        if (config('command-telemetry.context.include_git_commit', false)) {
            $logData['meta']['git_commit'] = env('GIT_COMMIT');
        }

        Log::channel(self::LOG_CHANNEL)->info($event, $logData);
    }

    /**
     * Check if telemetry is enabled
     */
    protected static function isEnabled(): bool
    {
        return config('command-telemetry.enabled', true);
    }

    /**
     * Check if we should sample this event type
     */
    protected static function shouldSample(string $eventType): bool
    {
        $rate = config("command-telemetry.sampling.{$eventType}", 1.0);

        return mt_rand() / mt_getrandmax() <= $rate;
    }

    /**
     * Sanitize command arguments to remove sensitive data
     */
    protected static function sanitizeArguments(array $arguments): array
    {
        $sensitivePatterns = config('command-telemetry.sanitization.sensitive_patterns', []);
        $maxLength = config('command-telemetry.sanitization.max_argument_length', 500);
        $hashSensitive = config('command-telemetry.sanitization.hash_sensitive_values', true);

        $sanitized = [];
        foreach ($arguments as $key => $value) {
            $isSensitive = false;

            // Check if key matches sensitive patterns
            foreach ($sensitivePatterns as $pattern) {
                if (preg_match($pattern, $key)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = $hashSensitive ? '[HASH:'.substr(md5((string) $value), 0, 8).']' : '[REDACTED]';
            } else {
                $stringValue = is_string($value) ? $value : json_encode($value);
                $sanitized[$key] = strlen($stringValue) > $maxLength
                    ? substr($stringValue, 0, $maxLength).'...'
                    : $stringValue;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize step configuration based on step type
     */
    protected static function sanitizeStepConfig(string $stepType, array $config): array
    {
        $sensitized = self::sanitizeArguments($config);

        // Step-specific sanitization
        switch ($stepType) {
            case 'ai.generate':
                if (isset($sensitized['prompt']) && strlen($sensitized['prompt']) > 200) {
                    $sensitized['prompt'] = substr($sensitized['prompt'], 0, 200).'...';
                }
                break;

            case 'database.update':
                // Never log actual SQL queries, just metadata
                if (isset($sensitized['query'])) {
                    $sensitized['query'] = '[SQL_QUERY_HASH:'.substr(md5($sensitized['query']), 0, 8).']';
                }
                break;
        }

        return $sensitized;
    }

    /**
     * Categorize command performance
     */
    protected static function categorizeCommandPerformance(float $durationMs): string
    {
        $thresholds = config('command-telemetry.performance.command_thresholds', [
            'fast' => 100,
            'normal' => 500,
            'slow' => 2000,
            'very_slow' => 5000,
        ]);

        if ($durationMs < $thresholds['fast']) {
            return 'fast';
        }
        if ($durationMs < $thresholds['normal']) {
            return 'normal';
        }
        if ($durationMs < $thresholds['slow']) {
            return 'slow';
        }
        if ($durationMs < $thresholds['very_slow']) {
            return 'very_slow';
        }

        return 'critical';
    }

    /**
     * Categorize step performance
     */
    protected static function categorizeStepPerformance(float $durationMs): string
    {
        $thresholds = config('command-telemetry.performance.step_thresholds', [
            'fast' => 50,
            'normal' => 200,
            'slow' => 1000,
            'very_slow' => 3000,
        ]);

        if ($durationMs < $thresholds['fast']) {
            return 'fast';
        }
        if ($durationMs < $thresholds['normal']) {
            return 'normal';
        }
        if ($durationMs < $thresholds['slow']) {
            return 'slow';
        }
        if ($durationMs < $thresholds['very_slow']) {
            return 'very_slow';
        }

        return 'critical';
    }

    /**
     * Categorize template performance
     */
    protected static function categorizeTemplatePerformance(float $durationMs): string
    {
        $thresholds = config('command-telemetry.performance.template_thresholds', [
            'fast' => 10,
            'normal' => 50,
            'slow' => 200,
        ]);

        if ($durationMs < $thresholds['fast']) {
            return 'fast';
        }
        if ($durationMs < $thresholds['normal']) {
            return 'normal';
        }
        if ($durationMs < $thresholds['slow']) {
            return 'slow';
        }

        return 'very_slow';
    }

    /**
     * Categorize errors for better analytics
     */
    protected static function categorizeError(string $error): string
    {
        $error = strtolower($error);

        if (str_contains($error, 'timeout') || str_contains($error, 'timed out')) {
            return 'timeout';
        }

        if (str_contains($error, 'memory') || str_contains($error, 'out of memory')) {
            return 'memory';
        }

        if (str_contains($error, 'permission') || str_contains($error, 'forbidden') || str_contains($error, 'unauthorized')) {
            return 'permission';
        }

        if (str_contains($error, 'not found') || str_contains($error, 'missing')) {
            return 'not_found';
        }

        if (str_contains($error, 'invalid') || str_contains($error, 'malformed') || str_contains($error, 'parse')) {
            return 'validation';
        }

        if (str_contains($error, 'connection') || str_contains($error, 'network') || str_contains($error, 'socket')) {
            return 'network';
        }

        if (str_contains($error, 'database') || str_contains($error, 'sql') || str_contains($error, 'query')) {
            return 'database';
        }

        return 'general';
    }
}
