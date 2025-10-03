<?php

namespace App\Services\Commands\DSL;

class TemplateEngine
{
    /**
     * Render template with context and filters
     */
    public function render(string $template, array $context): string
    {
        // Simple template engine with {{ variable | filter }} syntax
        return preg_replace_callback(
            '/\{\{\s*([^}]+)\s*\}\}/',
            function ($matches) use ($context) {
                return $this->processVariable($matches[1], $context);
            },
            $template
        );
    }

    /**
     * Process variable with optional filters
     */
    protected function processVariable(string $expression, array $context): string
    {
        $parts = explode('|', $expression);
        $variable = trim($parts[0]);
        $filters = array_slice($parts, 1);

        // Get value from context
        $value = $this->getValue($variable, $context);

        // Apply filters
        foreach ($filters as $filter) {
            $value = $this->applyFilter(trim($filter), $value);
        }

        return (string) ($value ?? '');
    }

    /**
     * Get value from context using dot notation
     */
    protected function getValue(string $path, array $context): mixed
    {
        $keys = explode('.', $path);
        $value = $context;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Apply filter to value
     */
    protected function applyFilter(string $filter, mixed $value): mixed
    {
        if (str_contains($filter, ':')) {
            [$filterName, $filterArg] = explode(':', $filter, 2);
            $filterName = trim($filterName);
            $filterArg = trim($filterArg);
        } else {
            $filterName = $filter;
            $filterArg = null;
        }

        switch ($filterName) {
            case 'trim':
                return trim((string) $value);
                
            case 'lower':
                return strtolower((string) $value);
                
            case 'upper':
                return strtoupper((string) $value);
                
            case 'slug':
                return \Str::slug((string) $value);
                
            case 'default':
                return $value ?: $filterArg; // Use ?: instead of ?? for better fallback
                
            case 'take':
                if (is_array($value)) {
                    return array_slice($value, 0, (int) $filterArg);
                }
                return substr((string) $value, 0, (int) $filterArg);
                
            case 'date':
                if ($value) {
                    $date = $value instanceof \DateTime ? $value : new \DateTime($value);
                    return $date->format($filterArg ?: 'Y-m-d\TH:i:s\Z');
                }
                return null;
                
            case 'jsonpath':
                if (is_array($value) || is_object($value)) {
                    return $this->applyJsonPath($value, $filterArg);
                }
                return null;
                
            default:
                return $value;
        }
    }

    /**
     * Apply JSONPath filter (simplified implementation)
     */
    protected function applyJsonPath(mixed $data, string $path): mixed
    {
        // Simple JSONPath implementation for $.foo.bar syntax
        if (!str_starts_with($path, '$.')) {
            return null;
        }

        $path = substr($path, 2); // Remove $.
        $keys = explode('.', $path);
        $result = $data;

        foreach ($keys as $key) {
            if (is_array($result) && array_key_exists($key, $result)) {
                $result = $result[$key];
            } elseif (is_object($result) && property_exists($result, $key)) {
                $result = $result->$key;
            } else {
                return null;
            }
        }

        return $result;
    }
}