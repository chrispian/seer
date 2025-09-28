<?php

namespace App\Actions;

use Illuminate\Http\Client\Response;

class HandleStreamingError
{
    public function __invoke(Response $response, callable $onError): void
    {
        $errorMessage = $this->formatErrorMessage($response);

        // Send error to client stream
        $onError($errorMessage);

        // Log the error for debugging
        \Illuminate\Support\Facades\Log::error('Streaming provider error', [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
        ]);
    }

    private function formatErrorMessage(Response $response): string
    {
        $status = $response->status();

        // Try to extract meaningful error from response body
        $body = $response->body();
        $decodedBody = json_decode($body, true);

        if (is_array($decodedBody) && isset($decodedBody['error']['message'])) {
            return "[Provider Error {$status}]: {$decodedBody['error']['message']}";
        }

        if (is_array($decodedBody) && isset($decodedBody['message'])) {
            return "[Provider Error {$status}]: {$decodedBody['message']}";
        }

        // Fallback to generic error message
        return "[Stream error: {$status}]";
    }
}
