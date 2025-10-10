<?php

namespace App\DTOs;

readonly class ParsedObsidianNote
{
    public function __construct(
        public string $title,
        public string $body,
        public array $frontMatter,
        public array $tags,
        public array $links = [],
    ) {}
}
