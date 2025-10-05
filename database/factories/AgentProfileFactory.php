<?php

namespace Database\Factories;

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\AgentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AgentProfile>
 */
class AgentProfileFactory extends Factory
{
    protected $model = AgentProfile::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(AgentType::cases());
        $sequence = $this->faker->unique()->numberBetween(1, 9999);
        $name = sprintf('%s Agent %d', $type->label(), $sequence);

        return [
            'name' => $name,
            'slug' => null,
            'type' => $type->value,
            'mode' => $type->defaultMode()->value,
            'description' => $this->faker->sentence(),
            'capabilities' => $this->faker->randomElements([
                'php',
                'laravel',
                'react',
                'testing',
                'documentation',
                'analysis',
                'coordination',
            ], $this->faker->numberBetween(2, 4)),
            'constraints' => $this->faker->randomElements([
                'no_production_access',
                'pair_with_reviewer',
                'requires_context',
            ], $this->faker->numberBetween(0, 2)),
            'tools' => $this->faker->randomElements([
                'composer',
                'artisan',
                'npm',
                'phpunit',
            ], $this->faker->numberBetween(1, 3)),
            'metadata' => ['version' => '1.0'],
            'status' => AgentStatus::Active->value,
        ];
    }

    public function inactive(): self
    {
        return $this->state([
            'status' => AgentStatus::Inactive->value,
        ]);
    }

    public function archived(): self
    {
        return $this->state([
            'status' => AgentStatus::Archived->value,
        ]);
    }
}
