<?php

declare(strict_types=1);

namespace App\Services\ContextBroker\Views;

class GitView
{
    public function compute(array $params = []): array
    {
        // TODO
        return ['branch'=>null, 'dirty'=>[], 'recent_commits'=>[]];
    }
}
