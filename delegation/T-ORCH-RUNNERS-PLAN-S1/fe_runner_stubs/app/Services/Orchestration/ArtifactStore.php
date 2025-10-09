<?php

declare(strict_types=1);

namespace App\Services\Orchestration;

class ArtifactStore
{
    public function put(string $uri, string $content): void
    {
        // TODO: store blob to configured disk (local/S3) mapped from fe://
    }
}
