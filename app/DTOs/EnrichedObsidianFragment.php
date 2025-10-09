<?php

namespace App\DTOs;

class EnrichedObsidianFragment
{
    public function __construct(
        public string $type,
        public array $tags,
        public array $customMetadata = [],
    ) {}
}
