<?php

namespace App\Services\Tools\Contracts;

interface Tool
{
    public function slug(): string;

    public function capabilities(): array;

    public function call(array $args, array $context = []): array;
}
