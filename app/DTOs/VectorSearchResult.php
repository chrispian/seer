<?php

namespace App\DTOs;

readonly class VectorSearchResult
{
    public function __construct(
        public int $fragmentId,
        public float $similarity,
        public float $textRank,
        public float $combinedScore,
        public string $snippet
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->fragmentId,
            'vec_sim' => $this->similarity,
            'txt_rank' => $this->textRank,
            'score' => $this->combinedScore,
            'snippet' => $this->snippet,
        ];
    }
}
