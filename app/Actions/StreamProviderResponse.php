<?php

namespace App\Actions;

use Illuminate\Http\Client\Response;

class StreamProviderResponse
{
    public function __invoke(Response $response, string $provider, callable $onDelta, callable $onComplete): array
    {
        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $finalMessage = '';
        $providerResponse = null;

        while (! $body->eof()) {
            $chunk = $body->read(8192);
            if ($chunk === '') {
                usleep(50_000);

                continue;
            }
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '') {
                    continue;
                }

                $json = json_decode($line, true);
                if (! is_array($json)) {
                    continue;
                }

                // Handle streaming delta content
                if ($this->hasStreamingContent($json, $provider)) {
                    $delta = $this->extractStreamingContent($json, $provider);
                    $finalMessage .= $delta;
                    $onDelta($delta);
                }

                // Handle completion
                if ($this->isStreamComplete($json, $provider)) {
                    $providerResponse = $json;
                    $onComplete();
                    break;
                }
            }
        }

        return [
            'final_message' => $finalMessage,
            'provider_response' => $providerResponse,
        ];
    }

    private function hasStreamingContent(array $json, string $provider): bool
    {
        return match ($provider) {
            'ollama' => isset($json['message']['content']),
            'openai' => isset($json['choices'][0]['delta']['content']),
            'anthropic' => isset($json['delta']['text']),
            default => false,
        };
    }

    private function extractStreamingContent(array $json, string $provider): string
    {
        return match ($provider) {
            'ollama' => $json['message']['content'],
            'openai' => $json['choices'][0]['delta']['content'],
            'anthropic' => $json['delta']['text'],
            default => '',
        };
    }

    private function isStreamComplete(array $json, string $provider): bool
    {
        return match ($provider) {
            'ollama' => ($json['done'] ?? false) === true,
            'openai' => isset($json['choices'][0]['finish_reason']),
            'anthropic' => ($json['type'] ?? '') === 'message_stop',
            default => false,
        };
    }
}
