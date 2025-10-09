<?php

namespace App\Services\Readwise;

use App\Models\Fragment;
use App\Models\Source;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReadwiseImportService
{
    public function __construct(
        private readonly ReadwiseApiClient $client,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return array<string, int|bool|null>
     */
    public function import(?string $since = null, ?string $cursor = null, bool $dryRun = false): array
    {
        $user = User::query()->firstOrFail();
        $settings = $user->profile_settings ?? [];
        $readwiseSettings = $settings['integrations']['readwise'] ?? [];

        $tokenEncrypted = $readwiseSettings['api_token'] ?? null;
        if (empty($tokenEncrypted)) {
            throw new \RuntimeException('Readwise API token is not configured.');
        }

        $token = Crypt::decryptString($tokenEncrypted);

        Source::query()->firstOrCreate(
            ['key' => 'readwise'],
            ['label' => 'Readwise', 'meta' => []]
        );

        $stats = [
            'highlights_total' => 0,
            'highlights_imported' => 0,
            'highlights_skipped' => 0,
            'dry_run' => $dryRun,
            'last_cursor' => null,
        ];

        $currentCursor = $cursor ?? $readwiseSettings['next_cursor'] ?? null;
        $updatedAfter = $since ?? $readwiseSettings['last_synced_at'] ?? null;
        $latestUpdate = $updatedAfter ? CarbonImmutable::parse($updatedAfter) : null;

        do {
            $response = $this->client->fetchHighlights($token, array_filter([
                'pageCursor' => $currentCursor,
                'updatedAfter' => $updatedAfter,
                'page_size' => 1000,
            ]));

            $books = $response['results'] ?? [];

            foreach ($books as $book) {
                $highlights = Arr::get($book, 'highlights', []);
                $stats['highlights_total'] += count($highlights);

                $bookTitle = Arr::get($book, 'title', 'Untitled');
                $bookAuthor = Arr::get($book, 'author');
                $bookCategory = Arr::get($book, 'category');
                $bookSourceUrl = Arr::get($book, 'source_url');

                foreach ($highlights as $highlight) {
                    $highlightId = Arr::get($highlight, 'id');
                    if (! $highlightId) {
                        $stats['highlights_skipped']++;

                        continue;
                    }

                    $createdAt = $this->parseTimestamp(Arr::get($highlight, 'highlighted_at'))
                        ?? $this->parseTimestamp(Arr::get($highlight, 'updated_at'))
                        ?? CarbonImmutable::now();

                    $updatedAt = $this->parseTimestamp(Arr::get($highlight, 'updated_at')) ?? $createdAt;
                    if (! $latestUpdate || $updatedAt->greaterThan($latestUpdate)) {
                        $latestUpdate = $updatedAt;
                    }

                    if ($dryRun) {
                        $stats['highlights_imported']++;

                        continue;
                    }

                    $this->db->transaction(function () use ($highlightId, $highlight, $createdAt, $updatedAt, $bookTitle, $bookAuthor, $bookCategory, $bookSourceUrl) {
                        $fragment = Fragment::query()->firstOrNew([
                            'metadata->readwise_id' => $highlightId,
                        ]);

                        $message = trim((string) Arr::get($highlight, 'text', ''));
                        $note = trim((string) Arr::get($highlight, 'note', ''));
                        if ($note !== '') {
                            $message .= "\n\nNote: {$note}";
                        }

                        $fragment->fill([
                            'message' => $message,
                            'title' => Str::limit($bookTitle, 120),
                            'type' => 'log',
                            'source' => 'Readwise',
                            'source_key' => 'readwise',
                        ]);

                        $fragment->tags = collect(Arr::get($highlight, 'tags', []))
                            ->pluck('name')
                            ->filter()
                            ->values()
                            ->all();

                        $fragment->metadata = array_merge($fragment->metadata ?? [], [
                            'readwise_id' => $highlightId,
                            'readwise_url' => Arr::get($highlight, 'readwise_url'),
                            'readwise_author' => $bookAuthor,
                            'readwise_category' => $bookCategory,
                            'readwise_location' => Arr::get($highlight, 'location'),
                            'readwise_source_title' => $bookTitle,
                        ]);

                        $fragment->relationships = array_merge($fragment->relationships ?? [], array_filter([
                            'source_url' => $bookSourceUrl ?? Arr::get($highlight, 'url'),
                        ]));

                        $fragment->setAttribute('created_at', $createdAt);
                        $fragment->setAttribute('updated_at', $updatedAt);
                        $fragment->save();
                    });

                    $stats['highlights_imported']++;
                }
            }

            $currentCursor = $response['nextPageCursor'] ?? null;
        } while ($currentCursor);

        if (! $dryRun) {
            $settings['integrations'] = $settings['integrations'] ?? [];
            $settings['integrations']['readwise'] = array_merge(
                $settings['integrations']['readwise'] ?? [],
                [
                    'last_synced_at' => optional($latestUpdate)->toIso8601String(),
                    'next_cursor' => $currentCursor,
                ]
            );

            $user->update(['profile_settings' => $settings]);
        }

        $stats['last_cursor'] = $currentCursor;

        return $stats;
    }

    private function parseTimestamp(?string $value): ?CarbonImmutable
    {
        if (empty($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable $e) {
            Log::warning('Failed to parse Readwise timestamp', ['value' => $value, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
