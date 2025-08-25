<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParseAtomicFragment
{
    public function __invoke(Fragment $fragment): Fragment
    {

        Log::debug('ParseAtomicFragment::invoke()', [
            'original_message' => $fragment->message,
        ]);

        // Match `type` as first word (with optional colon)
        preg_match('/^(\w+):?\s+(.*)$/s', trim($fragment->message), $matches);
        $type = strtolower(trim($matches[1] ?? 'note'));
        $body = trim($matches[2] ?? $fragment->message); // Fallback to full message if no match

        Log::debug('ParseAtomicFragment - Type extraction', [
            'type' => $type,
            'body' => $body,
        ]);

        // Enhanced tag extraction with normalization to kebab-case
        preg_match_all('/#([\w\-]+)/', $body, $tagMatches);

        Log::debug('ParseAtomicFragment - Tag matches', [
            'raw_matches' => $tagMatches[1] ?? [],
        ]);

        $tags = array_map(function ($tag) {
            // If already contains hyphens, keep as-is (just lowercase)
            if (str_contains($tag, '-')) {
                return strtolower($tag);
            }

            // Otherwise convert to kebab-case
            return Str::kebab(strtolower($tag));
        }, $tagMatches[1] ?? []);

        // Extract @people mentions
        preg_match_all('/@([\w\-\.]+)/', $body, $peopleMatches);
        $people = $peopleMatches[1] ?? [];

        // Extract [links] in square brackets
        preg_match_all('/\[([^\]]+)\]/', $body, $linkMatches);
        $links = $linkMatches[1] ?? [];

        // Extract URLs
        preg_match_all('/(https?:\/\/[^\s]+)/', $body, $urlMatches);
        $urls = $urlMatches[1] ?? [];

        // Clean message (remove tags only, keep mentions, links and URLs for readability)
        $cleanMessage = preg_replace('/#[\w\-]+/', '', $body);

        Log::debug('ParseAtomicFragment - Cleaning', [
            'clean_message' => $cleanMessage,
            'tags_to_save' => array_unique($tags),
        ]);

        $fragment->type = $type;
        $fragment->tags = array_unique($tags);
        $fragment->message = trim($cleanMessage);

        // Store extracted entities in metadata
        $metadata = $fragment->metadata ?? [];
        $metadata['people'] = array_unique($people);
        $metadata['links'] = array_unique($links);
        $metadata['urls'] = array_unique($urls);
        $fragment->metadata = $metadata;

        return tap($fragment)->save();
    }
}
