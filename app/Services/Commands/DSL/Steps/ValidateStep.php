<?php

namespace App\Services\Commands\DSL\Steps;

class ValidateStep extends Step
{
    public function getType(): string
    {
        return 'validate';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];
        $rules = $with['rules'] ?? [];
        $messages = $with['messages'] ?? [];

        if (empty($rules)) {
            throw new \InvalidArgumentException('Validate step requires rules');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_validate' => true,
                'rules' => $rules,
            ];
        }

        $errors = [];
        $validatedData = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $this->getFieldValue($field, $context);
            
            foreach ($fieldRules as $rule) {
                $ruleName = $rule;
                $ruleParam = null;
                
                if (str_contains($rule, ':')) {
                    [$ruleName, $ruleParam] = explode(':', $rule, 2);
                }
                
                $validationResult = $this->validateRule($field, $value, $ruleName, $ruleParam);
                
                if ($validationResult !== true) {
                    $customMessage = $messages["{$field}.{$ruleName}"] ?? 
                                   $messages[$field] ?? 
                                   $validationResult;
                    $errors[$field][] = $customMessage;
                    break; // Stop on first error for this field
                }
            }
            
            if (!isset($errors[$field])) {
                $validatedData[$field] = $value;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'validated_data' => $validatedData,
            'error_count' => count($errors),
        ];
    }

    protected function getFieldValue(string $field, array $context): mixed
    {
        // Support dot notation for nested fields
        $keys = explode('.', $field);
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

    protected function validateRule(string $field, mixed $value, string $rule, ?string $param): string|true
    {
        switch ($rule) {
            case 'required':
                if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
                    return "The {$field} field is required.";
                }
                return true;

            case 'min':
                $min = (int) $param;
                if (is_string($value) && strlen($value) < $min) {
                    return "The {$field} field must be at least {$min} characters.";
                }
                if (is_numeric($value) && $value < $min) {
                    return "The {$field} field must be at least {$min}.";
                }
                return true;

            case 'max':
                $max = (int) $param;
                if (is_string($value) && strlen($value) > $max) {
                    return "The {$field} field must not exceed {$max} characters.";
                }
                if (is_numeric($value) && $value > $max) {
                    return "The {$field} field must not exceed {$max}.";
                }
                return true;

            case 'length':
                if (!is_string($value)) {
                    return "The {$field} field must be a string.";
                }
                $length = (int) $param;
                if (strlen($value) !== $length) {
                    return "The {$field} field must be exactly {$length} characters.";
                }
                return true;

            case 'numeric':
                if (!is_numeric($value)) {
                    return "The {$field} field must be numeric.";
                }
                return true;

            case 'string':
                if (!is_string($value)) {
                    return "The {$field} field must be a string.";
                }
                return true;

            case 'in':
                $options = explode(',', $param);
                if (!in_array($value, $options)) {
                    return "The {$field} field must be one of: " . implode(', ', $options) . ".";
                }
                return true;

            case 'not_starts_with':
                if (is_string($value) && str_starts_with($value, $param)) {
                    return "The {$field} field must not start with '{$param}'.";
                }
                return true;

            default:
                return true; // Unknown rules pass
        }
    }

    public function validate(array $config): bool
    {
        $with = $config['with'] ?? [];
        return isset($with['rules']) && is_array($with['rules']);
    }
}