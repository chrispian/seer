<?php

namespace Database\Seeders\Demo\Seeders;

use App\Models\Type;
use Database\Seeders\Demo\Contracts\DemoSubSeeder;
use Database\Seeders\Demo\Support\DemoSeedContext;

class TypeSeeder implements DemoSubSeeder
{
    public function seed(DemoSeedContext $context): void
    {
        $types = [
            ['value' => 'todo', 'label' => 'Todo', 'color' => 'blue'],
            ['value' => 'contact', 'label' => 'Contact', 'color' => 'purple'],
            ['value' => 'note', 'label' => 'Note', 'color' => 'gray'],
        ];

        foreach ($types as $definition) {
            $type = Type::firstOrCreate(['value' => $definition['value']], $definition);
            $context->set('types', $definition['value'], $type);
        }

        $context->info('<info>✔</info> Demo fragment types ensured');
    }

    public function cleanup(DemoSeedContext $context): void
    {
        // Types are shared across environments; we leave them untouched.
    }
}
