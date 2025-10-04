<?php

namespace App\Services\Commands\DSL\Steps;

class ContextMergeStep extends UtilityStep
{
    public function getType(): string
    {
        return 'context.merge';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $this->validateConfig($config);
        
        $withConfig = $config['with'] ?? $config;
        $sources = $withConfig['sources'] ?? [];
        $strategy = $withConfig['strategy'] ?? 'right_wins';
        $output = $withConfig['output'] ?? 'merged_data';

        if (empty($sources)) {
            throw new \InvalidArgumentException('context.merge step requires sources array');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'sources_count' => count($sources),
                'strategy' => $strategy,
                'output_key' => $output,
                'would_merge' => true,
            ];
        }

        $startTime = microtime(true);
        
        try {
            $mergedData = [];
            
            foreach ($sources as $source) {
                $sourceData = $this->resolveSource($source, $context);
                
                if ($sourceData !== null) {
                    if (is_array($sourceData)) {
                        $mergedData = $this->deepMerge($mergedData, $sourceData, $strategy);
                    } else {
                        // Non-array sources are converted to single value
                        $mergedData['value'] = $sourceData;
                    }
                }
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'output' => $mergedData,
                'sources_processed' => count($sources),
                'strategy_used' => $strategy,
                'processing_time_ms' => $duration,
            ];
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sources_processed' => 0,
                'processing_time_ms' => $duration,
                'fallback' => [],
            ];
        }
    }

    /**
     * Resolve a source to actual data
     */
    protected function resolveSource(mixed $source, array $context): mixed
    {
        // If source is a string, treat it as a template to render
        if (is_string($source)) {
            $rendered = $this->templateEngine->render($source, $context);
            
            // Try to decode JSON if it looks like JSON
            if (is_string($rendered) && str_starts_with(trim($rendered), '{')) {
                $decoded = json_decode($rendered, true);
                return $decoded !== null ? $decoded : $rendered;
            }
            
            return $rendered;
        }
        
        // If source is already an array/object, use as-is but render any templates within
        if (is_array($source)) {
            return $this->renderTemplates($source, $context);
        }
        
        return $source;
    }

    /**
     * Validate the merge configuration
     */
    public function validate(array $config): bool
    {
        $withConfig = $config['with'] ?? $config;
        
        if (!isset($withConfig['sources']) || !is_array($withConfig['sources'])) {
            return false;
        }
        
        if (empty($withConfig['sources'])) {
            return false;
        }
        
        $strategy = $withConfig['strategy'] ?? 'right_wins';
        $validStrategies = ['left_wins', 'right_wins', 'merge_arrays'];
        
        if (!in_array($strategy, $validStrategies)) {
            return false;
        }
        
        return true;
    }

    /**
     * Override deepMerge to add more merge strategies specific to context merging
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
                    $target[$key] = match($strategy) {
                        'left_wins' => $target[$key],
                        'right_wins' => $value,
                        'merge_arrays' => $this->mergeValues($target[$key], $value),
                        'concatenate' => $this->concatenateValues($target[$key], $value),
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
     * Merge two values into an array
     */
    protected function mergeValues(mixed $left, mixed $right): array
    {
        $leftArray = is_array($left) ? $left : [$left];
        $rightArray = is_array($right) ? $right : [$right];
        
        return array_merge($leftArray, $rightArray);
    }

    /**
     * Concatenate two values as strings
     */
    protected function concatenateValues(mixed $left, mixed $right): string
    {
        return ((string) $left) . ((string) $right);
    }
}