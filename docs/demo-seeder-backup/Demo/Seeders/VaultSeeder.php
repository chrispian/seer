<?php

namespace Database\Seeders\Demo\Seeders;

use App\Models\Vault;
use Database\Seeders\Demo\Contracts\DemoSubSeeder;
use Database\Seeders\Demo\Support\DemoSeedContext;

class VaultSeeder implements DemoSubSeeder
{
    private const DEMO_FLAG = 'demo_seed';

    public function seed(DemoSeedContext $context): void
    {
        $definitions = [
            'work' => [
                'slug' => 'work',
                'name' => 'Demo Work Vault',
                'description' => 'Work streams, engineering and product planning',
                'is_default' => false,
                'sort_order' => 1,
            ],
            'personal' => [
                'slug' => 'personal',
                'name' => 'Demo Personal Vault',
                'description' => 'Personal productivity and life management',
                'is_default' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($definitions as $definition) {
            $vault = Vault::firstOrCreate(
                ['metadata->'.self::DEMO_FLAG => true, 'name' => $definition['name']],
                [
                    'description' => $definition['description'],
                    'is_default' => $definition['is_default'],
                    'sort_order' => $definition['sort_order'],
                    'metadata' => [self::DEMO_FLAG => true, 'slug' => $definition['slug'], 'display_name' => $definition['name']],
                ]
            );

            $context->set('vaults', $definition['slug'], $vault);
        }

        $context->info('<info>✔</info> Demo vaults ensured');
    }

    public function cleanup(DemoSeedContext $context): void
    {
        Vault::where('metadata->'.self::DEMO_FLAG, true)
            ->get()
            ->each(function (Vault $vault) use ($context) {
                $slug = $vault->metadata['slug'] ?? (string) $vault->id;
                $vault->delete();
                $context->forget('vaults', $slug);
            });
    }
}
