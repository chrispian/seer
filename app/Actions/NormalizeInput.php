<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;

class NormalizeInput
{
    public function __invoke(string $input): array
    {
        Log::debug('NormalizeInput::invoke()');

        $normalized = $this->normalizeText($input);
        $hash = $this->generateHash($normalized);
        $bucket = $this->generateTimeBucket();

        Log::debug('Input normalized', [
            'original_length' => strlen($input),
            'normalized_length' => strlen($normalized),
            'hash' => $hash,
            'bucket' => $bucket,
        ]);

        return [
            'original' => $input,
            'normalized' => $normalized,
            'hash' => $hash,
            'bucket' => $bucket,
        ];
    }

    private function normalizeText(string $input): string
    {
        // Trim whitespace
        $text = trim($input);

        // Normalize line endings first (before whitespace collapse)
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove zero-width characters (replace with space to avoid word collision)
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $text);

        // Collapse multiple whitespace into single spaces (after line endings)
        $text = preg_replace('/\s+/', ' ', $text);

        // Normalize URLs to lowercase (domain only, preserve query params)
        $text = preg_replace_callback(
            '/(https?:\/\/[^\/\s]+)/i',
            fn ($matches) => strtolower($matches[1]),
            $text
        );

        return $text;
    }

    private function generateHash(string $normalizedText): string
    {
        return hash('sha256', $normalizedText);
    }

    private function generateTimeBucket(): int
    {
        // 10-minute buckets: floor(timestamp / 600)
        return intval(floor(time() / 600));
    }
}
