<?php

namespace App\Services\Hardcover;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HardcoverApiClient
{
    private const BASE_URL = 'https://api.hardcover.app/v1/graphql';

    public function __construct() {}

    /**
     * Get the current user's ID.
     *
     * @param  string  $token  Bearer token (plain text)
     * @return int
     *
     * @throws \RuntimeException
     */
    public function getUserId(string $token): int
    {
        $query = '{
  me {
    id
    username
  }
}';

        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json',
        ])->post(self::BASE_URL, [
            'query' => $query,
        ]);

        if ($response->failed()) {
            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 60);
                throw new \RuntimeException("Rate limit exceeded. Retry after {$retryAfter} seconds.", 429);
            }

            $message = $response->json('errors.0.message') ?? $response->body();
            throw new \RuntimeException('Hardcover API request failed: '.Str::limit((string) $message, 200));
        }

        $data = $response->json();
        $userId = $data['data']['me'][0]['id'] ?? null;

        if (! $userId) {
            throw new \RuntimeException('Unable to retrieve user ID from Hardcover API');
        }

        return $userId;
    }

    /**
     * Fetch user's books from Hardcover.
     *
     * @param  string  $token  Bearer token (plain text)
     * @param  int  $userId  User ID from getUserId()
     * @param  int  $limit  Number of books per page
     * @param  int  $offset  Offset for pagination
     * @return array
     *
     * @throws \RuntimeException
     */
    public function fetchUserBooks(string $token, int $userId, int $limit = 100, int $offset = 0): array
    {
        $query = '{
  user_books(
    where: {user_id: {_eq: '.$userId.'}}
    limit: '.$limit.'
    offset: '.$offset.'
    order_by: {updated_at: desc}
  ) {
    status_id
    edition_id
    book_id
    created_at
    updated_at
    book {
      id
      title
      description
      pages
      release_date
      cached_contributors
      contributions {
        author {
          name
        }
      }
    }
  }
}';

        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json',
        ])->post(self::BASE_URL, [
            'query' => $query,
        ]);

        if ($response->failed()) {
            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 60);
                throw new \RuntimeException("Rate limit exceeded. Retry after {$retryAfter} seconds.", 429);
            }

            $errors = $response->json('errors') ?? [];
            $message = $errors[0]['message'] ?? $response->body();
            throw new \RuntimeException('Hardcover API request failed: '.Str::limit((string) $message, 200));
        }

        return $response->json();
    }
}
