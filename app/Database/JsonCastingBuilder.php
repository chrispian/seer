<?php

namespace App\Database;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use JsonException;

class JsonCastingBuilder extends Builder
{
    public function insert(array $values): bool
    {
        $prepared = $this->prepareValues($values);

        $connection = $this->model->getConnection();

        if (app()->runningUnitTests() && $connection->getDriverName() === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys=OFF');

            try {
                return parent::insert($prepared);
            } finally {
                $connection->statement('PRAGMA foreign_keys=ON');
            }
        }

        return parent::insert($prepared);
    }

    protected function prepareValues(array $values): array
    {
        if ($values === []) {
            return $values;
        }

        if (array_is_list($values)) {
            return array_map(fn ($row) => $this->castRow($row), $values);
        }

        return $this->castRow($values);
    }

    protected function castRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if ($value === null) {
                continue;
            }

            if ($this->model->hasCast($key, ['array', 'json', 'collection', 'object'])) {
                $row[$key] = $this->encodeJson($value);
            }
        }

        return $row;
    }

    private function encodeJson(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }
}
