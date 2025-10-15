<?php

namespace App\DTOs\Types;

class TypeSchema
{
    public function __construct(
        public string $alias,
        public string $sourceType,
        public array $fields = [],
        public array $relations = [],
        public array $capabilities = [],
        public array $config = [],
        public array $metadata = [],
        public bool $enabled = true
    ) {}

    public static function fromModel($feType): self
    {
        return new self(
            alias: $feType->alias,
            sourceType: $feType->source_type,
            fields: $feType->fields->map(fn($f) => TypeField::fromModel($f))->toArray(),
            relations: $feType->relations->map(fn($r) => TypeRelation::fromModel($r))->toArray(),
            capabilities: $feType->capabilities ?? [],
            config: $feType->config ?? [],
            metadata: $feType->metadata ?? [],
            enabled: $feType->enabled
        );
    }

    public function toArray(): array
    {
        return [
            'alias' => $this->alias,
            'source_type' => $this->sourceType,
            'fields' => array_map(fn($f) => $f instanceof TypeField ? $f->toArray() : $f, $this->fields),
            'relations' => array_map(fn($r) => $r instanceof TypeRelation ? $r->toArray() : $r, $this->relations),
            'capabilities' => $this->capabilities,
            'config' => $this->config,
            'metadata' => $this->metadata,
            'enabled' => $this->enabled,
        ];
    }
}
