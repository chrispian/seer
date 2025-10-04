<?php

namespace App\Services\Commands\DSL;

use Illuminate\Support\Facades\Log;

class TemplateEngine
{
    private static array $templateCache = [];

    private static int $cacheHits = 0;

    private static int $cacheMisses = 0;

    /**
     * Render template with context and filters
     */
    public function render(string $template, array $context): string
    {
        // Cache key for complex templates (>100 chars or containing control structures)
        $shouldCache = strlen($template) > 100 || str_contains($template, '{%');
        $cacheKey = $shouldCache ? md5($template) : null;

        if ($cacheKey && isset(self::$templateCache[$cacheKey])) {
            self::$cacheHits++;
            $processedTemplate = self::$templateCache[$cacheKey];
        } else {
            self::$cacheMisses++;

            // First, process control structures like {% if %}
            $processedTemplate = $this->processControlStructures($template, []);

            // Cache the processed template structure (without context)
            if ($cacheKey && count(self::$templateCache) < 100) { // Limit cache size
                self::$templateCache[$cacheKey] = $processedTemplate;
            }
        }

        // Then process variables with context
        return preg_replace_callback(
            '/\{\{\s*([^}]+)\s*\}\}/',
            function ($matches) use ($context) {
                return $this->processVariable($matches[1], $context);
            },
            $processedTemplate
        );
    }

    /**
     * Get template cache statistics
     */
    public static function getCacheStats(): array
    {
        return [
            'hits' => self::$cacheHits,
            'misses' => self::$cacheMisses,
            'cached_templates' => count(self::$templateCache),
            'hit_ratio' => self::$cacheHits + self::$cacheMisses > 0 ?
                round(self::$cacheHits / (self::$cacheHits + self::$cacheMisses) * 100, 2) : 0,
        ];
    }

    /**
     * Process control structures like {% if %} {% elif %} {% else %} {% endif %}
     */
    protected function processControlStructures(string $template, array $context): string
    {
        // Handle nested if blocks by processing from inside out
        // Guard against infinite loops by tracking replacements
        $maxIterations = 100; // Reasonable limit for nested depth
        $iteration = 0;

        while (preg_match('/\{\%\s*if\s+(.+?)\s*\%\}/', $template) && $iteration < $maxIterations) {
            $previousTemplate = $template;

            $template = preg_replace_callback(
                '/\{\%\s*if\s+(.+?)\s*\%\}(.*?)\{\%\s*endif\s*\%\}/s',
                function ($matches) use ($context) {
                    return $this->processIfBlock($matches[1], $matches[2], $context);
                },
                $template
            );

            // Break if no replacements were made (unmatched {% if %} blocks)
            if ($template === $previousTemplate) {
                // Log warning about unmatched control structures (if Laravel is bootstrapped)
                if (app()->bound('log')) {
                    Log::warning('Template engine found unmatched {% if %} blocks', [
                        'template_snippet' => substr($template, 0, 200),
                    ]);
                }
                break;
            }

            $iteration++;
        }

        // Warn if we hit the iteration limit
        if ($iteration >= $maxIterations && app()->bound('log')) {
            Log::warning('Template engine hit maximum iteration limit for control structures', [
                'iterations' => $iteration,
                'template_snippet' => substr($template, 0, 200),
            ]);
        }

        return $template;
    }

    /**
     * Process a single if block with potential elif/else branches
     */
    protected function processIfBlock(string $condition, string $content, array $context): string
    {
        // Split content by elif and else statements
        $parts = preg_split('/\{\%\s*(elif\s+.+?|else)\s*\%\}/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        $ifContent = $parts[0];
        $condition = trim($condition);

        // Check main if condition
        if ($this->evaluateCondition($condition, $context)) {
            return $ifContent;
        }

        // Process elif/else branches
        for ($i = 1; $i < count($parts); $i += 2) {
            $branchType = trim($parts[$i]);
            $branchContent = $parts[$i + 1] ?? '';

            if (str_starts_with($branchType, 'elif ')) {
                $elifCondition = trim(substr($branchType, 5));
                if ($this->evaluateCondition($elifCondition, $context)) {
                    return $branchContent;
                }
            } elseif ($branchType === 'else') {
                return $branchContent;
            }
        }

        return ''; // No conditions matched
    }

    /**
     * Process variable with optional filters
     */
    protected function processVariable(string $expression, array $context): string
    {
        $parts = explode('|', $expression);
        $variable = trim($parts[0]);
        $filters = array_slice($parts, 1);

        // Check if this is an expression (contains operators)
        if ($this->isExpression($variable)) {
            $value = $this->evaluateExpression($variable, $context);
        } else {
            // Get value from context
            $value = $this->getValue($variable, $context);
        }

        // Apply filters
        foreach ($filters as $filter) {
            $value = $this->applyFilter(trim($filter), $value, $context);
        }

        // Convert different types to string representations for template rendering
        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) ($value ?? '');
    }

