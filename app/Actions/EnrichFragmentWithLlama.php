<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichFragmentWithLlama
{
    public function __invoke(Fragment $fragment): ?Fragment
    {

        Log::debug('EnrichFragmentWithLlama::invoke()');

        $prompt = <<<PROMPT
Given the following user input, return a structured fragment in JSON.

Input:
{$fragment->message}

Output format:
{
  "type": "log",
  "message": "...",
  "tags": ["tag1", "tag2"],
  "metadata": {
    "confidence": 0.9
  },
  "state": {
    "status": "open"
  },
  "vault": "default"
}
Only return JSON. No markdown, no explanation.
PROMPT;

        $response = Http::timeout(20)->post('http://localhost:11434/api/generate', [
            'model' => 'llama3',
            'prompt' => $prompt,
            'stream' => false,
        ]);

        if (! $response->ok()) {
            Log::error('LLaMA failed', ['fragment_id' => $fragment->id]);
            return null;
        }

        $raw = $response->json('response');

        $cleanJson = preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $raw, $matches)
            ? $matches[1]
            : (str_starts_with(trim($raw), 'Here') ? explode('```', $raw)[1] ?? $raw : $raw);

        $parsed = json_decode($cleanJson, true);

        if (!is_array($parsed)) {
            Log::error('JSON decode failed', ['raw' => $raw, 'cleanJson' => $cleanJson]);
            return null;
        }

        // Save enrichment
        $fragment->metadata = array_merge((array) $fragment->metadata, [
            'enrichment' => $parsed,
        ]);

        if (!empty($parsed['type'])) {
            // Find type by value and set both type string and type_id
            $typeModel = \App\Models\Type::where('value', $parsed['type'])->first();
            if ($typeModel) {
                $fragment->type = $parsed['type'];
                $fragment->type_id = $typeModel->id;
            }
        }

        $fragment->save();

        return $fragment;
    }
}
