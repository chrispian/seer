<?php

namespace App\Services\Commands\DSL\Steps;

class TransformStep extends Step
{
    public function getType(): string
    {
        return 'transform';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $template = $config['template'] ?? '';
        $output = $config['output'] ?? 'text';

        if (!$template) {
            throw new \InvalidArgumentException('Transform step requires a template');
        }

        // Template is already rendered by CommandRunner
        $result = $template;

        // Handle output type
        if ($output === 'json') {
            try {
                return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \InvalidArgumentException("Transform step expected JSON output but got invalid JSON: {$e->getMessage()}");
            }
        }

        return $result;
    }

    public function validate(array $config): bool
    {
        return isset($config['template']);
    }
}