<?php

namespace App\Services\Commands\DSL;

class TemplateEngine
{
    /**
     * Render template with context and filters
     */
    public function render(string $template, array $context): string
    {
        // First, process control structures like {% if %}
        $template = $this->processControlStructures($template, $context);
        
        // Then process variables with {{ variable | filter }} syntax
        return preg_replace_callback(
            '/\{\{\s*([^}]+)\s*\}\}/',
            function ($matches) use ($context) {
                return $this->processVariable($matches[1], $context);
            },
            $template
        );
    }

    /**
     * Process control structures like {% if %} {% elif %} {% else %} {% endif %}
     */
    protected function processControlStructures(string $template, array $context): string
    {
        // Handle nested if blocks by processing from inside out
        while (preg_match('/\{\%\s*if\s+(.+?)\s*\%\}/', $template)) {
            $template = preg_replace_callback(
                '/\{\%\s*if\s+(.+?)\s*\%\}(.*?)\{\%\s*endif\s*\%\}/s',
                function ($matches) use ($context) {
                    return $this->processIfBlock($matches[1], $matches[2], $context);
                },
                $template
            );
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
            $value = $this->applyFilter(trim($filter), $value);
        }

        return (string) ($value ?? '');
    }

    /**
     * Check if string contains expression operators
     */
    protected function isExpression(string $variable): bool
    {
        // Check for mathematical operators
        if (preg_match('/[+\-*\/]/', $variable)) {
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
     * Evaluate condition for boolean result
     */
    protected function evaluateCondition(string $condition, array $context): bool
    {
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
            } else {
                $rightValue = $this->getValue($right, $context);
            }

            // Convert numeric strings to numbers for comparison
            if (is_numeric($leftValue)) $leftValue = is_float($leftValue) ? (float) $leftValue : (int) $leftValue;
            if (is_numeric($rightValue)) $rightValue = is_float($rightValue) ? (float) $rightValue : (int) $rightValue;

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

        // Handle boolean values and truthiness
        $value = $this->getValue($condition, $context);
        
        // Boolean literals
        if ($value === 'true' || $value === true) return true;
        if ($value === 'false' || $value === false) return false;
        
        // Empty/null checks
        if ($value === null || $value === '') return false;
        
        // Non-empty values are truthy
        return !empty($value);
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
                return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;

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
                    if (empty($value)) return null;
                    return reset($value);
                }
                if (is_string($value)) {
                    return substr($value, 0, 1);
                }
                return $value;

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
