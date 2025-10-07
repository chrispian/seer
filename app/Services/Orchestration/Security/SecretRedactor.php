<?php

namespace App\Services\Orchestration\Security;

use Illuminate\Support\Facades\Log;

class SecretRedactor
{
    protected array $patterns;
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('orchestration.secret_redaction.enabled', true);
        
        $builtInPatterns = config('orchestration.secret_redaction.patterns', []);
        $customPatterns = config('orchestration.secret_redaction.custom_patterns', []);
        
        $this->patterns = array_merge($builtInPatterns, $customPatterns);
    }

    public function redact(string $content): string
    {
        if (!$this->enabled) {
            return $content;
        }

        $redacted = $content;
        $redactionCount = 0;

        foreach ($this->patterns as $pattern) {
            $matches = [];
            if (preg_match_all("/{$pattern}/", $redacted, $matches)) {
                foreach ($matches[0] as $match) {
                    $type = $this->getSecretType($match);
                    $redacted = str_replace($match, "[REDACTED:{$type}]", $redacted);
                    $redactionCount++;
                }
            }
        }

        if ($redactionCount > 0) {
            Log::info('SecretRedactor: Redacted secrets', [
                'count' => $redactionCount,
                'content_length' => strlen($content),
            ]);
        }

        return $redacted;
    }

    public function redactArray(array $data): array
    {
        if (!$this->enabled) {
            return $data;
        }

        return $this->recursiveRedact($data);
    }

    protected function recursiveRedact($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'recursiveRedact'], $value);
        }

        if (is_string($value)) {
            return $this->redact($value);
        }

        return $value;
    }

    public function scan(string $content): array
    {
        $findings = [];

        foreach ($this->patterns as $pattern) {
            $matches = [];
            if (preg_match_all("/{$pattern}/", $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $type = $this->getSecretType($match);
                    $findings[] = [
                        'type' => $type,
                        'pattern' => $pattern,
                        'preview' => substr($match, 0, 20) . '...',
                    ];
                }
            }
        }

        return $findings;
    }

    public function hasSecrets(string $content): bool
    {
        foreach ($this->patterns as $pattern) {
            if (preg_match("/{$pattern}/", $content)) {
                return true;
            }
        }

        return false;
    }

    protected function getSecretType(string $match): string
    {
        if (str_contains($match, 'AWS_ACCESS')) {
            return 'AWS_ACCESS_KEY';
        }
        
        if (str_contains($match, 'AWS_SECRET')) {
            return 'AWS_SECRET_KEY';
        }
        
        if (str_contains($match, 'APP_KEY')) {
            return 'APP_KEY';
        }
        
        if (str_starts_with($match, 'Bearer ')) {
            return 'BEARER_TOKEN';
        }
        
        if (str_contains($match, 'OPENAI')) {
            return 'OPENAI_API_KEY';
        }
        
        if (str_contains($match, 'ANTHROPIC')) {
            return 'ANTHROPIC_API_KEY';
        }

        return 'SECRET';
    }

    public function getPatterns(): array
    {
        return $this->patterns;
    }

    public function addPattern(string $pattern): void
    {
        if (!in_array($pattern, $this->patterns, true)) {
            $this->patterns[] = $pattern;
        }
    }
}
