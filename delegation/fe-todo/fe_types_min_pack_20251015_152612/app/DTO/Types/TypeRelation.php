<?php

namespace App\DTO\Types;

class TypeRelation
{
    public function __construct(
        public string $name,
        public string $relation,
        public string $target,
        public array $options = [],
        public int $order = 0
    ) {}
}
