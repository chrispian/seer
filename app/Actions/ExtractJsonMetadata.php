<?php

namespace App\Actions;

class ExtractJsonMetadata
{
    public function handle(array $payload, \Closure $next): array
    {
        $fragment = $payload['fragment'];
        $data = $payload['data'];

        // Extract JSON metadata from the original message
        $originalMessage = $data['message'];
        $jsonMetadata = $this->extractJsonMetadata($originalMessage);

        if ($jsonMetadata['found']) {
            // Update fragment message with cleaned content
            $fragment->update(['message' => $jsonMetadata['cleaned_message']]);

            // Store extracted metadata for next pipeline step
            $payload['json_metadata'] = $jsonMetadata;
        } else {
            $payload['json_metadata'] = ['found' => false];
        }

        return $next($payload);
    }

    /**
     * Extract and parse JSON metadata block from assistant response
     */
    private function extractJsonMetadata(string $message): array
    {
        $pattern = '/<<<JSON_METADATA>>>(.*?)<<<END_JSON_METADATA>>>/s';

        if (! preg_match($pattern, $message, $matches)) {
            return [
                'found' => false,
                'metadata' => null,
                'tags' => null,
                'links' => null,
                'cleaned_message' => $message,
            ];
        }

        $jsonString = trim($matches[1]);
        $decoded = json_decode($jsonString, true);

        // Clean the message by removing all JSON blocks
        $cleanedMessage = preg_replace($pattern, '', $message);
        $cleanedMessage = trim($cleanedMessage);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON parsing failed, return original message
            return [
                'found' => true,
                'metadata' => null,
                'tags' => null,
                'links' => null,
                'cleaned_message' => $cleanedMessage,
            ];
        }

        return [
            'found' => true,
            'metadata' => $decoded['facets'] ?? null,
            'tags' => $decoded['tags'] ?? null,
            'links' => $decoded['links'] ?? null,
            'cleaned_message' => $cleanedMessage,
        ];
    }
}