    /**
     * Check if string contains expression operators
     */
    protected function isExpression(string $variable): bool
    {
        // Check for mathematical operators with surrounding context to avoid matching hyphens in variable names
        // Only match operators that have spaces around them or are between numbers/variables
        if (preg_match('/\s[+\-*\/]\s|^\s*[+\-*\/]\s|\s[+\-*\/]\s*$/', $variable)) {
            return true;
        }

        // Also check for operators between alphanumeric tokens (like var1+var2)
        if (preg_match('/[a-zA-Z0-9_\]]\s*[+*\/]\s*[a-zA-Z0-9_\[]/', $variable)) {
            return true;
        }

        // Check for comparison operators
        if (preg_match('/[<>=!]+/', $variable)) {
            return true;
        }

        // Check for ternary operator
        if (str_contains($variable, '?') && str_contains($variable, ':')) {
            return true;
        }

        return false;
    }

    /**
     * Evaluate expression with basic math and comparison operators
     */
    protected function evaluateExpression(string $expression, array $context): mixed
    {
        // Handle ternary operator first (condition ? true_value : false_value)
        if (preg_match('/(.+?)\s*\?\s*(.+?)\s*:\s*(.+)/', $expression, $matches)) {
            $condition = trim($matches[1]);
            $trueValue = trim($matches[2]);
            $falseValue = trim($matches[3]);

            $conditionResult = $this->evaluateCondition($condition, $context);
            $resultExpression = $conditionResult ? $trueValue : $falseValue;

            // Recursively evaluate the result
            return $this->isExpression($resultExpression)
                ? $this->evaluateExpression($resultExpression, $context)
                : $this->getValue($resultExpression, $context);
        }

        // Handle mathematical operations
        if (preg_match('/(.+?)\s*([+\-*\/])\s*(.+)/', $expression, $matches)) {
            $left = trim($matches[1]);
            $operator = $matches[2];
            $right = trim($matches[3]);

            $leftValue = $this->getValue($left, $context);
            $rightValue = $this->getValue($right, $context);

            // Convert to numbers
            $leftNum = is_numeric($leftValue) ? (is_float($leftValue) ? (float) $leftValue : (int) $leftValue) : 0;
            $rightNum = is_numeric($rightValue) ? (is_float($rightValue) ? (float) $rightValue : (int) $rightValue) : 0;

            return match ($operator) {
                '+' => $leftNum + $rightNum,
                '-' => $leftNum - $rightNum,
                '*' => $leftNum * $rightNum,
                '/' => $rightNum !== 0 ? $leftNum / $rightNum : 0,
                default => 0,
            };
        }

        // Handle comparison operations
        if (preg_match('/(.+?)\s*([><=!]+)\s*(.+)/', $expression, $matches)) {
            $left = trim($matches[1]);
            $operator = $matches[2];
            $right = trim($matches[3]);

            return $this->evaluateCondition($expression, $context);
        }

        // If no operators found, treat as regular variable
        return $this->getValue($expression, $context);
    }

