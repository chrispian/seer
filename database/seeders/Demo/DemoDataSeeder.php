<?php

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Contracts\DemoSubSeeder;
use Database\Seeders\Demo\Seeders\ChatSeeder;
use Database\Seeders\Demo\Seeders\ContactSeeder;
use Database\Seeders\Demo\Seeders\ProjectSeeder;
use Database\Seeders\Demo\Seeders\TodoSeeder;
use Database\Seeders\Demo\Seeders\TypeSeeder;
use Database\Seeders\Demo\Seeders\UserSeeder;
use Database\Seeders\Demo\Seeders\VaultSeeder;
use Database\Seeders\Demo\Support\DemoSeedContext;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /** @var array<int, DemoSubSeeder> */
    private array $seeders;

    public function __construct()
    {
        $this->seeders = [
            new UserSeeder,
            new VaultSeeder,
            new ProjectSeeder,
            new TypeSeeder,
            new ContactSeeder,
            new TodoSeeder,
            new ChatSeeder,
        ];
    }

    public function run(): void
    {
        if (! $this->shouldRun()) {
            $this->command?->warn('DemoDataSeeder skipped: run in local/testing or enable app.seed_demo_data.');

            return;
        }

        $context = new DemoSeedContext($this->command?->getOutput());

        foreach (array_reverse($this->seeders) as $seeder) {
            $seeder->cleanup($context);
        }

        foreach ($this->seeders as $seeder) {
            $seeder->seed($context);
        }

        $context->info('<comment>Demo dataset seeded successfully.</comment>');
    }

    private function shouldRun(): bool
    {
        return app()->environment(['local', 'development', 'testing']) || config('app.seed_demo_data', false);
    }
}
