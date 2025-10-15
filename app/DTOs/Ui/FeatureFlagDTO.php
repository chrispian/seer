<?php

namespace App\DTOs\Ui;

class FeatureFlagDTO
{
    public function __construct(
        public string $key,
        public string $name,
        public ?string $description = null,
        public bool $isEnabled = false,
        public ?int $percentage = null,
        public ?array $conditions = null,
        public ?array $metadata = null,
        public ?string $environment = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            name: $data['name'],
            description: $data['description'] ?? null,
            isEnabled: $data['is_enabled'] ?? false,
            percentage: $data['percentage'] ?? null,
            conditions: $data['conditions'] ?? null,
            metadata: $data['metadata'] ?? null,
            environment: $data['environment'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'is_enabled' => $this->isEnabled,
            'percentage' => $this->percentage,
            'conditions' => $this->conditions,
            'metadata' => $this->metadata,
            'environment' => $this->environment,
        ];
    }
}
