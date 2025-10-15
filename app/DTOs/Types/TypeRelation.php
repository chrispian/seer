<?php

namespace App\DTOs\Types;

class TypeRelation
{
    public function __construct(
        public string $name,
        public string $type,
        public string $relatedType,
        public ?string $foreignKey = null,
        public ?string $localKey = null,
        public array $metadata = []
    ) {}

    public static function fromModel($relation): self
    {
        return new self(
            name: $relation->name,
            type: $relation->type,
            relatedType: $relation->related_type,
            foreignKey: $relation->foreign_key,
            localKey: $relation->local_key,
            metadata: $relation->metadata ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'related_type' => $this->relatedType,
            'foreign_key' => $this->foreignKey,
            'local_key' => $this->localKey,
            'metadata' => $this->metadata,
        ];
    }
}
