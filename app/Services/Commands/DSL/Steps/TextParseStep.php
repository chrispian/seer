<?php

namespace App\Services\Commands\DSL\Steps;

use App\Services\TodoTextParser;

class TextParseStep extends Step
{
    public function __construct(
        protected TodoTextParser $todoParser
    ) {}

    public function getType(): string
    {
        return 'text.parse';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        // Handle both direct config and 'with' nested config
        $withConfig = $config['with'] ?? $config;
        $input = $withConfig['input'] ?? '';
        $parser = $withConfig['parser'] ?? 'todo';
        $rules = $withConfig['rules'] ?? [];

        if ($input === '' || $input === null) {
            throw new \InvalidArgumentException('Text parse step requires input text');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'parser' => $parser,
                'input_length' => strlen($input),
                'rules' => $rules,
                'would_parse' => true,
            ];
        }

        $startTime = microtime(true);

        try {
            $result = match ($parser) {
                'todo' => $this->parseTodoText($input, $rules),
                default => throw new \InvalidArgumentException("Unsupported parser type: {$parser}")
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'parser' => $parser,
                'input' => $input,
                'output' => $result,
                'parsing_time_ms' => $duration,
                'rules_applied' => $rules,
            ];

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => false,
                'parser' => $parser,
                'input' => $input,
                'error' => $e->getMessage(),
                'parsing_time_ms' => $duration,
                'fallback' => $this->getFallbackResult($input, $parser),
            ];
        }
    }

    /**
     * Parse todo text using TodoTextParser
     */
    protected function parseTodoText(string $input, array $rules): array
    {
        $result = $this->todoParser->parse($input);

        // Apply any custom rules
        if (isset($rules['extract_due_date']) && ! $rules['extract_due_date']) {
            $result['due_date'] = null;
        }

        if (isset($rules['extract_priority']) && ! $rules['extract_priority']) {
            $result['priority'] = 'medium';
        }

        if (isset($rules['extract_tags']) && ! $rules['extract_tags']) {
            $result['tags'] = ['todo'];
        }

        if (isset($rules['default_priority'])) {
            $result['priority'] = $rules['default_priority'];
        }

        if (isset($rules['force_tags'])) {
            $result['tags'] = array_unique(array_merge($result['tags'], $rules['force_tags']));
        }

        return $result;
    }

    /**
     * Provide fallback result when parsing fails
     */
    protected function getFallbackResult(string $input, string $parser): array
    {
        return match ($parser) {
            'todo' => [
                'title' => $this->createFallbackTitle($input),
                'description' => $input,
                'due_date' => null,
                'priority' => 'medium',
                'tags' => ['todo'],
                'status' => 'open',
                'parsed_by_fallback' => true,
            ],
            default => [
                'raw_text' => $input,
                'parsed_by_fallback' => true,
            ]
        };
    }

    /**
     * Create a reasonable title from input when parsing fails
     */
    protected function createFallbackTitle(string $input): string
    {
        // Clean up the input and take first part as title
        $title = trim($input);

        // Remove common prefixes
        $prefixes = ['todo:', 'task:', 'do:', 'reminder:'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($title), $prefix)) {
                $title = trim(substr($title, strlen($prefix)));
                break;
            }
        }

        // Limit length
        if (strlen($title) > 80) {
            $title = substr($title, 0, 77).'...';
        }

        return $title ?: 'Untitled Todo';
    }

    public function validate(array $config): bool
    {
        $withConfig = $config['with'] ?? $config;

        if (! isset($withConfig['input']) || empty($withConfig['input'])) {
            return false;
        }

        $parser = $withConfig['parser'] ?? 'todo';
        $supportedParsers = ['todo'];

        if (! in_array($parser, $supportedParsers)) {
            return false;
        }

        return true;
    }
}
