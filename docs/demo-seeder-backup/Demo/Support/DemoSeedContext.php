<?php

namespace Database\Seeders\Demo\Support;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;

class DemoSeedContext
{
    /** @var array<string, array<string, mixed>> */
    private array $store = [];

    public function __construct(private readonly ?OutputStyle $output = null) {}

    public function set(string $bucket, string $key, mixed $value): void
    {
        $this->store[$bucket][$key] = $value;
    }

    public function get(string $bucket, string $key, mixed $default = null): mixed
    {
        return $this->store[$bucket][$key] ?? $default;
    }

    public function collection(string $bucket): Collection
    {
        return collect($this->store[$bucket] ?? []);
    }

    public function forget(string $bucket, string $key): void
    {
        if (! isset($this->store[$bucket][$key])) {
            return;
        }

        unset($this->store[$bucket][$key]);

        if (empty($this->store[$bucket])) {
            unset($this->store[$bucket]);
        }
    }

    public function clear(string $bucket): void
    {
        unset($this->store[$bucket]);
    }

    public function info(string $message): void
    {
        $this->output?->writeln($message);
    }
}
