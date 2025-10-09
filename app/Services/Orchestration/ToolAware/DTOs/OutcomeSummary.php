<?php

namespace App\Services\Orchestration\ToolAware\DTOs;

final class OutcomeSummary
{
    public string $short_summary;
    public array $key_facts = [];
    public array $links = [];
    public float $confidence = 0.0;

    public function __construct(
        string $short_summary = '',
        array $key_facts = [],
        array $links = [],
        float $confidence = 0.0
    ) {
        $this->short_summary = $short_summary;
        $this->key_facts = $key_facts;
        $this->links = $links;
        $this->confidence = max(0.0, min(1.0, $confidence));
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['short_summary'] ?? '',
            $data['key_facts'] ?? [],
            $data['links'] ?? [],
            $data['confidence'] ?? 0.0
        );
    }

    public function toArray(): array
    {
        return [
            'short_summary' => $this->short_summary,
            'key_facts' => $this->key_facts,
            'links' => $this->links,
            'confidence' => $this->confidence,
        ];
    }
}
