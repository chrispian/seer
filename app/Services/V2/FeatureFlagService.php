<?php

namespace App\Services\V2;

use App\Models\FeUiFeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    protected int $cacheTtl = 300;

    public function isEnabled(string $key, ?object $context = null): bool
    {
        $flag = $this->getFlag($key);

        if (!$flag) {
            return config("fe_feature_flags.flags.{$key}.enabled", false);
        }

        if (!$flag->is_enabled) {
            return false;
        }

        if ($flag->percentage !== null && $context) {
            return $this->evaluatePercentage($flag->percentage, $context);
        }

        if ($flag->conditions && $context) {
            return $this->evaluateConditions($flag->conditions, $context);
        }

        return true;
    }

    public function getValue(string $key, $default = null)
    {
        $flag = $this->getFlag($key);

        if (!$flag || !$flag->is_enabled) {
            return config("fe_feature_flags.flags.{$key}.value", $default);
        }

        return $flag->metadata['value'] ?? $default;
    }

    public function getAllFlags(?string $environment = null): array
    {
        $env = $environment ?? app()->environment();
        
        return Cache::remember(
            "feature_flags:{$env}",
            $this->cacheTtl,
            fn() => FeUiFeatureFlag::enabled()
                ->forEnvironment($env)
                ->get()
                ->keyBy('key')
                ->toArray()
        );
    }

    public function clearCache(?string $environment = null): void
    {
        $env = $environment ?? app()->environment();
        Cache::forget("feature_flags:{$env}");
    }

    protected function getFlag(string $key): ?FeUiFeatureFlag
    {
        $cacheKey = "feature_flag:{$key}";

        return Cache::remember(
            $cacheKey,
            $this->cacheTtl,
            fn() => FeUiFeatureFlag::byKey($key)
                ->forEnvironment()
                ->first()
        );
    }

    protected function evaluatePercentage(int $percentage, object $context): bool
    {
        $identifier = $this->getContextIdentifier($context);
        $hash = crc32($identifier);
        $bucket = $hash % 100;

        return $bucket < $percentage;
    }

    protected function evaluateConditions(array $conditions, object $context): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '==';
            $value = $condition['value'] ?? null;

            if (!$field) {
                continue;
            }

            $contextValue = data_get($context, $field);

            if (!$this->compareValues($contextValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    protected function compareValues($contextValue, string $operator, $expectedValue): bool
    {
        return match ($operator) {
            '==' => $contextValue == $expectedValue,
            '===' => $contextValue === $expectedValue,
            '!=' => $contextValue != $expectedValue,
            '!==' => $contextValue !== $expectedValue,
            '>' => $contextValue > $expectedValue,
            '>=' => $contextValue >= $expectedValue,
            '<' => $contextValue < $expectedValue,
            '<=' => $contextValue <= $expectedValue,
            'in' => in_array($contextValue, (array) $expectedValue),
            'not_in' => !in_array($contextValue, (array) $expectedValue),
            'contains' => str_contains((string) $contextValue, (string) $expectedValue),
            default => false,
        };
    }

    protected function getContextIdentifier(object $context): string
    {
        if (isset($context->id)) {
            return (string) $context->id;
        }

        if (isset($context->email)) {
            return $context->email;
        }

        if (method_exists($context, 'getKey')) {
            return (string) $context->getKey();
        }

        return spl_object_hash($context);
    }
}
