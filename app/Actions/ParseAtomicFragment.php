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

        // Check if message starts with an explicit type prefix (word followed by colon)
        if (preg_match('/^(\w+):\s+(.*)$/s', trim($fragment->message), $matches)) {
            // Explicit type specified (e.g., "todo: pick up laundry")
            $type = strtolower(trim($matches[1]));
            $body = trim($matches[2]);
            
            Log::debug('ParseAtomicFragment - Explicit type found', [
                'type' => $type,
                'body' => $body,
            ]);
        } else {
            // No explicit type, use full message and let AI infer the type later
            $type = 'note'; // Default, will be overridden by AI inference
            $body = trim($fragment->message);
            
            Log::debug('ParseAtomicFragment - No explicit type, using full message', [
                'body' => $body,
            ]);
        }

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

        // Find type by value and set both type string and type_id
        $typeModel = \App\Models\Type::where('value', $type)->first();
        if ($typeModel) {
            $fragment->type = $type;
            $fragment->type_id = $typeModel->id;
        } else {
            // Default to log if type doesn't exist
            $logType = \App\Models\Type::where('value', 'log')->first();
            $fragment->type = 'log';
            $fragment->type_id = $logType?->id;
        }
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
