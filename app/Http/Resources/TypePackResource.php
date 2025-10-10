<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypePackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->resource['slug'],
            'name' => $this->resource['manifest']['name'] ?? $this->resource['slug'],
            'description' => $this->resource['manifest']['description'] ?? '',
            'version' => $this->resource['manifest']['version'] ?? '1.0.0',
            'capabilities' => $this->resource['manifest']['capabilities'] ?? [],
            'ui' => $this->resource['manifest']['ui'] ?? [],
            'schema' => $this->resource['schema'] ?? null,
            'indexes' => $this->resource['indexes'] ?? null,
            'source_path' => $this->resource['source_path'] ?? null,
        ];
    }
}
