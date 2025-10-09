<?php

namespace App\Services\Readwise;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ReadwiseApiClient
{
    private const BASE_URL = 'https://readwise.io/api/v2';

    public function __construct()
    {
        //
    }

    /**
     * Fetch highlights from Readwise.
     *
     * @param  string  $token  API token (plain text)
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function fetchHighlights(string $token, array $options = []): array
    {
        $query = Arr::only($options, ['pageCursor', 'updatedAfter', 'page_size']);

        $response = Http::withHeaders([
            'Authorization' => 'Token '.$token,
            'Accept' => 'application/json',
        ])->get(self::BASE_URL.'/export/', $query);

        if ($response->failed()) {
            $message = $response->json('detail') ?? $response->body();
            throw new \RuntimeException('Readwise API request failed: '.Str::limit((string) $message, 200));
        }

        return $response->json();
    }

    /**
     * Fetch Reader documents from Readwise.
     *
     * @param  string  $token  API token (plain text)
     * @param  array<string, mixed>  $options
     * @return array{results: array, nextPageCursor: ?string, count: int}
     *
     * @throws \RuntimeException
     */
    public function fetchReaderDocuments(string $token, array $options = []): array
    {
        $query = Arr::only($options, ['pageCursor', 'updatedAfter', 'location', 'category', 'page_size']);

        $response = Http::withHeaders([
            'Authorization' => 'Token '.$token,
            'Accept' => 'application/json',
        ])->get('https://readwise.io/api/v3/list/', $query);

        if ($response->failed()) {
            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 60);
                throw new \RuntimeException("Rate limit exceeded. Retry after {$retryAfter} seconds.", 429);
            }

            $message = $response->json('detail') ?? $response->body();
            throw new \RuntimeException('Readwise Reader API request failed: '.Str::limit((string) $message, 200));
        }

        return $response->json();
    }
}
