<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrchestrationSprint>
 */
class OrchestrationSprintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sprint_code' => 'SPRINT-' . strtoupper(fake()->unique()->lexify('???-###')),
            'title' => fake()->sentence(),
            'status' => fake()->randomElement(['planning', 'active', 'completed', 'on_hold']),
            'owner' => fake()->name(),
            'metadata' => [
                'goals' => [fake()->sentence()],
                'start_date' => fake()->date(),
                'duration' => fake()->randomElement(['1 week', '2 weeks', '1 month']),
            ],
            'file_path' => 'delegation/sprints/' . fake()->slug(),
        ];
    }
}
