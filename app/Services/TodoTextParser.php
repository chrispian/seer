<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

class TodoTextParser
{
    /**
     * Parse todo text into structured data
     */
    public function parse(string $text): array
    {
        $text = trim($text);
        
        if (empty($text)) {
            throw new \InvalidArgumentException('Todo text cannot be empty');
        }

        // Extract components in order to avoid conflicts
        $tags = $this->extractTags($text);
        $priority = $this->extractPriority($text);
        $dueDate = $this->extractDueDate($text);
        
        // Remove extracted metadata to get clean title
        $cleanText = $this->removeMetadata($text, $tags, $priority, $dueDate);
        $title = $this->extractTitle($cleanText);
        
        return [
            'title' => $title,
            'description' => $text, // Keep original for context
            'due_date' => $dueDate?->toISOString(),
            'priority' => $priority,
            'tags' => $tags,
            'status' => 'open', // Default status
        ];
    }

    /**
     * Extract due date from text using various patterns
     */
    public function extractDueDate(string $text): ?Carbon
    {
        $text = strtolower($text);
        
        // Specific date patterns (ISO format)
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $text, $matches)) {
            try {
                return Carbon::parse($matches[1]);
            } catch (\Exception $e) {
                // Invalid date format, continue with other patterns
            }
        }
        
        // Relative date patterns
        $relativeDates = [
            'today' => 0,
            'tomorrow' => 1,
            'tmr' => 1,
            'next week' => 7,
            'next month' => 30,
        ];
        
        foreach ($relativeDates as $pattern => $days) {
            if (str_contains($text, $pattern)) {
                return Carbon::now()->addDays($days);
            }
        }
        
        // Day of week patterns
        $daysOfWeek = [
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
            'mon' => Carbon::MONDAY,
            'tue' => Carbon::TUESDAY,
            'wed' => Carbon::WEDNESDAY,
            'thu' => Carbon::THURSDAY,
            'fri' => Carbon::FRIDAY,
            'sat' => Carbon::SATURDAY,
            'sun' => Carbon::SUNDAY,
        ];
        
        foreach ($daysOfWeek as $day => $dayOfWeek) {
            if (str_contains($text, $day)) {
                return Carbon::now()->next($dayOfWeek);
            }
        }
        
        // Time-based relative patterns
        if (preg_match('/in (\d+) (hour|day|week)s?/', $text, $matches)) {
            $amount = (int) $matches[1];
            $unit = $matches[2];
            
            switch ($unit) {
                case 'hour':
                    return Carbon::now()->addHours($amount);
                case 'day':
                    return Carbon::now()->addDays($amount);
                case 'week':
                    return Carbon::now()->addWeeks($amount);
            }
        }
        
        return null;
    }

    /**
     * Extract priority from text using various patterns
     */
    public function extractPriority(string $text): string
    {
        $text = strtolower($text);
        
        // Explicit priority patterns
        $priorityPatterns = [
            'urgent' => 'urgent',
            'critical' => 'urgent',
            'asap' => 'urgent',
            'high priority' => 'high',
            'high' => 'high',
            'important' => 'high',
            'low priority' => 'low',
            'low' => 'low',
            'minor' => 'low',
        ];
        
        foreach ($priorityPatterns as $pattern => $priority) {
            if (str_contains($text, $pattern)) {
                return $priority;
            }
        }
        
        // Symbol-based priority
        if (str_contains($text, '!!!')) {
            return 'urgent';
        }
        if (str_contains($text, '!!')) {
            return 'high';
        }
        if (str_contains($text, '!')) {
            return 'medium';
        }
        
        // P1/P2/P3 style priority
        if (preg_match('/p([123])/i', $text, $matches)) {
            $level = (int) $matches[1];
            return match($level) {
                1 => 'urgent',
                2 => 'high',
                3 => 'medium',
                default => 'medium'
            };
        }
        
        return 'medium'; // Default priority
    }

    /**
     * Extract tags from text (both # and @ style)
     */
    public function extractTags(string $text): array
    {
        $tags = [];
        
        // Extract #hashtags
        if (preg_match_all('/#([a-zA-Z0-9_-]+)/', $text, $matches)) {
            $tags = array_merge($tags, $matches[1]);
        }
        
        // Extract @context tags
        if (preg_match_all('/@([a-zA-Z0-9_-]+)/', $text, $matches)) {
            $tags = array_merge($tags, array_map(fn($tag) => "ctx-{$tag}", $matches[1]));
        }
        
        // Add 'todo' tag by default
        if (!in_array('todo', $tags)) {
            $tags[] = 'todo';
        }
        
        return array_unique($tags);
    }

    /**
     * Extract clean title from text after removing metadata
     */
    public function extractTitle(string $text): string
    {
        $text = trim($text);
        
        // Remove common todo prefixes
        $prefixes = ['todo:', 'task:', 'do:', 'reminder:'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($text), $prefix)) {
                $text = trim(substr($text, strlen($prefix)));
                break;
            }
        }
        
        // Take first sentence or up to 80 characters
        $sentences = preg_split('/[.!?]/', $text);
        $title = trim($sentences[0]);
        
        if (strlen($title) > 80) {
            $title = Str::limit($title, 80);
        }
        
        return $title ?: 'Untitled Todo';
    }

    /**
     * Remove extracted metadata from text to get clean title
     */
    protected function removeMetadata(string $text, array $tags, string $priority, ?Carbon $dueDate): string
    {
        // Remove hashtags and context tags
        $text = preg_replace('/#[a-zA-Z0-9_-]+/', '', $text);
        $text = preg_replace('/@[a-zA-Z0-9_-]+/', '', $text);
        
        // Remove priority keywords
        $priorityWords = ['urgent', 'critical', 'asap', 'high priority', 'high', 'important', 'low priority', 'low', 'minor'];
        foreach ($priorityWords as $word) {
            $text = str_ireplace($word, '', $text);
        }
        
        // Remove priority symbols
        $text = preg_replace('/!+/', '', $text);
        $text = preg_replace('/p[123]/i', '', $text);
        
        // Remove date/time expressions
        $datePatterns = [
            '/\d{4}-\d{2}-\d{2}/',
            '/\b(today|tomorrow|tmr|next week|next month)\b/i',
            '/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday|mon|tue|wed|thu|fri|sat|sun)\b/i',
            '/\bin \d+ (hour|day|week)s?\b/i',
        ];
        
        foreach ($datePatterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }
        
        // Clean up extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Get parser statistics for monitoring
     */
    public function getParserStats(): array
    {
        return [
            'patterns_supported' => [
                'due_dates' => ['today', 'tomorrow', 'next week', 'YYYY-MM-DD', 'day names', 'relative time'],
                'priorities' => ['urgent', 'high', 'medium', 'low', '!!!', 'P1/P2/P3'],
                'tags' => ['#hashtags', '@context'],
            ],
            'default_values' => [
                'priority' => 'medium',
                'status' => 'open',
                'due_date' => null,
            ],
        ];
    }
}