<?php

    namespace Database\Factories;

    use App\Models\SeerLog;
    use Illuminate\Database\Eloquent\Factories\Factory;

    class SeerLogFactory extends Factory
    {
        protected $model = SeerLog::class;

        public function definition(): array
        {
            return [
                'type' => $this->faker->randomElement(['todo', 'obs', 'note']),
                'message' => $this->faker->sentence,
                'tags' => ['faker'],
                'relationships' => [],
            ];
        }
    }