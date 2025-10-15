<?php

namespace App\DTOs\Types;

class TypeField
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $label = null,
        public bool $required = false,
        public bool $searchable = false,
        public bool $sortable = false,
        public bool $filterable = false,
        public array $validation = [],
        public array $metadata = [],
        public int $order = 0
    ) {}

    public static function fromModel($field): self
    {
        return new self(
            name: $field->name,
            type: $field->type,
            label: $field->label,
            required: $field->required,
            searchable: $field->searchable,
            sortable: $field->sortable,
            filterable: $field->filterable,
            validation: $field->validation ?? [],
            metadata: $field->metadata ?? [],
            order: $field->order
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->label,
            'required' => $this->required,
            'searchable' => $this->searchable,
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'validation' => $this->validation,
            'metadata' => $this->metadata,
            'order' => $this->order,
        ];
    }
}
