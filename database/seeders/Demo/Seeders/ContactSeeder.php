<?php

namespace Database\Seeders\Demo\Seeders;

use App\Models\Contact;
use App\Models\Fragment;
use Database\Seeders\Demo\Contracts\DemoSubSeeder;
use Database\Seeders\Demo\Support\DemoSeedContext;
use Database\Seeders\Demo\Support\TimelineGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ContactSeeder implements DemoSubSeeder
{
    private const DEMO_FLAG = 'demo_seed';

    private const CONTACT_COUNT = 25;

    public function __construct(private readonly TimelineGenerator $timeline = new TimelineGenerator) {}

    public function seed(DemoSeedContext $context): void
    {
        $faker = fake();
        $dates = $this->timeline->generate(self::CONTACT_COUNT);

        $dates->each(function (Carbon $timestamp, int $index) use ($context, $faker) {
            $vaultKey = $index % 2 === 0 ? 'work' : 'personal';
            $vault = $context->get('vaults', $vaultKey);
            $project = $context->collection('projects')
                ->filter(fn ($_, string $key) => str_starts_with($key, "{$vaultKey}."))
                ->values()
                ->random();

            $fullName = $faker->name();
            $organization = $faker->company();
            $message = sprintf('%s from %s — %s', $fullName, $organization, $faker->catchPhrase());

            $fragment = Fragment::create([
                'type' => 'contact',
                'message' => $message,
                'title' => $fullName,
                'tags' => ['contact', 'demo'],
                'relationships' => [],
                'metadata' => [
                    self::DEMO_FLAG => true,
                    'demo_category' => 'contact',
                ],
                'vault' => $vault?->name ?? $vaultKey,
                'project_id' => $project->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'inbox_status' => 'accepted',
                'inbox_at' => $timestamp,
                'reviewed_at' => $timestamp,
            ]);

            Model::unguarded(function () use ($fragment, $faker, $fullName, $organization) {
                Contact::updateOrCreate(
                    ['fragment_id' => $fragment->id],
                    [
                        'full_name' => $fullName,
                        'organization' => $organization,
                        'emails' => Arr::whereNotNull([
                            $faker->unique()->companyEmail(),
                            $faker->boolean(40) ? $faker->unique()->safeEmail() : null,
                        ]),
                        'phones' => Arr::whereNotNull([
                            $faker->phoneNumber(),
                            $faker->boolean(30) ? $faker->e164PhoneNumber() : null,
                        ]),
                        'state' => [
                            self::DEMO_FLAG => true,
                            'role' => $faker->jobTitle(),
                            'tags' => $faker->randomElements([
                                'client', 'partner', 'lead', 'vendor', 'internal',
                            ], $faker->numberBetween(1, 3)),
                        ],
                    ]
                );
            });

            $context->set('contact_fragments', (string) $fragment->id, $fragment);
        });

        $faker->unique(true);

        $context->info('<info>✔</info> Demo contacts created');
    }

    public function cleanup(DemoSeedContext $context): void
    {
        Fragment::with('contact')
            ->where('metadata->'.self::DEMO_FLAG, true)
            ->where('metadata->demo_category', 'contact')
            ->get()
            ->each(function (Fragment $fragment) use ($context) {
                $fragment->contact?->delete();
                $fragment->forceDelete();
                $context->forget('contact_fragments', (string) $fragment->id);
            });
    }
}
