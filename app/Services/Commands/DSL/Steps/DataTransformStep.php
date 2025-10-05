<?php

namespace App\Services\Commands\DSL\Steps;

use Carbon\Carbon;

class DataTransformStep extends UtilityStep
{
    public function getType(): string
    {
        return 'data.transform';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $this->validateConfig($config);
        
        $withConfig = $config['with'] ?? $config;
        $input = $withConfig['input'] ?? [];
        $rules = $withConfig['rules'] ?? [];

        if (empty($rules)) {
            throw new \InvalidArgumentException('data.transform step requires transformation rules');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'input_type' => gettype($input),
                'rules_count' => count($rules),
                'rules' => array_map(function($rule) {
                    return [
                        'field' => $rule['field'] ?? 'unknown',
                        'transform' => $rule['transform'] ?? $rule['to'] ?? 'unknown'
                    ];
                }, $rules),
                'would_transform' => true,
            ];
        }

        $startTime = microtime(true);
        
        try {
            // Resolve input to actual data
            $inputData = $this->resolveInput($input, $context);
            
            // Apply transformation rules
            $transformedData = $this->applyTransformationRules($inputData, $rules);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'output' => $transformedData,
                'rules_applied' => count($rules),
                'processing_time_ms' => $duration,
            ];
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => $duration,
                'fallback' => $input,
            ];
        }
    }

    /**
     * Resolve input to actual data
     */
    protected function resolveInput(mixed $input, array $context): mixed
    {
        if (is_string($input)) {
            $rendered = $this->templateEngine->render($input, $context);
            
            // Try to decode JSON if it looks like JSON
            if (is_string($rendered) && (str_starts_with(trim($rendered), '{') || str_starts_with(trim($rendered), '['))) {
                $decoded = json_decode($rendered, true);
                return $decoded !== null ? $decoded : $rendered;
            }
            
            return $rendered;
        }
        
        if (is_array($input)) {
            return $this->renderTemplates($input, $context);
        }
        
        return $input;
    }

    /**
     * Apply transformation rules to data
     */
    protected function applyTransformationRules(mixed $data, array $rules): mixed
    {
        $result = $data;
        
        foreach ($rules as $rule) {
            $result = $this->applyTransformationRule($result, $rule);
        }
        
        return $result;
    }

    /**
     * Apply a single transformation rule
     */
    protected function applyTransformationRule(mixed $data, array $rule): mixed
    {
        $field = $rule['field'] ?? null;
        $transform = $rule['transform'] ?? null;
        $from = $rule['from'] ?? null;
        $to = $rule['to'] ?? null;
        $format = $rule['format'] ?? null;
        $map = $rule['map'] ?? null;
        $default = $rule['default'] ?? null;

        if ($field === null) {
            throw new \InvalidArgumentException('Transformation rule must specify field');
        }

        // Handle array/object data
        if (is_array($data)) {
            if (isset($data[$field])) {
                $originalValue = $data[$field];
                $transformedValue = $this->transformValue($originalValue, $rule);
                $data[$field] = $transformedValue;
            } elseif ($default !== null) {
                $data[$field] = $default;
            }
            return $data;
        }
        
        // Handle scalar data - only transform if field matches some key
        return $data;
    }

    /**
     * Transform a single value according to rule
     */
    protected function transformValue(mixed $value, array $rule): mixed
    {
        $transform = $rule['transform'] ?? null;
        $from = $rule['from'] ?? null;
        $to = $rule['to'] ?? null;
        $format = $rule['format'] ?? null;
        $map = $rule['map'] ?? null;

        // Map transformation (lookup table)
        if ($map !== null && is_array($map)) {
            return $map[$value] ?? $value;
        }

        // Type conversion
        if ($from !== null && $to !== null) {
            return $this->convertType($value, $from, $to, $format);
        }

        // Named transformation
        if ($transform !== null) {
            return $this->applyNamedTransformation($value, $transform, $rule);
        }

        return $value;
    }

    /**
     * Convert value from one type to another
     */
    protected function convertType(mixed $value, string $from, string $to, ?string $format = null): mixed
    {
        try {
            return match($to) {
                'string' => $this->convertToString($value, $format),
                'integer', 'int' => $this->convertToInteger($value),
                'float', 'double' => $this->convertToFloat($value),
                'boolean', 'bool' => $this->convertToBoolean($value),
                'array' => $this->convertToArray($value),
                'object' => $this->convertToObject($value),
                'carbon', 'date' => $this->convertToCarbon($value, $format),
                'timestamp' => $this->convertToTimestamp($value, $format),
                'json' => $this->convertToJson($value),
                default => $value
            };
        } catch (\Exception $e) {
            // Return original value if conversion fails
            return $value;
        }
    }

    /**
     * Convert to string
     */
    protected function convertToString(mixed $value, ?string $format = null): string
    {
        if ($format && $value instanceof Carbon) {
            return $value->format($format);
        }
        
        if ($format && is_numeric($value)) {
            return sprintf($format, $value);
        }
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        return (string) $value;
    }

    /**
     * Convert to integer
     */
    protected function convertToInteger(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        
        if (is_string($value)) {
            // Handle common string representations
            $clean = trim(strtolower($value));
            if ($clean === 'true' || $clean === 'yes') return 1;
            if ($clean === 'false' || $clean === 'no') return 0;
        }
        
        return (int) $value;
    }

    /**
     * Convert to float
     */
    protected function convertToFloat(mixed $value): float
    {
        return (float) $value;
    }

    /**
     * Convert to boolean
     */
    protected function convertToBoolean(mixed $value): bool
    {
        if (is_string($value)) {
            $clean = trim(strtolower($value));
            return !in_array($clean, ['', '0', 'false', 'no', 'off', 'null']);
        }
        
        return (bool) $value;
    }

    /**
     * Convert to array
     */
    protected function convertToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // Try JSON decode first
            $decoded = json_decode($value, true);
            if ($decoded !== null) {
                return is_array($decoded) ? $decoded : [$decoded];
            }
            
            // Try comma-separated values
            if (str_contains($value, ',')) {
                return array_map('trim', explode(',', $value));
            }
        }
        
        return [$value];
    }

    /**
     * Convert to object
     */
    protected function convertToObject(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : ['value' => $value];
        }
        
        return ['value' => $value];
    }

    /**
     * Convert to Carbon date
     */
    protected function convertToCarbon(mixed $value, ?string $format = null): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }
        
        if (is_string($value)) {
            try {
                if ($format) {
                    return Carbon::createFromFormat($format, $value);
                }
                return Carbon::parse($value);
            } catch (\Exception $e) {
                return Carbon::now();
            }
        }
        
        return Carbon::now();
    }

    /**
     * Convert to timestamp
     */
    protected function convertToTimestamp(mixed $value, ?string $format = null): int
    {
        $carbon = $this->convertToCarbon($value, $format);
        return $carbon->timestamp;
    }

    /**
     * Convert to JSON
     */
    protected function convertToJson(mixed $value): string
    {
        return json_encode($value);
    }

    /**
     * Apply named transformation
     */
    protected function applyNamedTransformation(mixed $value, string $transform, array $rule): mixed
    {
        return match($transform) {
            'split' => $this->splitValue($value, $rule),
            'join' => $this->joinValue($value, $rule),
            'extract' => $this->extractValue($value, $rule),
            'calculate' => $this->calculateValue($value, $rule),
            'format_date' => $this->formatDate($value, $rule),
            'normalize' => $this->normalizeValue($value, $rule),
            'validate' => $this->validateValue($value, $rule),
            default => $value
        };
    }

    /**
     * Split string value
     */
    protected function splitValue(mixed $value, array $rule): array
    {
        $delimiter = $rule['delimiter'] ?? ',';
        $limit = $rule['limit'] ?? null;
        
        $string = (string) $value;
        
        if ($limit) {
            return explode($delimiter, $string, $limit);
        }
        
        return explode($delimiter, $string);
    }

    /**
     * Join array value
     */
    protected function joinValue(mixed $value, array $rule): string
    {
        $delimiter = $rule['delimiter'] ?? ',';
        
        if (!is_array($value)) {
            return (string) $value;
        }
        
        return implode($delimiter, $value);
    }

    /**
     * Extract value using regex or key
     */
    protected function extractValue(mixed $value, array $rule): mixed
    {
        $pattern = $rule['pattern'] ?? null;
        $key = $rule['key'] ?? null;
        
        if ($pattern && is_string($value)) {
            if (preg_match($pattern, $value, $matches)) {
                return $matches[1] ?? $matches[0];
            }
        }
        
        if ($key && is_array($value)) {
            return $value[$key] ?? null;
        }
        
        return $value;
    }

    /**
     * Calculate value using expression
     */
    protected function calculateValue(mixed $value, array $rule): mixed
    {
        $expression = $rule['expression'] ?? null;
        
        if (!$expression || !is_numeric($value)) {
            return $value;
        }
        
        // Simple math operations
        $numericValue = (float) $value;
        
        if (preg_match('/^([+\-*\/])\s*(.+)$/', $expression, $matches)) {
            $operator = $matches[1];
            $operand = (float) $matches[2];
            
            return match($operator) {
                '+' => $numericValue + $operand,
                '-' => $numericValue - $operand,
                '*' => $numericValue * $operand,
                '/' => $operand != 0 ? $numericValue / $operand : $numericValue,
                default => $numericValue
            };
        }
        
        return $value;
    }

    /**
     * Format date value
     */
    protected function formatDate(mixed $value, array $rule): string
    {
        $format = $rule['format'] ?? 'Y-m-d H:i:s';
        $inputFormat = $rule['input_format'] ?? null;
        
        $carbon = $this->convertToCarbon($value, $inputFormat);
        return $carbon->format($format);
    }

    /**
     * Normalize value
     */
    protected function normalizeValue(mixed $value, array $rule): mixed
    {
        $type = $rule['type'] ?? 'string';
        
        return match($type) {
            'phone' => $this->normalizePhone($value),
            'email' => $this->normalizeEmail($value),
            'url' => $this->normalizeUrl($value),
            'slug' => \Str::slug((string) $value),
            default => $value
        };
    }

    /**
     * Validate value
     */
    protected function validateValue(mixed $value, array $rule): mixed
    {
        $rules = $rule['rules'] ?? [];
        
        foreach ($rules as $validationRule) {
            if (!$this->isValid($value, $validationRule)) {
                throw new \InvalidArgumentException("Validation failed for rule: {$validationRule}");
            }
        }
        
        return $value;
    }

    /**
     * Normalize phone number
     */
    protected function normalizePhone(mixed $value): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $value);
        return $phone;
    }

    /**
     * Normalize email
     */
    protected function normalizeEmail(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    /**
     * Normalize URL
     */
    protected function normalizeUrl(mixed $value): string
    {
        $url = trim((string) $value);
        
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }
        
        return $url;
    }

    /**
     * Check if value is valid according to rule
     */
    protected function isValid(mixed $value, string $rule): bool
    {
        return match($rule) {
            'required' => !$this->isEmpty($value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean' => is_bool($value) || in_array(strtolower((string) $value), ['true', 'false', '1', '0']),
            default => true
        };
    }

    /**
     * Validate data transform configuration
     */
    public function validate(array $config): bool
    {
        $withConfig = $config['with'] ?? $config;
        
        if (!isset($withConfig['input'])) {
            return false;
        }
        
        if (!isset($withConfig['rules']) || !is_array($withConfig['rules'])) {
            return false;
        }
        
        if (empty($withConfig['rules'])) {
            return false;
        }
        
        return true;
    }
}