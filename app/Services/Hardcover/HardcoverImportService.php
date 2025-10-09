<?php

namespace App\Services\Hardcover;

use App\Models\Fragment;
use App\Models\Source;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HardcoverImportService
{
    private const RATE_LIMIT_BUFFER = 10;

    private const MAX_REQUESTS_PER_DAY = 500;

    private const STATUS_MAP = [
        1 => 'want_to_read',
        2 => 'currently_reading',
        3 => 'read',
        4 => 'did_not_finish',
    ];

    public function __construct(
        private readonly HardcoverApiClient $client,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Import Hardcover books with rate limit handling.
     *
     * @return array<string, int|bool|string|null>
     */
    public function import(?int $offset = null, bool $dryRun = false): array
    {
        $user = User::query()->firstOrFail();
        $settings = $user->profile_settings ?? [];
        $hardcoverSettings = $settings['integrations']['hardcover'] ?? [];

        $tokenEncrypted = $hardcoverSettings['bearer_token'] ?? null;
        if (empty($tokenEncrypted)) {
            throw new \RuntimeException('Hardcover bearer token is not configured.');
        }

        $token = Crypt::decryptString($tokenEncrypted);

        Source::query()->firstOrCreate(
            ['key' => 'hardcover'],
            ['label' => 'Hardcover', 'meta' => []]
        );

        $stats = [
            'books_total' => 0,
            'books_imported' => 0,
            'books_skipped' => 0,
            'dry_run' => $dryRun,
            'rate_limited' => false,
            'last_offset' => null,
        ];

        $currentOffset = $offset ?? $hardcoverSettings['next_offset'] ?? 0;
        $userId = $this->client->getUserId($token);

        $requestCount = 1;
        $requestStartTime = now();

        try {
            $totalImported = 0;

            while ($totalImported < self::MAX_REQUESTS_PER_DAY) {
                if ($requestCount >= (60 - self::RATE_LIMIT_BUFFER)) {
                    $elapsed = now()->diffInSeconds($requestStartTime);
                    if ($elapsed < 60) {
                        $stats['rate_limited'] = true;
                        Log::info('Hardcover: Rate limit buffer reached, stopping import', [
                            'requests_made' => $requestCount,
                            'elapsed_seconds' => $elapsed,
                        ]);
                        break;
                    }

                    $requestCount = 0;
                    $requestStartTime = now();
                }

                $response = $this->client->fetchUserBooks($token, $userId, 100, $currentOffset);
                $requestCount++;

                $userBooks = $response['data']['user_books'] ?? [];
                if (empty($userBooks)) {
                    break;
                }

                $stats['books_total'] += count($userBooks);

                foreach ($userBooks as $userBook) {
                    $book = $userBook['book'] ?? null;
                    if (! $book || ! isset($book['id'])) {
                        $stats['books_skipped']++;

                        continue;
                    }

                    $bookId = $book['id'];
                    $title = $book['title'] ?? 'Untitled';
                    $description = $book['description'] ?? '';

                    $authors = [];
                    foreach ($book['contributions'] ?? [] as $contribution) {
                        $authorName = $contribution['author']['name'] ?? null;
                        if ($authorName) {
                            $authors[] = $authorName;
                        }
                    }
                    $authorText = ! empty($authors) ? 'by '.implode(', ', $authors) : '';

                    $message = $title;
                    if ($authorText) {
                        $message .= "\n".$authorText;
                    }
                    if ($description) {
                        $message .= "\n\n".$description;
                    }

                    $statusId = $userBook['status_id'] ?? null;
                    $statusText = self::STATUS_MAP[$statusId] ?? null;

                    $createdAt = $this->parseTimestamp($userBook['created_at']) ?? CarbonImmutable::now();
                    $updatedAt = $this->parseTimestamp($userBook['updated_at']) ?? $createdAt;

                    if ($dryRun) {
                        $stats['books_imported']++;

                        continue;
                    }

                    $this->db->transaction(function () use ($bookId, $book, $userBook, $message, $title, $statusText, $createdAt, $updatedAt) {
                        $fragment = Fragment::query()->firstOrNew([
                            'metadata->hardcover_book_id' => $bookId,
                        ]);

                        $fragment->fill([
                            'message' => $message,
                            'title' => Str::limit($title, 120),
                            'type' => 'log',
                            'source' => 'Hardcover',
                            'source_key' => 'hardcover',
                        ]);

                        $state = [];
                        if ($statusText) {
                            $state['reading_status'] = $statusText;
                        }
                        if (! empty($state)) {
                            $fragment->state = $state;
                        }

                        $fragment->metadata = array_merge($fragment->metadata ?? [], [
                            'hardcover_book_id' => $bookId,
                            'hardcover_edition_id' => $userBook['edition_id'] ?? null,
                            'hardcover_status_id' => $userBook['status_id'] ?? null,
                            'pages' => $book['pages'] ?? null,
                            'release_date' => $book['release_date'] ?? null,
                            'book_object' => $book,
                        ]);

                        $fragment->setAttribute('created_at', $createdAt);
                        $fragment->setAttribute('updated_at', $updatedAt);
                        $fragment->save();
                    });

                    $stats['books_imported']++;
                    $totalImported++;

                    if ($totalImported >= self::MAX_REQUESTS_PER_DAY) {
                        $stats['rate_limited'] = true;
                        Log::info('Hardcover: Daily import limit reached', [
                            'books_imported' => $totalImported,
                        ]);
                        break 2;
                    }
                }

                $currentOffset += 100;
            }
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 429) {
                $stats['rate_limited'] = true;
                Log::warning('Hardcover: Rate limit hit', [
                    'message' => $e->getMessage(),
                    'books_imported' => $stats['books_imported'],
                ]);
            } else {
                throw $e;
            }
        }

        if (! $dryRun) {
            $settings['integrations'] = $settings['integrations'] ?? [];
            $settings['integrations']['hardcover'] = array_merge(
                $settings['integrations']['hardcover'] ?? [],
                [
                    'last_synced_at' => now()->toIso8601String(),
                    'next_offset' => $currentOffset,
                ]
            );

            $user->update(['profile_settings' => $settings]);
        }

        $stats['last_offset'] = $currentOffset;

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
            Log::warning('Failed to parse Hardcover timestamp', ['value' => $value, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
