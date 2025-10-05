<?php

namespace App\Services\Tools;

use App\Services\Tools\Contracts\Tool;
use Illuminate\Support\Facades\Config;

class ToolRegistry
{
    /** @var array<string,Tool> */
    protected array $map = [];

    public function register(Tool $tool): void
    {
        $this->map[$tool->slug()] = $tool;
    }

    public function get(string $slug): Tool
    {
        if (! isset($this->map[$slug])) {
            throw new \RuntimeException("Tool not registered: $slug");
        }

        return $this->map[$slug];
    }

    public function allowed(string $slug): bool
    {
        $allowed = Config::get('fragments.tools.allowed', []);

        return in_array($slug, $allowed, true);
    }
}
