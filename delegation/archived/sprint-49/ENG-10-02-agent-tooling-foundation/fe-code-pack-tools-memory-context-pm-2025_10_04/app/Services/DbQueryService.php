<?php

namespace App\Services;

use App\Tools\DbQueryTool;

class DbQueryService
{
    public function __construct(protected DbQueryTool $tool) {}

    public function run(array $payload): array
    {
        // In a larger app you'd validate against JSON schema or FormRequest
        return $this->tool->run($payload);
    }
}
