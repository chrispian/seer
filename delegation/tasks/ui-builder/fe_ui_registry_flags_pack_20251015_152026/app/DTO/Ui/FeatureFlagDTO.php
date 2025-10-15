<?php

namespace App\DTO\Ui;

class FeatureFlagDTO
{
    public function __construct(
        public string $key,
        public bool $enabled = false,
        public int $rollout = 0,
        public array $conditions = [],
        public ?string $description = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {}
}
