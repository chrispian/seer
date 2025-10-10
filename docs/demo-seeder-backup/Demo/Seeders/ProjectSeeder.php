<?php

namespace Database\Seeders\Demo\Seeders;

use App\Models\Project;
use Database\Seeders\Demo\Contracts\DemoSubSeeder;
use Database\Seeders\Demo\Support\DemoSeedContext;
use InvalidArgumentException;

class ProjectSeeder implements DemoSubSeeder
{
    private const DEMO_FLAG = 'demo_seed';

    public function seed(DemoSeedContext $context): void
    {
        $definitions = [
            'work' => [
                ['name' => 'Demo Engineering Roadmap', 'description' => 'Engineering roadmap planning'],
                ['name' => 'Demo Product Discovery', 'description' => 'Product discovery notes'],
            ],
            'personal' => [
                ['name' => 'Demo Life Admin', 'description' => 'Life admin and errands'],
                ['name' => 'Demo Learning Goals', 'description' => 'Personal development and learning'],
            ],
        ];

        $context->collection('vaults')->each(function ($vault, string $key) use ($context, $definitions) {
            if (! array_key_exists($key, $definitions)) {
                throw new InvalidArgumentException("No project definitions found for vault key '{$key}'");
            }

            foreach ($definitions[$key] as $index => $definition) {
                $project = Project::firstOrCreate(
                    [
                        'vault_id' => $vault->id,
                        'name' => $definition['name'],
                    ],
                    [
                        'description' => $definition['description'],
                        'is_default' => $index === 0,
                        'sort_order' => $index + 1,
                        'metadata' => [self::DEMO_FLAG => true],
                    ]
                );

                $context->set('projects', "{$key}.{$index}", $project);

                if ($index === 0) {
                    $context->set('projects', "{$key}.default", $project);
                }
            }
        });

        $context->info('<info>✔</info> Demo projects ensured');
    }

    public function cleanup(DemoSeedContext $context): void
    {
        Project::where('metadata->'.self::DEMO_FLAG, true)
            ->get()
            ->each(function (Project $project) use ($context) {
                $project->delete();
                $context->forget('projects', (string) $project->id);
            });
    }
}