    /**
     * Evaluate condition for boolean result with support for logical operators
     */
    protected function evaluateCondition(string $condition, array $context): bool
    {
        // Handle parentheses for grouping first (deepest nesting)
        while (preg_match('/\(([^()]+)\)/', $condition, $matches)) {
            $innerCondition = $matches[1];
            $innerResult = $this->evaluateCondition($innerCondition, $context);
            $condition = str_replace($matches[0], $innerResult ? 'true' : 'false', $condition);
        }

        // Handle NOT operator
        if (preg_match('/^not\s+(.+)/i', $condition, $matches)) {
            $innerCondition = trim($matches[1]);

            return ! $this->evaluateCondition($innerCondition, $context);
        }

        // Handle logical operators (AND, OR) with proper precedence
        // Split by OR first (lowest precedence)
        if (preg_match('/(.+?)\s+or\s+(.+)/i', $condition)) {
            $parts = preg_split('/\s+or\s+/i', $condition, 2);
            $left = trim($parts[0]);
            $right = trim($parts[1]);

            return $this->evaluateCondition($left, $context) || $this->evaluateCondition($right, $context);
        }

        // Then split by AND (higher precedence)
        if (preg_match('/(.+?)\s+and\s+(.+)/i', $condition)) {
            $parts = preg_split('/\s+and\s+/i', $condition, 2);
            $left = trim($parts[0]);
            $right = trim($parts[1]);

            return $this->evaluateCondition($left, $context) && $this->evaluateCondition($right, $context);
        }

        // Handle template expressions with filters (like startswith, contains, etc.)
        if (str_contains($condition, '|') && ! preg_match('/\s*([><=!]+)\s*/', $condition)) {
            // This is a pure template expression with filters (no comparison operators)
            try {
                $value = $this->processVariable($condition, $context);

                // If the filter result is explicitly boolean-like, use it
                if ($value === true || $value === false) {
                    return $value;
                }
                if ($value === 'true') {
                    return true;
                }
                if ($value === 'false') {
                    return false;
                }
                if ($value === '1') {
                    return true;
                }
                if ($value === '0' || $value === '') {
                    return false;
                }

                // If it's not a boolean, fall through to other checks
            } catch (\Exception $e) {
                // If template processing fails, fall through to other condition checks
            }
        }

        // Handle template expressions with comparison operators
        if (str_contains($condition, '|') && preg_match('/(.+?)\s*([><=!]+)\s*(.+)/', $condition, $matches)) {
            $left = trim($matches[1]);
            $operator = $matches[2];
            $right = trim($matches[3]);

            // If left side contains filters, process it as a template variable
            if (str_contains($left, '|')) {
                $leftValue = $this->processVariable($left, $context);
            } else {
                $leftValue = $this->getValue($left, $context);
            }

            // If right side contains filters, process it as a template variable
            if (str_contains($right, '|')) {
                $rightValue = $this->processVariable($right, $context);
            } else {
                // Check if right side is a string literal
                if (preg_match('/^"(.*)"$/', $right, $quoteMatches) || preg_match("/^'(.*)'$/", $right, $quoteMatches)) {
                    $rightValue = $quoteMatches[1];
                }
                // Check if right side is a numeric literal
                elseif (is_numeric($right)) {
                    $rightValue = is_float($right) ? (float) $right : (int) $right;
                }
                // Otherwise treat as context path
                else {
                    $rightValue = $this->getValue($right, $context);
                }
            }

            // Convert numeric strings to numbers for comparison
            if (is_numeric($leftValue)) {
                $leftValue = is_float($leftValue) ? (float) $leftValue : (int) $leftValue;
            }
            if (is_numeric($rightValue)) {
                $rightValue = is_float($rightValue) ? (float) $rightValue : (int) $rightValue;
            }

            return match ($operator) {
                '==' => $leftValue == $rightValue,
                '!=' => $leftValue != $rightValue,
                '>' => $leftValue > $rightValue,
                '<' => $leftValue < $rightValue,
                '>=' => $leftValue >= $rightValue,
                '<=' => $leftValue <= $rightValue,
                default => false,
            };
        }

        // Handle length comparisons
        if (preg_match('/(.+?)\s*\|\s*length\s*([><=]+)\s*(\d+)/', $condition, $matches)) {
            $variable = trim($matches[1]);
            $operator = $matches[2];
            $expected = (int) $matches[3];

            $value = $this->getValue($variable, $context);
            $actualLength = is_array($value) ? count($value) : strlen((string) $value);

            return match ($operator) {
                '>' => $actualLength > $expected,
                '<' => $actualLength < $expected,
                '>=' => $actualLength >= $expected,
                '<=' => $actualLength <= $expected,
                '==' => $actualLength == $expected,
                '!=' => $actualLength != $expected,
                default => false,
            };
        }

        // Handle direct value comparisons
        if (preg_match('/(.+?)\s*([><=!]+)\s*(.+)/', $condition, $matches)) {
            $left = trim($matches[1]);
            $operator = $matches[2];
            $right = trim($matches[3]);

            $leftValue = $this->getValue($left, $context);

            // Check if right side is a string literal
            if (preg_match('/^["\'](.+)["\']$/', $right, $quoteMatches)) {
                $rightValue = $quoteMatches[1];
            }
            // Check if right side is a numeric literal
            elseif (is_numeric($right)) {
                $rightValue = is_float($right) ? (float) $right : (int) $right;
            }
            // Otherwise treat as context path
            else {
                $rightValue = $this->getValue($right, $context);
            }

            // Convert numeric strings to numbers for comparison
            if (is_numeric($leftValue)) {
                $leftValue = is_float($leftValue) ? (float) $leftValue : (int) $leftValue;
            }
            if (is_numeric($rightValue)) {
                $rightValue = is_float($rightValue) ? (float) $rightValue : (int) $rightValue;
            }

            return match ($operator) {
                '==' => $leftValue == $rightValue,
                '!=' => $leftValue != $rightValue,
                '>' => $leftValue > $rightValue,
                '<' => $leftValue < $rightValue,
                '>=' => $leftValue >= $rightValue,
                '<=' => $leftValue <= $rightValue,
                default => false,
            };
        }

        // Handle boolean literals directly
        $trimmed = trim($condition);
        if ($trimmed === 'true') {
            return true;
        }
        if ($trimmed === 'false') {
            return false;
        }

        // Handle boolean values and truthiness from context
        $value = $this->getValue($condition, $context);

        // Boolean literals from context
        if ($value === 'true' || $value === true) {
            return true;
        }
        if ($value === 'false' || $value === false) {
            return false;
        }

        // Empty/null checks
        if ($value === null || $value === '') {
            return false;
        }

        // Non-empty values are truthy
        return ! empty($value);
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
            } elseif (is_object($value) && property_exists($value, $key)) {
                $value = $value->$key;
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Apply filter to value
     */
    protected function applyFilter(string $filter, mixed $value, array $context = []): mixed
    {
        if (str_contains($filter, ':')) {
            [$filterName, $filterArg] = explode(':', $filter, 2);
            $filterName = trim($filterName);
            $filterArg = trim($filterArg);

            // Remove quotes from string literals (including empty strings)
            if (preg_match('/^"(.*)"$/', $filterArg, $matches) || preg_match("/^'(.*)'$/", $filterArg, $matches)) {
                $filterArg = $matches[1];
            }
            // Evaluate variable references in filter arguments
            elseif (! empty($context) && str_contains($filterArg, '.')) {
                $evaluatedArg = $this->getValue($filterArg, $context);
                if ($evaluatedArg !== null) {
                    $filterArg = $evaluatedArg;
                }
            }
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

            case 'json':
                return json_encode($value);

            case 'length':
                if (is_array($value)) {
                    return count($value);
                }

                return strlen((string) $value);

            case 'first':
                if (is_array($value)) {
                    return reset($value);
                }

                return $value;

            case 'last':
                if (is_array($value)) {
                    return end($value);
                }

                return $value;

            case 'join':
                if (is_array($value)) {
                    return implode($filterArg ?: ', ', $value);
                }

                return $value;

            case 'capitalize':
                return ucfirst((string) $value);

            case 'truncate':
                $length = (int) ($filterArg ?: 100);
                $text = (string) $value;

                return strlen($text) > $length ? substr($text, 0, $length).'...' : $text;

            case 'slice':
                if (is_string($value)) {
                    $start = (int) $filterArg;

                    return substr($value, $start);
                }
                if (is_array($value)) {
                    $start = (int) $filterArg;

                    return array_slice($value, $start);
                }

                return $value;

            case 'first':
                if (is_array($value)) {
                    if (empty($value)) {
                        return null;
                    }

                    return reset($value);
                }
                if (is_string($value)) {
                    return substr($value, 0, 1);
                }

                return $value;

            case 'startswith':
                if (! $filterArg) {
                    return false;
                }

                return str_starts_with((string) $value, $filterArg);

            case 'contains':
                if (! $filterArg) {
                    return false;
                }

                return str_contains((string) $value, $filterArg);

            case 'match':
                if (! $filterArg) {
                    return false;
                }

                return (bool) preg_match('/'.preg_quote($filterArg, '/').'/', (string) $value);

            case 'split':
                $delimiter = $filterArg ?: ',';

                return explode($delimiter, (string) $value);

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
        if (! str_starts_with($path, '$.')) {
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
