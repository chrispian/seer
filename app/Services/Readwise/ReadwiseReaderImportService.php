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

class ReadwiseReaderImportService
{
    private const RATE_LIMIT_BUFFER = 5;

    public function __construct(
        private readonly ReadwiseApiClient $client,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Import Reader documents with rate limit handling.
     *
     * @return array<string, int|bool|string|null>
     */
    public function import(?string $since = null, ?string $cursor = null, bool $dryRun = false): array
    {
        $user = User::query()->firstOrFail();
        $settings = $user->profile_settings ?? [];
        $readerSettings = $settings['integrations']['readwise']['reader'] ?? [];

        $tokenEncrypted = $settings['integrations']['readwise']['api_token'] ?? null;
        if (empty($tokenEncrypted)) {
            throw new \RuntimeException('Readwise API token is not configured.');
        }

        $token = Crypt::decryptString($tokenEncrypted);

        Source::query()->firstOrCreate(
            ['key' => 'readwise-reader'],
            ['label' => 'Readwise Reader', 'meta' => []]
        );

        $stats = [
            'documents_total' => 0,
            'documents_imported' => 0,
            'documents_skipped' => 0,
            'dry_run' => $dryRun,
            'rate_limited' => false,
            'last_cursor' => null,
            'last_updated_at' => null,
        ];

        $currentCursor = $cursor ?? $readerSettings['next_cursor'] ?? null;
        $updatedAfter = $since ?? $readerSettings['last_synced_at'] ?? null;
        $latestUpdate = $updatedAfter ? CarbonImmutable::parse($updatedAfter) : null;

        $requestCount = 0;
        $requestStartTime = now();

        try {
            do {
                if ($requestCount >= (20 - self::RATE_LIMIT_BUFFER)) {
                    $elapsed = now()->diffInSeconds($requestStartTime);
                    if ($elapsed < 60) {
                        $stats['rate_limited'] = true;
                        Log::info('Readwise Reader: Rate limit buffer reached, stopping import', [
                            'requests_made' => $requestCount,
                            'elapsed_seconds' => $elapsed,
                        ]);
                        break;
                    }

                    $requestCount = 0;
                    $requestStartTime = now();
                }

                $response = $this->client->fetchReaderDocuments($token, array_filter([
                    'pageCursor' => $currentCursor,
                    'updatedAfter' => $updatedAfter,
                    'page_size' => 100,
                ]));

                $requestCount++;

                $documents = $response['results'] ?? [];
                $stats['documents_total'] += count($documents);

                foreach ($documents as $document) {
                    $documentId = Arr::get($document, 'id');
                    if (! $documentId) {
                        $stats['documents_skipped']++;

                        continue;
                    }

                    if (Arr::get($document, 'parent_id')) {
                        $stats['documents_skipped']++;

                        continue;
                    }

                    $savedAt = $this->parseTimestamp(Arr::get($document, 'saved_at'))
                        ?? $this->parseTimestamp(Arr::get($document, 'created_at'))
                        ?? CarbonImmutable::now();

                    $updatedAt = $this->parseTimestamp(Arr::get($document, 'updated_at')) ?? $savedAt;
                    if (! $latestUpdate || $updatedAt->greaterThan($latestUpdate)) {
                        $latestUpdate = $updatedAt;
                    }

                    if ($dryRun) {
                        $stats['documents_imported']++;

                        continue;
                    }

                    $this->db->transaction(function () use ($documentId, $document, $savedAt, $updatedAt) {
                        $fragment = Fragment::query()->firstOrNew([
                            'metadata->readwise_reader_id' => $documentId,
                        ]);

                        $title = Arr::get($document, 'title', 'Untitled');
                        $summary = trim((string) Arr::get($document, 'summary', ''));
                        $notes = trim((string) Arr::get($document, 'notes', ''));

                        $message = $summary;
                        if ($notes !== '') {
                            $message .= ($message ? "\n\n" : '')."Notes: {$notes}";
                        }

                        $fragment->fill([
                            'message' => $message ?: $title,
                            'title' => Str::limit($title, 120),
                            'type' => 'log',
                            'source' => 'Readwise Reader',
                            'source_key' => 'readwise-reader',
                        ]);

                        $fragment->tags = collect(Arr::get($document, 'tags', []))
                            ->map(fn ($tag) => is_array($tag) ? ($tag['name'] ?? null) : $tag)
                            ->filter()
                            ->values()
                            ->all();

                        $fragment->metadata = array_merge($fragment->metadata ?? [], [
                            'readwise_reader_id' => $documentId,
                            'readwise_reader_url' => Arr::get($document, 'url'),
                            'author' => Arr::get($document, 'author'),
                            'category' => Arr::get($document, 'category'),
                            'location' => Arr::get($document, 'location'),
                            'site_name' => Arr::get($document, 'site_name'),
                            'word_count' => Arr::get($document, 'word_count'),
                            'reading_progress' => Arr::get($document, 'reading_progress'),
                            'published_date' => Arr::get($document, 'published_date'),
                        ]);

                        $fragment->relationships = array_merge($fragment->relationships ?? [], array_filter([
                            'source_url' => Arr::get($document, 'source_url'),
                            'image_url' => Arr::get($document, 'image_url'),
                        ]));

                        $fragment->setAttribute('created_at', $savedAt);
                        $fragment->setAttribute('updated_at', $updatedAt);
                        $fragment->save();
                    });

                    $stats['documents_imported']++;
                }

                $currentCursor = $response['nextPageCursor'] ?? null;
            } while ($currentCursor);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 429) {
                $stats['rate_limited'] = true;
                Log::warning('Readwise Reader: Rate limit hit', [
                    'message' => $e->getMessage(),
                    'documents_imported' => $stats['documents_imported'],
                ]);
            } else {
                throw $e;
            }
        }

        if (! $dryRun) {
            $settings['integrations'] = $settings['integrations'] ?? [];
            $settings['integrations']['readwise'] = $settings['integrations']['readwise'] ?? [];
            $settings['integrations']['readwise']['reader'] = array_merge(
                $settings['integrations']['readwise']['reader'] ?? [],
                [
                    'last_synced_at' => optional($latestUpdate)->toIso8601String(),
                    'next_cursor' => $currentCursor,
                ]
            );

            $user->update(['profile_settings' => $settings]);
        }

        $stats['last_cursor'] = $currentCursor;
        $stats['last_updated_at'] = optional($latestUpdate)->toIso8601String();

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
            Log::warning('Failed to parse Readwise Reader timestamp', ['value' => $value, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
