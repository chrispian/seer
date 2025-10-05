<?php

namespace App\Services\Commands\DSL\Steps;

use App\Services\Commands\DSL\TemplateEngine;

abstract class UtilityStep extends Step
{
    public function __construct(
        protected TemplateEngine $templateEngine
    ) {}

    /**
     * Validate common utility step configuration
     */
    protected function validateConfig(array $config): void
    {
        $withConfig = $config['with'] ?? $config;

        if (empty($withConfig)) {
            throw new \InvalidArgumentException('Utility step requires configuration in "with" block');
        }
    }

    /**
     * Render templates in data structure recursively
     */
    protected function renderTemplates(array $data, array $context): array
    {
        $rendered = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $rendered[$key] = $this->templateEngine->render($value, $context);
            } elseif (is_array($value)) {
                $rendered[$key] = $this->renderTemplates($value, $context);
            } else {
                $rendered[$key] = $value;
            }
        }

        return $rendered;
    }

    /**
     * Apply transformation pipeline to a value
     */
    protected function applyTransforms(mixed $value, array $transforms): mixed
    {
        foreach ($transforms as $transform) {
            $value = $this->applyTransform($value, $transform);
        }

        return $value;
    }

    /**
     * Apply a single transformation to a value
     */
    protected function applyTransform(mixed $value, string|array $transform): mixed
    {
        if (is_string($transform)) {
            // Simple transform like "uppercase", "lowercase", "trim"
            return $this->applySimpleTransform($value, $transform);
        }

        if (is_array($transform)) {
            // Complex transform with parameters
            foreach ($transform as $transformName => $params) {
                $value = $this->applyComplexTransform($value, $transformName, $params);
            }
        }

        return $value;
    }

    /**
     * Apply simple string transformations
     */
    protected function applySimpleTransform(mixed $value, string $transform): mixed
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return $value;
        }

        $stringValue = (string) $value;

        return match ($transform) {
            'uppercase' => strtoupper($stringValue),
            'lowercase' => strtolower($stringValue),
            'trim' => trim($stringValue),
            'title_case' => ucwords($stringValue),
            'capitalize' => ucfirst($stringValue),
            'slug' => \Str::slug($stringValue),
            default => $value
        };
    }

    /**
     * Apply complex transformations with parameters
     */
    protected function applyComplexTransform(mixed $value, string $transform, mixed $params): mixed
    {
        return match ($transform) {
            'truncate' => $this->truncateValue($value, $params),
            'substring' => $this->substringValue($value, $params),
            'replace' => $this->replaceValue($value, $params),
            'format' => $this->formatValue($value, $params),
            'cast' => $this->castValue($value, $params),
            default => $value
        };
    }

    /**
     * Truncate string value
     */
    protected function truncateValue(mixed $value, mixed $params): string
    {
        $string = (string) $value;
        $length = is_numeric($params) ? (int) $params : 100;
        $suffix = '...';

        if (is_array($params)) {
            $length = $params['length'] ?? 100;
            $suffix = $params['suffix'] ?? '...';
        }

        return strlen($string) > $length ? substr($string, 0, $length).$suffix : $string;
    }

    /**
     * Extract substring
     */
    protected function substringValue(mixed $value, mixed $params): string
    {
        $string = (string) $value;
        $start = 0;
        $length = null;

        if (is_numeric($params)) {
            $start = (int) $params;
        } elseif (is_array($params)) {
            $start = $params['start'] ?? 0;
            $length = $params['length'] ?? null;
        }

        return $length ? substr($string, $start, $length) : substr($string, $start);
    }

    /**
     * Replace string values
     */
    protected function replaceValue(mixed $value, mixed $params): string
    {
        $string = (string) $value;

        if (is_array($params) && isset($params['search']) && isset($params['replace'])) {
            return str_replace($params['search'], $params['replace'], $string);
        }

        return $string;
    }

    /**
     * Format value with template
     */
    protected function formatValue(mixed $value, mixed $params): string
    {
        if (is_string($params)) {
            // Simple template like "Value: {value}"
            return str_replace('{value}', (string) $value, $params);
        }

        return (string) $value;
    }

    /**
     * Cast value to different type
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'string' => (string) $value,
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'array' => is_array($value) ? $value : [$value],
            'json' => json_encode($value),
            default => $value
        };
    }

    /**
     * Deep merge arrays with conflict resolution
     */
    protected function deepMerge(array $target, array $source, string $strategy = 'right_wins'): array
    {
        foreach ($source as $key => $value) {
            if (array_key_exists($key, $target)) {
                if (is_array($target[$key]) && is_array($value)) {
                    // Both are arrays, merge recursively
                    $target[$key] = $this->deepMerge($target[$key], $value, $strategy);
                } else {
                    // Handle conflicts based on strategy
                    $target[$key] = match ($strategy) {
                        'left_wins' => $target[$key],
                        'right_wins' => $value,
                        'merge_arrays' => is_array($target[$key]) ?
                            array_merge((array) $target[$key], (array) $value) : $value,
                        default => $value
                    };
                }
            } else {
                $target[$key] = $value;
            }
        }

        return $target;
    }

    /**
     * Validate data type
     */
    protected function validateType(mixed $value, string $expectedType, string $fieldName): mixed
    {
        $isValid = match ($expectedType) {
            'string' => is_string($value),
            'array' => is_array($value),
            'object' => is_object($value) || is_array($value),
            'number' => is_numeric($value),
            'boolean' => is_bool($value),
            default => true
        };

        if (! $isValid) {
            throw new \InvalidArgumentException("Field '{$fieldName}' must be of type '{$expectedType}'");
        }

        return $value;
    }

    /**
     * Get safe array value with default
     */
    protected function getArrayValue(array $array, string $key, mixed $default = null): mixed
    {
        return $array[$key] ?? $default;
    }

    /**
     * Check if value is empty (but allow 0 and false)
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }
}
