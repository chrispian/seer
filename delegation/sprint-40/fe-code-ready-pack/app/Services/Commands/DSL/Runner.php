<?php

namespace App\Services\Commands\DSL;

use Symfony\Component\Yaml\Yaml;
use App\Services\Commands\CommandRegistry;

class Runner
{
    public function __construct(
        protected CommandRegistry $registry
    ){}

    public function run(string $slug, array $ctx): array
    {
        $pack = $this->registry->get($slug);
        $doc = Yaml::parse($pack['yaml']);
        $steps = $doc['steps'] ?? [];
        $scope = ['ctx' => $ctx, 'steps' => []];

        foreach ($steps as $step) {
            $type = $step['type'];
            $id = $step['id'];
            $impl = $this->resolveStep($type);
            $out = $impl->execute($step, $scope);
            $scope['steps'][$id] = ['output' => $out];
        }

        return ['ok' => true, 'ref' => null, 'debug' => $scope];
    }

    protected function resolveStep(string $type): Step
    {
        return match($type) {
            'transform' => app(TransformStep::class),
            'ai.generate' => app(AiGenerateStep::class),
            'fragment.create' => app(FragmentCreateStep::class),
            'search.query' => app(SearchQueryStep::class),
            'notify' => app(NotifyStep::class),
            'tool.call' => app(ToolCallStep::class),
            default => throw new \InvalidArgumentException("Unknown step type: $type"),
        };
    }
}
