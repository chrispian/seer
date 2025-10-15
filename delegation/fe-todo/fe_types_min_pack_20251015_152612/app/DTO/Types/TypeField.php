<?php

namespace App\DTO\Types;

class TypeField
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $required = false,
        public bool $unique = false,
        public array $options = [],
        public int $order = 0
    ) {}
}
