<?php

namespace App\Services\ChatImports;

use App\Models\ChatSession;
use App\Models\Fragment;
use App\Models\Project;
use App\Models\Source;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;

class ChatGptImportService
{
    public function __construct(
        private readonly ChatGptConversationParser $parser,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return array<string, int|bool>
     */
    public function import(string $path, bool $dryRun = false, bool $runPipeline = false): array
    {
        $conversationsPath = $this->resolveConversationsPath($path);
        $contents = File::get($conversationsPath);

        try {
            $rawConversations = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new JsonException('Failed to parse conversations.json: '.$e->getMessage(), $e->getCode(), $e);
        }

        if (! is_array($rawConversations)) {
            throw new JsonException('Invalid conversations payload: expected array of conversations.');
        }

        $sources = $this->resolveSources($dryRun);
        $defaultVault = Vault::getDefault();
        $defaultProject = $defaultVault
            ? Project::getDefaultForVault($defaultVault->id)
            : null;

        $stats = [
            'conversations_total' => 0,
            'conversations_parsed' => 0,
            'conversations_skipped' => 0,
            'sessions_created' => 0,
            'sessions_updated' => 0,
            'messages_imported' => 0,
            'fragments_upserted' => 0,
            'dry_run' => $dryRun,
            'pipeline_requested' => $runPipeline,
        ];

        foreach ($rawConversations as $rawConversation) {
            $stats['conversations_total']++;

            if (! is_array($rawConversation)) {
                $stats['conversations_skipped']++;

                continue;
            }

            $parsed = $this->parser->parse($rawConversation);

            if (! $parsed || $parsed->messages === []) {
                $stats['conversations_skipped']++;

                continue;
            }

            $stats['conversations_parsed']++;
            $stats['messages_imported'] += count($parsed->messages);

            if ($dryRun) {
                continue;
            }

            $this->db->transaction(function () use ($parsed, $sources, $defaultVault, $defaultProject, $runPipeline, &$stats) {
                [$session, $created] = $this->upsertChatSession($parsed, $defaultVault, $defaultProject);

                if ($created) {
                    $stats['sessions_created']++;
                } else {
                    $stats['sessions_updated']++;
                }

                $sessionMessages = [];
                $previousFragmentId = null;

                foreach ($parsed->messages as $index => $message) {
                    $fragment = $this->upsertFragment(
                        conversation: $parsed,
                        message: $message,
                        session: $session,
                        sources: $sources,
                        defaultVault: $defaultVault,
                        defaultProject: $defaultProject,
                        previousFragmentId: $previousFragmentId,
                    );

                    $previousFragmentId = $fragment->id;

                    $sessionMessages[] = [
                        'id' => $message->id,
                        'type' => $message->role,
                        'message' => $message->text,
                        'fragment_id' => $fragment->id,
                        'created_at' => $message->createdAt->toIso8601String(),
                        'metadata' => array_merge($message->metadata, [
                            'chatgpt_message_index' => $index,
                        ]),
                    ];
                }

                $this->updateChatSessionMessages($session, $parsed, $sessionMessages);

                if ($runPipeline) {
                    // Placeholder for specialised pipeline integration.
                }

                $stats['fragments_upserted'] += count($sessionMessages);
            });
        }

        return $stats;
    }

    private function resolveConversationsPath(string $path): string
    {
        $resolved = File::isDirectory($path)
            ? rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'conversations.json'
            : $path;

        if (! File::exists($resolved)) {
            throw new JsonException("conversations.json not found at {$resolved}");
        }

        return $resolved;
    }

    /**
     * @return array<string, Source>
     */
    private function resolveSources(bool $dryRun): array
    {
        $sourceKeys = [
            'chatgpt-web' => 'ChatGPT Web',
            'chatgpt-user' => 'ChatGPT User',
        ];

        $sources = Source::query()->whereIn('key', array_keys($sourceKeys))->get()->keyBy('key');

        if ($sources->count() === count($sourceKeys)) {
            return $sources->all();
        }

        if ($dryRun) {
            // Fall back to virtual records so downstream logic can continue.
            return collect($sourceKeys)
                ->map(fn (string $label, string $key) => new Source(['key' => $key, 'label' => $label, 'meta' => []]))
                ->all();
        }

        foreach ($sourceKeys as $key => $label) {
            if (! $sources->has($key)) {
                $sources[$key] = Source::query()->create([
                    'key' => $key,
                    'label' => $label,
                    'meta' => [],
                ]);
            }
        }

        return $sources->all();
    }

    /**
     * @return array{ChatSession, bool}
     */
    private function upsertChatSession(ParsedConversation $conversation, ?Vault $vault, ?Project $project): array
    {
        $session = ChatSession::query()
            ->where('metadata->chatgpt_conversation_id', $conversation->conversationId)
            ->first();
        $created = false;

        if (! $session) {
            $session = new ChatSession;
            $created = true;
        }

        $session->fill([
            'vault_id' => $session->vault_id ?? $vault?->id,
            'project_id' => $session->project_id ?? $project?->id,
            'title' => $conversation->title,
            'summary' => $session->summary,
            'is_active' => true,
            'model_provider' => 'openai',
            'model_name' => $conversation->metadata['default_model_slug'] ?? null,
            'last_activity_at' => $conversation->updatedAt,
        ]);

        $session->metadata = array_merge($session->metadata ?? [], [
            'chatgpt_conversation_id' => $conversation->conversationId,
            'chatgpt_title' => $conversation->title,
        ]);

        $session->save();

        return [$session, $created];
    }

    /**
     * @param  array<string, Source>  $sources
     */
    private function upsertFragment(
        ParsedConversation $conversation,
        ParsedMessage $message,
        ChatSession $session,
        ?Vault $defaultVault,
        ?Project $defaultProject,
        ?int $previousFragmentId,
        array $sources,
    ): Fragment {
        $sourceKey = $message->role === 'assistant' ? 'chatgpt-web' : 'chatgpt-user';
        $source = $sources[$sourceKey] ?? null;

        return Fragment::withoutTimestamps(function () use (
            $conversation,
            $message,
            $session,
            $defaultVault,
            $defaultProject,
            $previousFragmentId,
            $source,
            $sourceKey,
        ) {
            $fragment = Fragment::query()
                ->where('metadata->chatgpt_message_id', $message->id)
                ->first();

            if (! $fragment) {
                $fragment = new Fragment;
            }

            $fragment->fill([
                'message' => $message->text,
                'type' => 'log',
                'vault' => $defaultVault?->name,
                'project_id' => $defaultProject?->id,
                'source' => $source?->label ?? Str::title(str_replace('-', ' ', $sourceKey)),
                'source_key' => $sourceKey,
            ]);

            $fragment->metadata = array_merge($fragment->metadata ?? [], [
                'chatgpt_conversation_id' => $conversation->conversationId,
                'chatgpt_message_id' => $message->id,
                'chatgpt_role' => $message->role,
            ]);

            $fragment->relationships = array_merge($fragment->relationships ?? [], array_filter([
                'conversation_id' => $conversation->conversationId,
                'previous_fragment_id' => $previousFragmentId,
                'chat_session_id' => $session->id,
            ]));

            $fragment->setAttribute('created_at', $message->createdAt);
            $fragment->setAttribute('updated_at', $message->createdAt);

            $fragment->save();

            return $fragment;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $sessionMessages
     */
    private function updateChatSessionMessages(ChatSession $session, ParsedConversation $conversation, array $sessionMessages): void
    {
        $session->messages = $sessionMessages;
        $session->message_count = count($sessionMessages);

        $lastCreatedAt = end($sessionMessages)['created_at'] ?? $conversation->updatedAt->toIso8601String();
        $session->last_activity_at = CarbonImmutable::parse($lastCreatedAt);
        $session->is_active = true;
        $session->save();
    }
}
