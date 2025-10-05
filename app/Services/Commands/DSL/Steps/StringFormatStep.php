<?php

namespace App\Services\Commands\DSL\Steps;

class StringFormatStep extends UtilityStep
{
    public function getType(): string
    {
        return 'string.format';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $this->validateConfig($config);

        $withConfig = $config['with'] ?? $config;
        $template = $withConfig['template'] ?? '';
        $data = $withConfig['data'] ?? [];
        $transforms = $withConfig['transforms'] ?? [];

        if (empty($template)) {
            throw new \InvalidArgumentException('string.format step requires template');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'template' => $template,
                'data_keys' => is_array($data) ? array_keys($data) : 'dynamic',
                'transforms_count' => count($transforms),
                'would_format' => true,
            ];
        }

        $startTime = microtime(true);

        try {
            // Resolve data source
            $resolvedData = $this->resolveData($data, $context);

            // Render template with data
            $formatted = $this->renderTemplate($template, $resolvedData, $context);

            // Apply transforms if specified
            if (! empty($transforms)) {
                $formatted = $this->applyTransforms($formatted, $transforms);
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'output' => $formatted,
                'template_used' => $template,
                'data_processed' => $resolvedData,
                'transforms_applied' => count($transforms),
                'processing_time_ms' => $duration,
            ];

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => $duration,
                'fallback' => $template, // Return original template as fallback
            ];
        }
    }

    /**
     * Resolve data source to actual data
     */
    protected function resolveData(mixed $data, array $context): array
    {
        if (is_string($data)) {
            // Data is a template reference
            $rendered = $this->templateEngine->render($data, $context);

            // Try to decode JSON if it looks like JSON
            if (is_string($rendered) && str_starts_with(trim($rendered), '{')) {
                $decoded = json_decode($rendered, true);

                return $decoded !== null ? $decoded : ['value' => $rendered];
            }

            return ['value' => $rendered];
        }

        if (is_array($data)) {
            // Render any templates within the data structure
            return $this->renderTemplates($data, $context);
        }

        // Convert scalar values to array format
        return ['value' => $data];
    }

    /**
     * Render template with enhanced variable substitution
     */
    protected function renderTemplate(string $template, array $data, array $context): string
    {
        // First, substitute data variables using {{ key }} syntax
        $result = preg_replace_callback(
            '/\{\{\s*([^}]+)\s*\}\}/',
            function ($matches) use ($data) {
                $variable = trim($matches[1]);

                // Check if it's a context variable (should be processed by templateEngine)
                if (str_contains($variable, 'steps.') || str_contains($variable, 'ctx.') || str_contains($variable, 'now') || str_contains($variable, 'uuid')) {
                    return $matches[0]; // Leave as-is for template engine
                }

                // Look up in data array
                $value = $this->getNestedValue($data, $variable);

                return $value !== null ? (string) $value : $matches[0];
            },
            $template
        );

        // Then, render any remaining template variables (like steps.*, ctx.*, etc.)
        $result = $this->templateEngine->render($result, $context);

        return $result;
    }

    /**
     * Get nested value from array using dot notation
     */
    protected function getNestedValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

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
     * Apply string-specific transforms
     */
    protected function applyTransforms(mixed $value, array $transforms): mixed
    {
        $result = (string) $value;

        foreach ($transforms as $transform) {
            if (is_string($transform)) {
                $result = $this->applySimpleTransform($result, $transform);
            } elseif (is_array($transform)) {
                foreach ($transform as $transformName => $params) {
                    $result = $this->applyComplexTransform($result, $transformName, $params);
                }
            }
        }

        return $result;
    }

    /**
     * Extended simple transforms for string formatting
     */
    protected function applySimpleTransform(mixed $value, string $transform): mixed
    {
        $stringValue = (string) $value;

        return match ($transform) {
            'uppercase' => strtoupper($stringValue),
            'lowercase' => strtolower($stringValue),
            'trim' => trim($stringValue),
            'title_case' => ucwords($stringValue),
            'capitalize' => ucfirst($stringValue),
            'slug' => \Str::slug($stringValue),
            'reverse' => strrev($stringValue),
            'strip_tags' => strip_tags($stringValue),
            'escape_html' => htmlspecialchars($stringValue, ENT_QUOTES, 'UTF-8'),
            'url_encode' => urlencode($stringValue),
            'base64_encode' => base64_encode($stringValue),
            'md5' => md5($stringValue),
            'length' => strlen($stringValue),
            default => parent::applySimpleTransform($value, $transform)
        };
    }

    /**
     * Extended complex transforms for string formatting
     */
    protected function applyComplexTransform(mixed $value, string $transform, mixed $params): mixed
    {
        $stringValue = (string) $value;

        return match ($transform) {
            'pad_left' => $this->padLeft($stringValue, $params),
            'pad_right' => $this->padRight($stringValue, $params),
            'wrap' => $this->wrapText($stringValue, $params),
            'prefix' => $this->prefixText($stringValue, $params),
            'suffix' => $this->suffixText($stringValue, $params),
            'repeat' => $this->repeatText($stringValue, $params),
            'word_wrap' => $this->wordWrap($stringValue, $params),
            default => parent::applyComplexTransform($value, $transform, $params)
        };
    }

    /**
     * Pad string on the left
     */
    protected function padLeft(string $value, mixed $params): string
    {
        $length = is_numeric($params) ? (int) $params : 10;
        $padString = ' ';

        if (is_array($params)) {
            $length = $params['length'] ?? 10;
            $padString = $params['char'] ?? ' ';
        }

        return str_pad($value, $length, $padString, STR_PAD_LEFT);
    }

    /**
     * Pad string on the right
     */
    protected function padRight(string $value, mixed $params): string
    {
        $length = is_numeric($params) ? (int) $params : 10;
        $padString = ' ';

        if (is_array($params)) {
            $length = $params['length'] ?? 10;
            $padString = $params['char'] ?? ' ';
        }

        return str_pad($value, $length, $padString, STR_PAD_RIGHT);
    }

    /**
     * Wrap text with prefix and suffix
     */
    protected function wrapText(string $value, mixed $params): string
    {
        if (is_string($params)) {
            return $params.$value.$params;
        }

        if (is_array($params)) {
            $prefix = $params['prefix'] ?? '';
            $suffix = $params['suffix'] ?? '';

            return $prefix.$value.$suffix;
        }

        return $value;
    }

    /**
     * Add prefix to text
     */
    protected function prefixText(string $value, mixed $params): string
    {
        $prefix = is_string($params) ? $params : '';

        return $prefix.$value;
    }

    /**
     * Add suffix to text
     */
    protected function suffixText(string $value, mixed $params): string
    {
        $suffix = is_string($params) ? $params : '';

        return $value.$suffix;
    }

    /**
     * Repeat text
     */
    protected function repeatText(string $value, mixed $params): string
    {
        $times = is_numeric($params) ? (int) $params : 1;

        return str_repeat($value, max(0, $times));
    }

    /**
     * Word wrap text
     */
    protected function wordWrap(string $value, mixed $params): string
    {
        $width = is_numeric($params) ? (int) $params : 80;
        $break = "\n";
        $cut = false;

        if (is_array($params)) {
            $width = $params['width'] ?? 80;
            $break = $params['break'] ?? "\n";
            $cut = $params['cut'] ?? false;
        }

        return wordwrap($value, $width, $break, $cut);
    }

    /**
     * Validate string format configuration
     */
    public function validate(array $config): bool
    {
        $withConfig = $config['with'] ?? $config;

        if (! isset($withConfig['template']) || empty($withConfig['template'])) {
            return false;
        }

        if (! is_string($withConfig['template'])) {
            return false;
        }

        return true;
    }
}
