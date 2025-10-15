<?php

namespace App\Services;

use App\Models\FeUiFeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    public function isActive(string $key, array $context = []): bool
    {
        $flag = Cache::remember("flag:{$key}", 30, fn() => FeUiFeatureFlag::where('key',$key)->first());
        if (!$flag || !$flag->enabled) {
            return false;
        }

        $now = now();
        if ($flag->starts_at && $now->lt($flag->starts_at)) return false;
        if ($flag->ends_at && $now->gt($flag->ends_at)) return false;

        $conditions = $flag->conditions_json ?? [];
        if (!$this->passesConditions($conditions, $context)) return false;

        // Percentage rollout: stable bucket per user/session
        $bucketSource = $context['user_id'] ?? $context['session_id'] ?? uniqid('', true);
        $bucket = crc32((string) $bucketSource) % 100; // 0..99
        return $bucket < max(0, min(100, (int) $flag->rollout));
    }

    protected function passesConditions(array $conditions, array $context): bool
    {
        $check = function(string $key, callable $fn) use ($conditions, $context) {
            return !isset($conditions[$key]) || $fn($conditions[$key], $context);
        };

        return
            $check('roles',    fn($need,$ctx) => empty($need) || array_intersect((array)($ctx['roles'] ?? []), (array)$need)) &&
            $check('users',    fn($need,$ctx) => empty($need) || in_array($ctx['user_id'] ?? null, (array)$need, true)) &&
            $check('paths',    fn($need,$ctx) => empty($need) || $this->pathMatches($ctx['path'] ?? '', (array)$need)) &&
            $check('modules',  fn($need,$ctx) => empty($need) || in_array($ctx['module'] ?? null, (array)$need, true)) &&
            $check('env',      fn($need,$ctx) => empty($need) || in_array(app()->environment(), (array)$need, true));
    }

    protected function pathMatches(string $path, array $patterns): bool
    {
        foreach ($patterns as $p) {
            $regex = '#^' . str_replace(['*','/'], ['.*','\/'], $p) . '$#';
            if (preg_match($regex, $path)) return true;
        }
        return false;
    }
}
