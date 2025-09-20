<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(0, 200),
            'metadata' => null,
        ];
    }
}
