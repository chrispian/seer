<?php

namespace App\Services\Types;

use App\DTOs\Types\TypeSchema;
use App\Models\FeType;
use Illuminate\Support\Facades\Cache;

class TypeRegistry
{
    private const CACHE_PREFIX = 'fe_type_schema:';
    private const CACHE_TTL = 3600;

    public function get(string $alias): ?TypeSchema
    {
        return Cache::remember(
            self::CACHE_PREFIX . $alias,
            self::CACHE_TTL,
            fn() => $this->loadFromDatabase($alias)
        );
    }

    public function all(): array
    {
        $types = FeType::with(['fields', 'relations'])
            ->enabled()
            ->get();

        return $types->map(fn($type) => TypeSchema::fromModel($type))->toArray();
    }

    public function refresh(string $alias): void
    {
        Cache::forget(self::CACHE_PREFIX . $alias);
    }

    public function refreshAll(): void
    {
        $types = FeType::pluck('alias');
        
        foreach ($types as $alias) {
            $this->refresh($alias);
        }
    }

    private function loadFromDatabase(string $alias): ?TypeSchema
    {
        $feType = FeType::with(['fields', 'relations'])
            ->byAlias($alias)
            ->enabled()
            ->first();

        return $feType ? TypeSchema::fromModel($feType) : null;
    }

    public function register(TypeSchema $schema): void
    {
        $feType = FeType::updateOrCreate(
            ['alias' => $schema->alias],
            [
                'source_type' => $schema->sourceType,
                'config' => $schema->config,
                'capabilities' => $schema->capabilities,
                'metadata' => $schema->metadata,
                'enabled' => $schema->enabled,
            ]
        );

        $feType->fields()->delete();
        foreach ($schema->fields as $field) {
            $feType->fields()->create($field instanceof \App\DTOs\Types\TypeField ? [
                'name' => $field->name,
                'type' => $field->type,
                'label' => $field->label,
                'required' => $field->required,
                'searchable' => $field->searchable,
                'sortable' => $field->sortable,
                'filterable' => $field->filterable,
                'validation' => $field->validation,
                'metadata' => $field->metadata,
                'order' => $field->order,
            ] : $field);
        }

        $feType->relations()->delete();
        foreach ($schema->relations as $relation) {
            $feType->relations()->create($relation instanceof \App\DTOs\Types\TypeRelation ? [
                'name' => $relation->name,
                'type' => $relation->type,
                'related_type' => $relation->relatedType,
                'foreign_key' => $relation->foreignKey,
                'local_key' => $relation->localKey,
                'metadata' => $relation->metadata,
            ] : $relation);
        }

        $this->refresh($schema->alias);
    }
}
