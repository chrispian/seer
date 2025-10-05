<?php

namespace Database\Seeders\Demo\Contracts;

use Database\Seeders\Demo\Support\DemoSeedContext;

interface DemoSubSeeder
{
    public function seed(DemoSeedContext $context): void;

    public function cleanup(DemoSeedContext $context): void;
}
