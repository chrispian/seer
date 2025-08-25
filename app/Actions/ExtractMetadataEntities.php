<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class ExtractMetadataEntities
{
    public function __invoke(Fragment $fragment): Fragment
    {
        Log::debug('ExtractMetadataEntities::invoke()');

        $message = $fragment->message;
        $entities = [];

        // Extract @mentions (people)
        preg_match_all('/@([\w\-\.]+)/', $message, $peopleMatches);
        $entities['people'] = array_unique($peopleMatches[1] ?? []);

        // Extract #hashtags (already handled in ParseAtomicFragment but included for completeness)
        preg_match_all('/#([\w\-]+)/', $message, $tagMatches);
        $entities['hashtags'] = array_unique($tagMatches[1] ?? []);

        // Extract [bracketed links/references]
        preg_match_all('/\[([^\]]+)\]/', $message, $linkMatches);
        $entities['references'] = array_unique($linkMatches[1] ?? []);

        // Extract URLs
        preg_match_all('/(https?:\/\/[^\s]+)/', $message, $urlMatches);
        $entities['urls'] = array_unique($urlMatches[1] ?? []);

        // Extract emails
        preg_match_all('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $message, $emailMatches);
        $entities['emails'] = array_unique($emailMatches[1] ?? []);

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
        $entities['dates'] = array_unique($dates);

        // Extract phone numbers
        preg_match_all('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $message, $phoneMatches);
        $entities['phones'] = array_unique($phoneMatches[0] ?? []);

        // Extract code blocks (backtick wrapped)
        preg_match_all('/`([^`]+)`/', $message, $codeMatches);
        $entities['code_snippets'] = array_unique($codeMatches[1] ?? []);

        // Store in parsed_entities column
        $fragment->parsed_entities = $entities;

        // Also merge with existing metadata for backward compatibility
        $metadata = $fragment->metadata ?? [];
        $metadata['entities'] = $entities;
        $fragment->metadata = $metadata;

        return tap($fragment)->save();
    }
}
