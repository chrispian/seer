<?php

namespace Database\Factories;

use App\Models\SeerLog;

class SeerLogFactory extends FragmentFactory
{
    protected $model = SeerLog::class;

    public function definition(): array
    {
        $definition = parent::definition();

        $definition['type'] = 'obs';
        $definition['tags'] = $definition['tags'] ?? [];
        $definition['relationships'] = $definition['relationships'] ?? [];

        return $definition;
    }
}
