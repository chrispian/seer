<?php

namespace Database\Factories;

use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkItemFactory extends Factory
{
    protected $model = WorkItem::class;

    public function definition(): array
    {
        return [
            'type' => 'task',
            'status' => 'todo',
            'metadata' => [
                'task_code' => 'T-TEST-' . $this->faker->randomNumber(3),
                'task_name' => $this->faker->sentence(),
            ],
        ];
    }
}
