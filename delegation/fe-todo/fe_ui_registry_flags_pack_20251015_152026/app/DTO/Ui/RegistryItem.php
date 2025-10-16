<?php

namespace App\DTO\Ui;

class RegistryItem
{
    public function __construct(
        public string $key,
        public string $type,
        public ?string $resourceKey,
        public string $version,
        public ?string $hash,
        public array $manifest = [],
        public array $tags = [],
        public string $visibility = 'private',
        public bool $enabled = true,
    ) {}
}
