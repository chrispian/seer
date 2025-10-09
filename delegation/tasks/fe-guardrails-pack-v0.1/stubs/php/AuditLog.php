<?php

namespace App\Services\Audit;

class AuditLog
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? storage_path('logs/guardrails.jsonl');
    }

    public function write(array $entry): void
    {
        $entry['id'] = bin2hex(random_bytes(8));
        $entry['hash'] = hash('sha256', json_encode($entry));
        file_put_contents($this->file, json_encode($entry).PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
