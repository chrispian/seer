<?php

namespace App\Services\Types;

use App\Models\FeType;
use App\DTO\Types\{TypeSchema, TypeField, TypeRelation};

class TypeRegistry
{
    public function get(string $alias): ?TypeSchema
    {
        $type = FeType::with(['fields','relations'])->where('key', $alias)->first();
        if (!$type) return null;

        return new TypeSchema(
            key: $type->key,
            version: $type->version,
            fields: array_map(fn($f) => new TypeField($f->name, $f->type, (bool)$f->required, (bool)$f->unique, $f->options_json ?? [], (int)$f->order), $type->fields->all()),
            relations: array_map(fn($r) => new TypeRelation($r->name, $r->relation, $r->target, $r->options_json ?? [], (int)$r->order), $type->relations->all()),
            meta: $type->meta_json ?? [],
            options: $type->options_json ?? []
        );
    }
}
