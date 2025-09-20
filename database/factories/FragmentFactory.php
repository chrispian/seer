<?php

namespace Database\Factories;

use App\Models\Fragment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class FragmentFactory extends Factory
{
    protected $model = Fragment::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['todo', 'obs', 'note', 'meeting', 'idea']),
            'message' => $this->faker->sentence(10),
            'title' => null,
            'tags' => [$this->faker->randomElement(['work', 'personal', 'urgent', 'draft'])],
            'relationships' => [],
            'parsed_entities' => null,
            'metadata' => null,
            'vault' => null,
            'project_id' => null,
        ];
    }

    public function withTitle(?string $title = null): static
    {
        return $this->state(fn () => [
            'title' => $title ?? $this->faker->sentence(3),
        ]);
    }

    public function withEmbeddings(): static
    {
        return $this->state(fn () => [
            'embedding' => array_fill(0, 1536, $this->faker->randomFloat(6, -1, 1)),
            'embedding_model' => 'text-embedding-3-small',
            'metadata' => [
                'embedding_generated_at' => now()->toISOString(),
                'model_provider' => 'openai',
            ],
        ]);
    }

    public function withoutEmbeddings(): static
    {
        return $this->state(fn () => [
            'embedding' => null,
            'embedding_model' => null,
        ]);
    }

    public function withAIMetadata(string $provider = 'openai', string $model = 'gpt-4o-mini'): static
    {
        return $this->state(fn () => [
            'metadata' => [
                'ai_provider' => $provider,
                'text_model' => $model,
                'processed_at' => now()->toISOString(),
                'processing_time_ms' => $this->faker->numberBetween(100, 2000),
            ],
        ]);
    }

    public function withEntities(): static
    {
        return $this->state(fn () => [
            'parsed_entities' => [
                'people' => $this->faker->randomElements(['john.doe', 'jane.smith', 'bob.wilson'], $this->faker->numberBetween(0, 2)),
                'emails' => $this->faker->randomElements(['test@example.com', 'user@company.com'], $this->faker->numberBetween(0, 1)),
                'phones' => $this->faker->randomElements(['+1-555-0123', '(555) 123-4567'], $this->faker->numberBetween(0, 1)),
                'urls' => $this->faker->randomElements(['https://example.com', 'https://docs.example.com'], $this->faker->numberBetween(0, 1)),
                'dates' => $this->faker->randomElements(['2024-12-31', 'tomorrow', 'next week'], $this->faker->numberBetween(0, 1)),
            ],
        ]);
    }

    public function withVault(?string $vault = null, ?int $projectId = null): static
    {
        return $this->state(fn () => [
            'vault' => $vault ?? $this->faker->randomElement(['work', 'personal', 'clients']),
            'project_id' => $projectId ?? Project::factory(),
        ]);
    }

    public function todo(): static
    {
        return $this->state(fn () => [
            'type' => 'todo',
            'message' => 'TODO: '.$this->faker->sentence(),
            'tags' => ['todo', $this->faker->randomElement(['urgent', 'low-priority', 'in-progress'])],
        ]);
    }

    public function meeting(): static
    {
        return $this->state(fn () => [
            'type' => 'meeting',
            'message' => 'Meeting notes: '.$this->faker->sentence(15),
            'tags' => ['meeting', $this->faker->randomElement(['standup', 'review', 'planning'])],
            'parsed_entities' => [
                'people' => $this->faker->randomElements(['team.lead', 'project.manager', 'client.contact'], 2),
                'dates' => [now()->addDays($this->faker->numberBetween(1, 7))->format('Y-m-d')],
            ],
        ]);
    }

    public function withComplexContent(): static
    {
        return $this->state(fn () => [
            'message' => 'Call John at +1-555-0123 about the project update. Follow up with sarah@example.com and check https://docs.example.com/report.pdf by 2024-12-31. Meeting with @team.lead tomorrow.',
            'parsed_entities' => [
                'people' => ['John', 'sarah', 'team.lead'],
                'emails' => ['sarah@example.com'],
                'phones' => ['+1-555-0123'],
                'urls' => ['https://docs.example.com/report.pdf'],
                'dates' => ['2024-12-31', 'tomorrow'],
            ],
        ]);
    }
}
