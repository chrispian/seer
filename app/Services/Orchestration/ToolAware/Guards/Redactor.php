<?php

namespace App\Services\Orchestration\ToolAware\Guards;

class Redactor
{
    protected array $patterns = [
        // Email addresses
        '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
        
        // API keys (long alphanumeric strings)
        '/\b[A-Z0-9]{20,}\b/',
        
        // OpenAI keys
        '/sk-[a-zA-Z0-9]{20,}/',
        
        // Anthropic keys
        '/sk-ant-[a-zA-Z0-9-]+/',
        
        // Bearer tokens
        '/Bearer\s+[a-zA-Z0-9._-]+/',
        
        // Authorization headers
        '/Authorization:\s*[^\s]+/',
        
        // AWS keys
        '/AKIA[0-9A-Z]{16}/',
        
        // Generic secrets
        '/password["\s:=]+[^\s"]+/i',
        '/api[_-]?key["\s:=]+[^\s"]+/i',
        '/secret["\s:=]+[^\s"]+/i',
        '/token["\s:=]+[^\s"]+/i',
        
        // Credit card numbers (basic pattern)
        '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/',
        
        // SSN
        '/\b\d{3}-\d{2}-\d{4}\b/',
        
        // Phone numbers
        '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',
    ];

    protected string $replacement = '[REDACTED]';

    /**
     * Redact sensitive information from text
     */
    public function redact(string $text): string
    {
        foreach ($this->patterns as $pattern) {
            $text = preg_replace($pattern, $this->replacement, $text);
        }

        return $text;
    }

    /**
     * Redact sensitive information from array (recursive)
     */
    public function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->redact($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            }
        }

        return $data;
    }

    /**
     * Redact sensitive keys from array
     */
    public function redactKeys(array $data, array $sensitiveKeys = []): array
    {
        $defaultSensitiveKeys = [
            'password',
            'api_key',
            'apiKey',
            'secret',
            'token',
            'auth',
            'authorization',
            'credentials',
            'private_key',
            'privateKey',
        ];

        $keysToRedact = array_merge($defaultSensitiveKeys, $sensitiveKeys);

        foreach ($data as $key => $value) {
            // Check if key matches sensitive patterns
            foreach ($keysToRedact as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $data[$key] = $this->replacement;
                    continue 2;
                }
            }

            // Recurse into arrays
            if (is_array($value)) {
                $data[$key] = $this->redactKeys($value, $sensitiveKeys);
            }
        }

        return $data;
    }

    /**
     * Combine all redaction methods
     */
    public function redactAll(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->redact($data);
        }

        if (is_array($data)) {
            $data = $this->redactKeys($data);
            $data = $this->redactArray($data);
            return $data;
        }

        return $data;
    }
}
