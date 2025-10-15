<?php

namespace App\DTO\Types;

class TypeSchema
{
    public function __construct(
        public string $key,
        public string $version,
        /** @var TypeField[] */
        public array $fields = [],
        /** @var TypeRelation[] */
        public array $relations = [],
        public array $meta = [],
        public array $options = [],
    ) {}
}
