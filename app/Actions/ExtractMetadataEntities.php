<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExtractMetadataEntities
{
    public function handle(Fragment $fragment, $next)
    {
        $fragment = $this->__invoke($fragment);

        return $next($fragment);
    }

    public function __invoke(Fragment $fragment): Fragment
    {
        Log::debug('ExtractMetadataEntities::invoke()');

        $message = $fragment->message;
        $entities = [];

        // Extract @mentions (people)
        preg_match_all('/@([\w\-\.]+)/', $message, $peopleMatches);
        $people = $peopleMatches[1] ?? [];

        // Extract capitalized names from contextual phrases (e.g., "Call John")
        preg_match_all('/\b(?:call|meet(?:ing)? with|ping|email|contact|follow up with)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/i', $message, $contextualNameMatches);
        $people = array_merge($people, $contextualNameMatches[1] ?? []);

        // Extract standalone proper nouns that look like names (two consecutive capitalized words)
        preg_match_all('/\b([A-Z][a-z]+\s+[A-Z][a-z]+)\b/', $message, $properNameMatches);
        $people = array_merge($people, $properNameMatches[1] ?? []);

        $people = array_merge($people, $this->extractCapitalizedNames($message));

        $entities['people'] = $this->uniqueValues($people);

        // Extract #hashtags (already handled in ParseAtomicFragment but included for completeness)
        preg_match_all('/#([\w\-]+)/', $message, $tagMatches);
        $entities['hashtags'] = $this->uniqueValues($tagMatches[1] ?? []);

        // Extract [bracketed links/references]
        preg_match_all('/\[([^\]]+)\]/', $message, $linkMatches);
        $entities['references'] = $this->uniqueValues($linkMatches[1] ?? []);

        // Extract URLs
        preg_match_all('/(https?:\/\/[^\s]+)/', $message, $urlMatches);
        $entities['urls'] = $this->uniqueValues($urlMatches[1] ?? []);

        // Extract emails
        preg_match_all('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $message, $emailMatches);
        $entities['emails'] = $this->uniqueValues($emailMatches[1] ?? []);

        // Extract dates in various formats
        $datePatterns = [
            '/\b(\d{4}-\d{2}-\d{2})\b/', // YYYY-MM-DD
            '/\b(\d{1,2}\/\d{1,2}\/\d{2,4})\b/', // MM/DD/YYYY or M/D/YY
            '/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2},?\s+\d{4}\b/i', // Month DD, YYYY
        ];

        $dates = [];
        foreach ($datePatterns as $pattern) {
            preg_match_all($pattern, $message, $dateMatches);
            if (! empty($dateMatches[0])) {
                $dates = array_merge($dates, $dateMatches[0]);
            }
        }
        $entities['dates'] = $this->uniqueValues($dates);

        // Extract phone numbers
        preg_match_all('/\+?\d[\d\s\-\(\)]{6,}/', $message, $phoneMatches);
        $phones = $phoneMatches[0] ?? [];

        $normalizedPhones = [];
        foreach ($phones as $phone) {
            $normalizedPhones[] = $phone;

            $normalized = $this->normalizePhoneNumber($phone);
            if ($normalized) {
                $normalizedPhones[] = $normalized;
            }
        }

        $entities['phones'] = $this->uniqueValues(array_filter($normalizedPhones, function ($value) {
            $trimmed = trim((string) $value);

            return $trimmed !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed);
        }));

        // Extract code blocks (backtick wrapped)
        preg_match_all('/`([^`]+)`/', $message, $codeMatches);
        $entities['code_snippets'] = $this->uniqueValues($codeMatches[1] ?? []);

        // Merge with existing parsed entities
        $fragment->parsed_entities = $this->mergeEntityArrays($fragment->parsed_entities ?? [], $entities);

        // Also merge with existing metadata for backward compatibility
        $metadata = $fragment->metadata ?? [];
        $metadataEntities = is_array($metadata['entities'] ?? null) ? $metadata['entities'] : [];
        $metadata['entities'] = $this->mergeEntityArrays($metadataEntities, $entities);
        $fragment->metadata = $metadata;

        return tap($fragment)->save();
    }

    private function uniqueValues(array $values): array
    {
        $filtered = array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : $value, $values), function ($value) {
            if ($value === null || $value === '') {
                return false;
            }

            return ! (is_string($value) && Str::length($value) === 0);
        });

        return array_values(array_unique($filtered));
    }

    private function mergeEntityArrays(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $values) {
            $existingValues = $existing[$key] ?? [];

            if (! is_array($existingValues)) {
                $existingValues = [$existingValues];
            }

            $merged = $this->uniqueValues(array_merge($existingValues, $values));
            $existing[$key] = $merged;
        }

        return $existing;
    }

    private function extractCapitalizedNames(string $message): array
    {
        preg_match_all('/\b([A-Z][a-z]+)\b/', $message, $matches);

        $stopWords = [
            'call', 'email', 'check', 'follow', 'meeting', 'need', 'presentation', 'important', 'team', 'client',
        ];

        $names = [];
        foreach ($matches[1] as $candidate) {
            if (! in_array(strtolower($candidate), $stopWords, true)) {
                $names[] = $candidate;
            }
        }

        return $names;
    }

    private function normalizePhoneNumber(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        if (strlen($digits) !== 11) {
            return null;
        }

        return sprintf(
            '+%s-%s-%s-%s',
            substr($digits, 0, 1),
            substr($digits, 1, 3),
            substr($digits, 4, 3),
            substr($digits, 7)
        );
    }
}
