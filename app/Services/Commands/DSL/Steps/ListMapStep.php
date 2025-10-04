<?php

namespace App\Services\Commands\DSL\Steps;

class ListMapStep extends UtilityStep
{
    public function getType(): string
    {
        return 'list.map';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $this->validateConfig($config);
        
        $withConfig = $config['with'] ?? $config;
        $input = $withConfig['input'] ?? [];
        $template = $withConfig['template'] ?? null;
        $filter = $withConfig['filter'] ?? null;
        $limit = $withConfig['limit'] ?? null;
        $transforms = $withConfig['transforms'] ?? [];

        if ($dryRun) {
            return [
                'dry_run' => true,
                'input_type' => gettype($input),
                'has_template' => $template !== null,
                'has_filter' => $filter !== null,
                'limit' => $limit,
                'transforms_count' => count($transforms),
                'would_map' => true,
            ];
        }

        $startTime = microtime(true);
        
        try {
            // Resolve input to actual array
            $inputArray = $this->resolveInput($input, $context);
            
            if (!is_array($inputArray)) {
                throw new \InvalidArgumentException('list.map input must resolve to an array');
            }
            
            $results = [];
            $processed = 0;
            
            foreach ($inputArray as $index => $item) {
                // Create item context for template rendering
                $itemContext = array_merge($context, [
                    'item' => $item,
                    'index' => $index,
                    'current' => $item, // Alias for convenience
                ]);
                
                // Apply filter if specified
                if ($filter !== null && !$this->evaluateFilter($filter, $itemContext)) {
                    continue;
                }
                
                // Apply template transformation if specified
                if ($template !== null) {
                    $transformedItem = $this->applyTemplate($template, $itemContext);
                } else {
                    $transformedItem = $item;
                }
                
                // Apply transforms if specified
                if (!empty($transforms)) {
                    $transformedItem = $this->applyTransforms($transformedItem, $transforms);
                }
                
                $results[] = $transformedItem;
                $processed++;
                
                // Apply limit if specified
                if ($limit !== null && $processed >= $limit) {
                    break;
                }
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'output' => $results,
                'input_count' => count($inputArray),
                'output_count' => count($results),
                'filtered_count' => count($inputArray) - count($results),
                'processing_time_ms' => $duration,
            ];
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => $duration,
                'fallback' => [],
            ];
        }
    }

    /**
     * Resolve input to actual array
     */
    protected function resolveInput(mixed $input, array $context): array
    {
        if (is_string($input)) {
            $rendered = $this->templateEngine->render($input, $context);
            
            // Try to decode JSON if it looks like JSON
            if (is_string($rendered) && str_starts_with(trim($rendered), '[')) {
                $decoded = json_decode($rendered, true);
                return $decoded !== null ? $decoded : [$rendered];
            }
            
            return is_array($rendered) ? $rendered : [$rendered];
        }
        
        if (is_array($input)) {
            return $input;
        }
        
        // Convert scalar to single-item array
        return [$input];
    }

    /**
     * Evaluate filter condition for an item
     */
    protected function evaluateFilter(string $filter, array $itemContext): bool
    {
        try {
            $rendered = $this->templateEngine->render('{{ ' . $filter . ' }}', $itemContext);
            
            // Convert result to boolean
            if ($rendered === 'true' || $rendered === '1') {
                return true;
            } elseif ($rendered === 'false' || $rendered === '0' || $rendered === '') {
                return false;
            }
            
            // For other values, check truthiness
            return !empty($rendered);
            
        } catch (\Exception $e) {
            // If filter evaluation fails, exclude the item
            return false;
        }
    }

    /**
     * Apply template transformation to an item
     */
    protected function applyTemplate(mixed $template, array $itemContext): mixed
    {
        if (is_string($template)) {
            // String template - render and return
            return $this->templateEngine->render($template, $itemContext);
        }
        
        if (is_array($template)) {
            // Object template - render each property
            return $this->renderTemplates($template, $itemContext);
        }
        
        return $template;
    }

    /**
     * Apply array-specific transforms
     */
    protected function applyTransforms(mixed $value, array $transforms): mixed
    {
        foreach ($transforms as $transform) {
            if (is_string($transform)) {
                $value = $this->applySimpleTransform($value, $transform);
            } elseif (is_array($transform)) {
                foreach ($transform as $transformName => $params) {
                    $value = $this->applyComplexTransform($value, $transformName, $params);
                }
            }
        }
        
        return $value;
    }

    /**
     * Extended simple transforms for array operations
     */
    protected function applySimpleTransform(mixed $value, string $transform): mixed
    {
        return match($transform) {
            'flatten' => $this->flattenArray($value),
            'unique' => $this->uniqueArray($value),
            'sort' => $this->sortArray($value),
            'reverse' => $this->reverseArray($value),
            'compact' => $this->compactArray($value),
            'keys' => $this->getKeys($value),
            'values' => $this->getValues($value),
            'count' => $this->getCount($value),
            default => parent::applySimpleTransform($value, $transform)
        };
    }

    /**
     * Extended complex transforms for array operations
     */
    protected function applyComplexTransform(mixed $value, string $transform, mixed $params): mixed
    {
        return match($transform) {
            'slice' => $this->sliceArray($value, $params),
            'chunk' => $this->chunkArray($value, $params),
            'group_by' => $this->groupBy($value, $params),
            'pluck' => $this->pluckValues($value, $params),
            'where' => $this->whereArray($value, $params),
            'sort_by' => $this->sortBy($value, $params),
            default => parent::applyComplexTransform($value, $transform, $params)
        };
    }

    /**
     * Flatten array
     */
    protected function flattenArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        $result = [];
        array_walk_recursive($value, function($item) use (&$result) {
            $result[] = $item;
        });
        
        return $result;
    }

    /**
     * Get unique values
     */
    protected function uniqueArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        return array_values(array_unique($value, SORT_REGULAR));
    }

    /**
     * Sort array
     */
    protected function sortArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        $sorted = $value;
        sort($sorted);
        return $sorted;
    }

    /**
     * Reverse array
     */
    protected function reverseArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        return array_reverse($value);
    }

    /**
     * Remove empty values
     */
    protected function compactArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        return array_values(array_filter($value, function($item) {
            return !$this->isEmpty($item);
        }));
    }

    /**
     * Get array keys
     */
    protected function getKeys(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        return array_keys($value);
    }

    /**
     * Get array values
     */
    protected function getValues(mixed $value): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        return array_values($value);
    }

    /**
     * Get count
     */
    protected function getCount(mixed $value): int
    {
        if (is_array($value)) {
            return count($value);
        } elseif (is_string($value)) {
            return strlen($value);
        }
        
        return 1;
    }

    /**
     * Slice array
     */
    protected function sliceArray(mixed $value, mixed $params): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        $start = 0;
        $length = null;
        
        if (is_numeric($params)) {
            $start = (int) $params;
        } elseif (is_array($params)) {
            $start = $params['start'] ?? 0;
            $length = $params['length'] ?? null;
        }
        
        return array_slice($value, $start, $length);
    }

    /**
     * Chunk array
     */
    protected function chunkArray(mixed $value, mixed $params): array
    {
        if (!is_array($value)) {
            return [[$value]];
        }
        
        $size = is_numeric($params) ? (int) $params : 2;
        return array_chunk($value, max(1, $size));
    }

    /**
     * Group by field
     */
    protected function groupBy(mixed $value, mixed $params): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        $field = is_string($params) ? $params : 'id';
        $grouped = [];
        
        foreach ($value as $item) {
            if (is_array($item) && isset($item[$field])) {
                $key = $item[$field];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [];
                }
                $grouped[$key][] = $item;
            }
        }
        
        return $grouped;
    }

    /**
     * Pluck values by field
     */
    protected function pluckValues(mixed $value, mixed $params): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        $field = is_string($params) ? $params : 'id';
        $result = [];
        
        foreach ($value as $item) {
            if (is_array($item) && isset($item[$field])) {
                $result[] = $item[$field];
            }
        }
        
        return $result;
    }

    /**
     * Filter where field matches value
     */
    protected function whereArray(mixed $value, mixed $params): array
    {
        if (!is_array($value) || !is_array($params)) {
            return is_array($value) ? $value : [$value];
        }
        
        $field = $params['field'] ?? 'id';
        $operator = $params['operator'] ?? '==';
        $filterValue = $params['value'] ?? null;
        
        return array_values(array_filter($value, function($item) use ($field, $operator, $filterValue) {
            if (!is_array($item) || !isset($item[$field])) {
                return false;
            }
            
            $itemValue = $item[$field];
            
            return match($operator) {
                '==' => $itemValue == $filterValue,
                '!=' => $itemValue != $filterValue,
                '>' => $itemValue > $filterValue,
                '<' => $itemValue < $filterValue,
                '>=' => $itemValue >= $filterValue,
                '<=' => $itemValue <= $filterValue,
                'contains' => str_contains((string) $itemValue, (string) $filterValue),
                'starts_with' => str_starts_with((string) $itemValue, (string) $filterValue),
                'ends_with' => str_ends_with((string) $itemValue, (string) $filterValue),
                default => false
            };
        }));
    }

    /**
     * Sort by field
     */
    protected function sortBy(mixed $value, mixed $params): array
    {
        if (!is_array($value)) {
            return [$value];
        }
        
        $field = is_string($params) ? $params : 'id';
        $direction = 'asc';
        
        if (is_array($params)) {
            $field = $params['field'] ?? 'id';
            $direction = $params['direction'] ?? 'asc';
        }
        
        $sorted = $value;
        usort($sorted, function($a, $b) use ($field, $direction) {
            if (!is_array($a) || !is_array($b)) {
                return 0;
            }
            
            $aValue = $a[$field] ?? null;
            $bValue = $b[$field] ?? null;
            
            if ($aValue === $bValue) {
                return 0;
            }
            
            $result = $aValue < $bValue ? -1 : 1;
            return $direction === 'desc' ? -$result : $result;
        });
        
        return $sorted;
    }

    /**
     * Validate list map configuration
     */
    public function validate(array $config): bool
    {
        $withConfig = $config['with'] ?? $config;
        
        if (!isset($withConfig['input'])) {
            return false;
        }
        
        return true;
    }
}