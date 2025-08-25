<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateAutoTitle
{
    public function __invoke(Fragment $fragment): Fragment
    {
        Log::debug('GenerateAutoTitle::invoke()');

        // Skip if title already exists
        if (! empty($fragment->title)) {
            return $fragment;
        }

        $message = $fragment->message;
        $title = '';

        // Strategy 1: First line if ≤80 characters
        $lines = explode("\n", $message);
        $firstLine = trim($lines[0] ?? '');

        if (strlen($firstLine) <= 80 && ! empty($firstLine)) {
            $title = $firstLine;
        } else {
            // Strategy 2: First sentence if ≤100 characters
            preg_match('/^[^.!?]+[.!?]/', $message, $sentenceMatch);
            $firstSentence = trim($sentenceMatch[0] ?? '');

            if (strlen($firstSentence) <= 100 && ! empty($firstSentence)) {
                $title = $firstSentence;
            } else {
                // Strategy 3: Keyword fallback
                $title = $this->generateKeywordTitle($fragment);
            }
        }

        // Clean up the title
        $title = $this->cleanTitle($title);

        // Ensure title doesn't exceed 255 characters
        if (strlen($title) > 255) {
            $title = Str::limit($title, 252, '...');
        }

        $fragment->title = $title;

        return tap($fragment)->save();
    }

    private function generateKeywordTitle(Fragment $fragment): string
    {
        $keywords = [];

        // Start with fragment type
        if ($fragment->type && $fragment->type !== 'note') {
            $keywords[] = ucfirst($fragment->type);
        }

        // Add tags
        if (! empty($fragment->tags)) {
            $topTags = array_slice($fragment->tags, 0, 3);
            foreach ($topTags as $tag) {
                $keywords[] = '#'.$tag;
            }
        }

        // Add people mentions if available
        $metadata = $fragment->metadata ?? [];
        if (! empty($metadata['people'])) {
            $topPeople = array_slice($metadata['people'], 0, 2);
            foreach ($topPeople as $person) {
                $keywords[] = '@'.$person;
            }
        }

        // If still no keywords, use truncated message
        if (empty($keywords)) {
            return Str::limit($fragment->message, 80, '...');
        }

        // Combine keywords with fragment preview
        $preview = Str::words($fragment->message, 10, '...');

        return implode(' ', $keywords).': '.$preview;
    }

    private function cleanTitle(string $title): string
    {
        // Remove excessive whitespace
        $title = preg_replace('/\s+/', ' ', $title);

        // Remove markdown formatting
        $title = preg_replace('/[*_`~]/', '', $title);

        // Remove URLs for cleaner titles
        $title = preg_replace('/(https?:\/\/[^\s]+)/', '[link]', $title);

        // Trim and return
        return trim($title);
    }
}
