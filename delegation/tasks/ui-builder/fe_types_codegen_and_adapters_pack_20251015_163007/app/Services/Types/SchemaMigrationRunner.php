<?php

namespace App\Services\Types;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class SchemaMigrationRunner
{
    public function apply($schema, array $plan): void
    {
        $table = Str::snake(Str::pluralStudly($schema->key));
        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table) use ($schema) {
                $table->id();
                foreach ($schema->fields as $f) {
                    $this->addColumn($table, $f->name, $f->type, !$f->required);
                    if ($f->unique ?? false) $table->unique($f->name);
                }
                $table->timestamps();
            });
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($plan) {
            foreach ($plan as $step) {
                if (preg_match('/ADD COLUMN (\w+) \(([^)]+)\)/', $step, $m)) {
                    $this->addColumn($table, $m[1], $m[2], true);
                }
            }
        });
    }

    protected function addColumn(Blueprint $table, string $name, string $type, bool $nullable): void
    {
        if ($type === 'string') $col = $table->string($name);
        elseif (str_starts_with($type, 'decimal')) {
            preg_match('/decimal:(\d+),(\d+)/', $type, $mm);
            $col = $table->decimal($name, (int)($mm[1]??12), (int)($mm[2]??2));
        } elseif ($type === 'datetime') $col = $table->dateTime($name);
        elseif ($type === 'boolean') $col = $table->boolean($name);
        elseif (str_starts_with($type, 'enum:')) {
            $opts = explode(':', $type, 2)[1];
            $vals = array_map('trim', explode(',', $opts));
            $col = $table->enum($name, $vals);
        } else $col = $table->json($name);
        if ($nullable) $col->nullable();
    }
}
