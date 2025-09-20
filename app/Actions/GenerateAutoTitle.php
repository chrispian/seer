<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Models\Type;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateAutoTitle
{
    public function handle(Fragment $fragment, $next)
    {
        $fragment = $this->__invoke($fragment);

        return $next($fragment);
    }

    public function __invoke(Fragment $fragment): Fragment
    {
        Log::debug('GenerateAutoTitle::invoke()');

        // Skip if title already exists
        if (! empty($fragment->title)) {
            return $fragment;
        }

        $message = $fragment->message;
        $title = '';
        $usedFirstLine = false;
        $usedFirstSentence = false;

        // Strategy 1: First line if ≤80 characters
        $lines = explode("\n", $message);
        $firstLine = trim($lines[0] ?? '');

        if (strlen($firstLine) <= 80 && ! empty($firstLine)) {
            $title = $firstLine;
            $usedFirstLine = true;
        } else {
            // Strategy 2: First sentence if ≤100 characters
            preg_match('/^[^.!?]+[.!?]/', $message, $sentenceMatch);
            $firstSentence = trim($sentenceMatch[0] ?? '');

            if (strlen($firstSentence) <= 100 && ! empty($firstSentence)) {
                $title = $firstSentence;
                $usedFirstSentence = true;
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

        $typeValue = $this->resolveTypeValue($fragment);
        if (! $typeValue || $typeValue === 'note') {
            $lowerMessage = strtolower($message);
            $tagSet = array_map('strtolower', $fragment->tags ?? []);

            if (str_contains($lowerMessage, 'reminder') ||
                str_contains($lowerMessage, 'task') ||
                in_array('urgent', $tagSet, true)) {
                $typeValue = 'task';
            }
        }
        if ($typeValue) {
            $normalizedType = Str::title(strtolower($typeValue));

            if (! ($normalizedType === 'Note' && $usedFirstLine)) {
                if (! Str::startsWith(Str::lower($title), Str::lower($normalizedType))) {
                    $title = trim($normalizedType.': '.$title);
                }
            }
        }

        $fragment->setAttribute('title', $title);
        $fragment->save();

        return $fragment;
    }

    private function generateKeywordTitle(Fragment $fragment): string
    {
        $keywords = [];

        // Start with fragment type
        $type = $this->resolveTypeValue($fragment);
        if ($type) {
            $keywords[] = Str::title($type);
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
        $people = $metadata['people'] ?? ($metadata['entities']['people'] ?? []);
        if (! empty($people)) {
            $topPeople = array_slice($people, 0, 2);
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

    private function resolveTypeValue(Fragment $fragment): ?string
    {
        $rawAttributes = $fragment->getAttributes();
        $storedType = $rawAttributes['type'] ?? null;
        if (is_string($storedType) && $storedType !== '') {
            return $storedType;
        }

        $attributeType = $fragment->getAttributeValue('type');
        if (is_string($attributeType) && $attributeType !== '') {
            return $attributeType;
        }

        $rawType = $fragment->getRawOriginal('type');
        if (is_string($rawType) && $rawType !== '') {
            return $rawType;
        }

        $relation = $fragment->getRelationValue('type');
        if ($relation instanceof Type) {
            return $relation->value ?? $relation->name ?? null;
        }

        $typeProperty = $fragment->type;
        if ($typeProperty instanceof Type) {
            return $typeProperty->value ?? $typeProperty->name ?? null;
        }

        if (is_object($typeProperty) && isset($typeProperty->value)) {
            return $typeProperty->value;
        }

        if (is_string($typeProperty) && $typeProperty !== '') {
            return $typeProperty;
        }

        return null;
    }
}
