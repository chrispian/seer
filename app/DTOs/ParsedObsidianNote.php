<?php

namespace App\DTOs;

class ParsedObsidianNote
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $frontMatter,
        public readonly array $tags,
        public readonly array $links = [],
    ) {}
}
