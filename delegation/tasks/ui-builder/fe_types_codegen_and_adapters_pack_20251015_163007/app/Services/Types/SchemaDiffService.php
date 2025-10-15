<?php

namespace App\Services\Types;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchemaDiffService
{
    public function diff($schema): array
    {
        $table = Str::snake(Str::pluralStudly($schema->key));
        try {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
        } catch (\Throwable $e) {
            $columns = [];
        }
        $existing = array_map(fn($c) => $c->Field ?? null, $columns);
        $existing = array_filter($existing);

        $plan = [];
        foreach ($schema->fields as $f) {
            if (!in_array($f->name, $existing)) {
                $plan[] = "ADD COLUMN {$f->name} ({$f->type})";
            }
        }
        return $plan;
    }
}
