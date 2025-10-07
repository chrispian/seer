<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\AgentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'stream' => 'test.stream.' . $this->faker->word(),
            'type' => $this->faker->randomElement(['context_pack', 'notification', 'handoff']),
            'to_agent_id' => AgentProfile::factory(),
            'envelope' => ['test' => 'data'],
        ];
    }
}
