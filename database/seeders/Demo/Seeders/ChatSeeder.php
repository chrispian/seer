<?php

namespace Database\Seeders\Demo\Seeders;

use App\Models\ChatSession;
use App\Models\Fragment;
use Database\Seeders\Demo\Contracts\DemoSubSeeder;
use Database\Seeders\Demo\Support\DemoSeedContext;
use Database\Seeders\Demo\Support\TimelineGenerator;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ChatSeeder implements DemoSubSeeder
{
    private const DEMO_FLAG = 'demo_seed';

    private const CHAT_COUNT = 10;

    private const MESSAGES_PER_CHAT = 5;

    public function __construct(private readonly TimelineGenerator $timeline = new TimelineGenerator) {}

    public function seed(DemoSeedContext $context): void
    {
        $dates = $this->timeline->generate(self::CHAT_COUNT);

        $faker = fake();

        $dates->each(function (Carbon $timestamp, int $index) use ($context, $faker) {
            $vaultKey = $index % 2 === 0 ? 'work' : 'personal';
            $vault = $context->get('vaults', $vaultKey);
            $projects = $context->collection('projects')
                ->filter(fn ($_, string $key) => str_starts_with($key, "{$vaultKey}."));

            if ($projects->isEmpty()) {
                $projects = $context->collection('projects');
            }

            $project = $projects->values()->random();

            $messages = $this->buildMessages($timestamp, $faker);
            $messageFragments = collect();

            $modelChoice = Arr::random([
                ['provider' => 'openai', 'model' => Arr::random(['gpt-4o-mini', 'o4-mini'])],
                ['provider' => 'anthropic', 'model' => 'claude-3.5-sonnet'],
                ['provider' => 'ollama', 'model' => Arr::random(['llama3:8b', 'llama3.1:latest'])],
            ]);

            $title = Str::limit(strip_tags($messages[0]['message']), 60, '...');
            $createdAt = Carbon::parse($messages[0]['timestamp']);
            $lastActivity = Carbon::parse(end($messages)['timestamp']);

            Model::unguarded(function () use ($project, $messages, $modelChoice, $title, $createdAt, $lastActivity, $faker, $messageFragments, $context) {
                $session = ChatSession::create([
                    'vault_id' => $project->vault_id,
                    'project_id' => $project->id,
                    'title' => $title,
                    'messages' => $messages,
                    'metadata' => [
                        self::DEMO_FLAG => true,
                        'demo_category' => 'chat',
                        'session_summary' => $faker->sentence(10),
                    ],
                    'message_count' => count($messages),
                    'last_activity_at' => $lastActivity,
                    'is_active' => true,
                    'is_pinned' => $faker->boolean(10),
                    'sort_order' => 0,
                    'model_provider' => $modelChoice['provider'],
                    'model_name' => $modelChoice['model'],
                    'created_at' => $createdAt,
                    'updated_at' => $lastActivity,
                ]);

                foreach ($messages as $offset => $message) {
                    $messageTimestamp = Carbon::parse($message['timestamp']);
                    $fragment = Fragment::create([
                        'type' => 'chat_message',
                        'title' => Str::limit($message['message'], 60, '...'),
                        'message' => $message['message'],
                        'metadata' => [
                            self::DEMO_FLAG => true,
                            'demo_category' => 'chat_message',
                            'chat_session_id' => $session->id,
                            'author_type' => $message['type'],
                        ],
                        'created_at' => $messageTimestamp,
                        'updated_at' => $messageTimestamp,
                    ]);

                    $messageFragments->push($fragment);
                }

                $context->set('chat_sessions', (string) $session->id, $session);
                $context->set('chat_messages', (string) $session->id, $messageFragments);
            });
        });

        $context->info('<info>✔</info> Demo chat sessions created');
    }

    public function cleanup(DemoSeedContext $context): void
    {
        ChatSession::withTrashed()
            ->where('metadata->'.self::DEMO_FLAG, true)
            ->each(fn (ChatSession $session) => $session->forceDelete());
    }

    private function buildMessages(Carbon $start, Generator $faker): array
    {
        $messages = [];
        $current = $start->copy()->setTime(rand(7, 18), rand(0, 59));

        for ($i = 0; $i < self::MESSAGES_PER_CHAT; $i++) {
            $isUser = $i % 2 === 0;
            $messages[] = [
                'id' => (string) Str::uuid(),
                'type' => $isUser ? 'user' : 'assistant',
                'message' => $isUser
                    ? $faker->sentence(rand(8, 14))
                    : $faker->paragraph(2),
                'timestamp' => $current->toIso8601String(),
                'metadata' => $isUser ? [] : [
                    'response_time_ms' => rand(400, 2500),
                    'model' => Arr::random(['gpt-4o-mini', 'claude-3.5-sonnet', 'llama3:8b']),
                ],
            ];

            $current = $current->copy()->addMinutes(rand(3, 18));
        }

        return $messages;
    }
}
