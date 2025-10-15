<?php

namespace App\DTOs\Ui;

class RegistryItem
{
    public function __construct(
        public string $type,
        public string $name,
        public string $slug,
        public ?string $description = null,
        public string $version = '1.0.0',
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?array $metadata = null,
        public bool $isActive = true,
        public ?string $publishedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            version: $data['version'] ?? '1.0.0',
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            isActive: $data['is_active'] ?? true,
            publishedAt: $data['published_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'version' => $this->version,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'metadata' => $this->metadata,
            'is_active' => $this->isActive,
            'published_at' => $this->publishedAt,
        ];
    }
}
