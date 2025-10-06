<?php

namespace App\Tools\Orchestration\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait NormalisesFilters
{
    protected function normaliseArray(mixed $values): array
    {
        $items = is_array($values) ? $values : [$values];

        return array_values(array_filter(array_map(static function ($value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            return $value === null || $value === '' ? null : $value;
        }, $items)));
    }

    protected function normaliseLowercaseArray(mixed $values): array
    {
        return array_map(static fn ($value) => Str::lower((string) $value), $this->normaliseArray($values));
    }

    protected function normaliseCodes(?array $codes): ?array
    {
        if (empty($codes)) {
            return null;
        }

        $normalised = [];

        foreach ($codes as $code) {
            $code = trim((string) $code);

            if ($code === '') {
                continue;
            }

            if (preg_match('/^\d+$/', $code)) {
                $normalised[] = 'SPRINT-' . str_pad($code, 2, '0', STR_PAD_LEFT);
                continue;
            }

            if (preg_match('/^(?:sprint-)?(\d+)$/i', $code, $matches)) {
                $normalised[] = 'SPRINT-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                continue;
            }

            $normalised[] = Str::upper($code);
        }

        return $normalised === [] ? null : array_values(array_unique($normalised));
    }

    protected function normalisePositiveInt(mixed $value, ?int $default = null): ?int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $int = (int) $value;

        return $int > 0 ? $int : $default;
    }

    protected function sliceLists(array $items, int $limit = 3): string
    {
        if ($items === []) {
            return '—';
        }

        $display = array_slice($items, 0, $limit);

        if (count($items) > $limit) {
            $display[] = sprintf('… +%d more', count($items) - $limit);
        }

        return implode(', ', $display);
    }

    protected function optionalIso(?\DateTimeInterface $date): ?string
    {
        return $date?->format(DATE_ATOM);
    }

    protected function optionalHuman(?\DateTimeInterface $date): ?string
    {
        return $date?->diffForHumans();
    }

    protected function pick(array $data, array $keys): array
    {
        return Arr::only($data, $keys);
    }
}
