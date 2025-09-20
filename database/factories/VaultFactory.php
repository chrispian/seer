<?php

namespace Database\Factories;

use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vault>
 */
class VaultFactory extends Factory
{
    protected $model = Vault::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'is_default' => false,
            'sort_order' => $this->faker->numberBetween(0, 200),
            'metadata' => null,
        ];
    }
}
