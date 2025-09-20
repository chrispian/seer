<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Vault;
use App\Models\VaultRoutingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaultRoutingRule>
 */
class VaultRoutingRuleFactory extends Factory
{
    protected $model = VaultRoutingRule::class;

    public function definition(): array
    {
        return [
            'name' => 'test-rule-'.$this->faker->randomNumber(),
            'match_type' => $this->faker->randomElement(['keyword', 'type', 'tag']),
            'match_value' => 'test-value-'.$this->faker->randomNumber(),
            'conditions' => [
                'threshold' => $this->faker->randomFloat(2, 0.1, 0.9),
                'keywords' => ['test', 'data'],
            ],
            'target_vault_id' => Vault::factory(),
            'target_project_id' => Project::factory(),
            'scope_vault_id' => null,
            'scope_project_id' => null,
            'priority' => $this->faker->numberBetween(1, 200),
            'is_active' => true,
            'notes' => 'Test routing rule notes',
        ];
    }
}
