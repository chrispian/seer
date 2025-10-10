<?php

namespace Database\Seeders\Demo\Seeders;

use App\Models\Fragment;
use App\Models\Todo;
use Database\Seeders\Demo\Contracts\DemoSubSeeder;
use Database\Seeders\Demo\Support\DemoSeedContext;
use Database\Seeders\Demo\Support\TimelineGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TodoSeeder implements DemoSubSeeder
{
    private const DEMO_FLAG = 'demo_seed';

    private const TODO_COUNT = 100;

    public function __construct(private readonly TimelineGenerator $timeline = new TimelineGenerator) {}

    public function seed(DemoSeedContext $context): void
    {
        $statuses = ['open', 'in_progress', 'blocked', 'complete'];
        $priorities = ['low', 'medium', 'high'];
        $tags = ['demo', 'backlog', 'follow-up', 'urgent', 'learning', 'ops'];

        $faker = fake();
        $dates = $this->timeline->generate(self::TODO_COUNT);

        $dates->each(function (Carbon $timestamp, int $index) use ($context, $statuses, $priorities, $tags, $faker) {
            $vaultKey = $index % 2 === 0 ? 'work' : 'personal';
            $vault = $context->get('vaults', $vaultKey);

            if (! $vault) {
                $vault = $context->collection('vaults')->first(
                    fn ($candidate) => $candidate->name === $vaultKey
                        || ($candidate->metadata['display_name'] ?? null) === $vaultKey
                );
            }

            $projects = $context->collection('projects')
                ->filter(fn ($_, string $key) => str_starts_with($key, "{$vaultKey}."));

            if ($projects->isEmpty()) {
                $projects = $context->collection('projects');
            }

            $project = $projects->values()->random();

            $status = $statuses[$index % count($statuses)];
            $priority = $priorities[$index % count($priorities)];
            $dueAt = $timestamp->copy()->addDays(rand(1, 21));

            $title = Str::headline($faker->unique()->sentence(4));
            $message = 'TODO: '.$title.' - '.$faker->sentence(6);

            $state = [
                'status' => $status,
                'priority' => $priority,
                'due_at' => $dueAt->toIso8601String(),
                'completed_at' => $status === 'complete'
                    ? $timestamp->copy()->addDays(rand(0, 3))->toIso8601String()
                    : null,
            ];

            $fragment = Fragment::create([
                'type' => 'todo',
                'message' => $message,
                'title' => $title,
                'tags' => Arr::random($tags, rand(2, 3)),
                'relationships' => [],
                'metadata' => [
                    self::DEMO_FLAG => true,
                    'demo_category' => 'todo',
                ],
                'state' => $state,
                'vault' => $vault?->name ?? $vaultKey,
                'project_id' => $project->id,
                'inbox_status' => 'accepted',
                'inbox_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            Model::unguarded(function () use ($fragment, $title, $state) {
                Todo::updateOrCreate(
                    ['fragment_id' => $fragment->id],
                    [
                        'title' => $title,
                        'state' => $state,
                    ]
                );
            });

            $context->set('todo_fragments', (string) $fragment->id, $fragment);
        });

        $faker->unique(true);

        $context->info('<info>✔</info> Demo todos created');
    }

    public function cleanup(DemoSeedContext $context): void
    {
        Fragment::with('todo')
            ->where('metadata->'.self::DEMO_FLAG, true)
            ->where('metadata->demo_category', 'todo')
            ->get()
            ->each(function (Fragment $fragment) use ($context) {
                $fragment->todo?->delete();
                $fragment->forceDelete();
                $context->forget('todo_fragments', (string) $fragment->id);
            });
    }
}
