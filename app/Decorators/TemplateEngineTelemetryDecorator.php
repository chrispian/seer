<?php

namespace App\Decorators;

use App\Services\Commands\DSL\TemplateEngine;
use App\Services\Telemetry\CommandTelemetry;

/**
 * Decorator for TemplateEngine that adds telemetry tracking
 * for template rendering performance and cache efficiency.
 */
class TemplateEngineTelemetryDecorator
{
    public function __construct(
        protected TemplateEngine $templateEngine
    ) {}

    /**
     * Render template with telemetry tracking
     */
    public function render(string $template, array $context): string
    {
        if (! config('command-telemetry.features.template_performance_tracking', true)) {
            return $this->templateEngine->render($template, $context);
        }

        $startTime = microtime(true);
        $cacheStatsBefore = TemplateEngine::getCacheStats();

        try {
            $result = $this->templateEngine->render($template, $context);

            $duration = (microtime(true) - $startTime) * 1000;
            $cacheStatsAfter = TemplateEngine::getCacheStats();

            // Determine if this was a cache hit
            $cacheHit = $cacheStatsAfter['hits'] > $cacheStatsBefore['hits'];

            CommandTelemetry::logTemplateRendering($template, $duration, $cacheHit, [
                'context_keys' => array_keys($context),
                'context_depth' => $this->calculateContextDepth($context),
                'has_control_structures' => str_contains($template, '{%'),
                'has_variables' => str_contains($template, '{{'),
                'cache_stats' => $cacheStatsAfter,
            ]);

            return $result;

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            CommandTelemetry::logError('template_rendering', $e->getMessage(), [
                'template_length' => strlen($template),
                'template_hash' => md5($template),
                'context_keys' => array_keys($context),
                'duration_ms' => $duration,
            ]);

            throw $e;
        }
    }

    /**
     * Calculate the depth of the context array for complexity metrics
     */
    protected function calculateContextDepth(array $context, int $currentDepth = 1): int
    {
        $maxDepth = $currentDepth;

        foreach ($context as $value) {
            if (is_array($value)) {
                $depth = $this->calculateContextDepth($value, $currentDepth + 1);
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }

    /**
     * Get cache statistics (delegate to wrapped engine)
     */
    public static function getCacheStats(): array
    {
        return TemplateEngine::getCacheStats();
    }

    /**
     * Delegate all other method calls to the wrapped engine
     */
    public function __call(string $method, array $arguments)
    {
        return $this->templateEngine->$method(...$arguments);
    }

    /**
     * Static factory method to wrap TemplateEngine with telemetry
     */
    public static function wrap(TemplateEngine $templateEngine): self
    {
        return new self($templateEngine);
    }
}
