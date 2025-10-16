<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\Types\TypeRegistry;

class TypesCodegen extends Command
{
    protected $signature = 'types:codegen {alias} {--force}';
    protected $description = 'Generate Eloquent model, migration, resource, policy, and form requests from a FE Type schema.';

    public function handle(TypeRegistry $registry)
    {
        $alias = $this->argument('alias');
        $schema = $registry->get($alias);
        if (!$schema) {
            $this->error("Type {$alias} not found.");
            return 1;
        }

        $class = Str::studly($alias);
        $table = Str::snake(Str::pluralStudly($class));

        $replacements = [
            '{{class}}' => $class,
            '{{table}}' => $table,
            '{{alias}}' => $alias,
            '{{fields_migration}}' => $this->fieldsToMigration($schema->fields),
            '{{fillable}}' => $this->fieldsToFillable($schema->fields),
            '{{casts}}' => $this->fieldsToCasts($schema->fields),
            '{{rules_create}}' => $this->fieldsToRules($schema->fields, true),
            '{{rules_update}}' => $this->fieldsToRules($schema->fields, false),
            '{{resource_fields}}' => $this->fieldsToResource($schema->fields),
        ];

        $this->generateFromStub('Model.stub', app_path("Models/{$class}.php"), $replacements);
        $this->generateFromStub('Policy.stub', app_path("Policies/{$class}Policy.php"), $replacements);
        $this->generateFromStub('RequestCreate.stub', app_path("Http/Requests/{$class}CreateRequest.php"), $replacements);
        $this->generateFromStub('RequestUpdate.stub', app_path("Http/Requests/{$class}UpdateRequest.php"), $replacements);
        $this->generateFromStub('Resource.stub', app_path("Http/Resources/{$class}Resource.php"), $replacements);

        $timestamp = date('Y_m_d_His');
        $this->generateFromStub('Migration.stub', base_path("database/migrations/{$timestamp}_create_{$table}_table.php"), $replacements);

        $this->info("Codegen complete for {$alias}.");
        return 0;
    }

    protected function generateFromStub(string $stub, string $target, array $repl)
    {
        $path = base_path("resources/stubs/types/{$stub}");
        if (!file_exists($path)) {
            $this->error("Missing stub: {$stub}");
            return;
        }
        $content = file_get_contents($path);
        $content = strtr($content, $repl);
        @mkdir(dirname($target), 0777, true);
        if (file_exists($target) && !$this->option('force')) {
            $this->warn("Skip existing: {$target} (use --force to overwrite)");
            return;
        }
        file_put_contents($target, $content);
        $this->line("Wrote: {$target}");
    }

    protected function fieldsToMigration(array $fields): string
    {
        $lines = ["$table->id();"];
        foreach ($fields as $f) {
            $name = $f->name;
            $type = $f->type;
            $required = $f->required ?? false;
            $nullable = $required ? '' : '->nullable()';

            if (str_starts_with($type, 'string')) {
                $lines.append("\$table->string('{$name}'){$nullable};");
            } elseif (str_starts_with($type, 'decimal')) {
                $precision = 12; $scale = 2;
                if (preg_match('/decimal:(\d+),(\d+)/', $type, $m)) { $precision = $m[1]; $scale = $m[2]; }
                $lines.append("\$table->decimal('{$name}', {$precision}, {$scale}){$nullable};");
            } elseif (str_starts_with($type, 'enum:')) {
                $opts = explode(':', $type, 2)[1];
                $vals = implode(',', array_map(fn($v)=>"'".trim($v)."'", explode(',', $opts)));
                $lines.append("\$table->enum('{$name}', [{$vals}]){$nullable};");
            } elseif ($type === 'datetime') {
                $lines.append("\$table->dateTime('{$name}'){$nullable};");
            } elseif ($type === 'boolean') {
                $lines.append("\$table->boolean('{$name}'){$nullable};");
            } else {
                $lines.append("\$table->json('{$name}'){$nullable};");
            }

            if (($f->unique ?? false) === true) {
                $lines.append("\$table->unique('{$name}');");
            }
        }
        $lines.append("\$table->timestamps();");
        return implode("\n            ", $lines);
    }

    protected function fieldsToFillable(array $fields): string
    {
        $names = array_map(fn($f)=>"'{$f->name}'", $fields);
        return implode(', ', $names);
    }

    protected function fieldsToCasts(array $fields): string
    {
        $casts = [];
        foreach ($fields as $f) {
            $t = $f->type;
            $name = $f->name;
            if ($t === 'datetime') $casts[$name] = 'datetime';
            elseif ($t === 'boolean') $casts[$name] = 'boolean';
            elseif (str_starts_with($t, 'decimal')) $casts[$name] = 'decimal:2';
        }
        if (empty($casts)) return '';
        $pairs = array_map(fn($k,$v)=>"'{$k}' => '{$v}'", array_keys($casts), $casts);
        return implode(', ', $pairs);
    }

    protected function fieldsToRules(array $fields, bool $isCreate): string
    {
        $rules = [];
        foreach ($fields as $f) {
            $parts = [];
            $parts[] = $f->required ? 'required' : 'nullable';

            $t = $f->type;
            if ($t === 'string') $parts[] = 'string';
            elseif (str_starts_with($t, 'decimal')) $parts[] = 'numeric';
            elseif ($t === 'datetime') $parts[] = 'date';
            elseif ($t === 'boolean') $parts[] = 'boolean';
            elseif (str_starts_with($t, 'enum:')) {
                $opts = explode(':', $t, 2)[1];
                $parts.append('in:' . $opts);
            }

            if ($f->unique) $parts[] = 'unique:' . Str::snake(Str::pluralStudly(Str::studly($this->argument('alias')))) . ',' . $f->name;

            $rules[$f->name] = implode('|', $parts);
        }
        if (empty($rules)) return '';
        $export = var_export($rules, true);
        return str_replace(['array (', ')'], ['[', ']'], $export);
    }

    protected function fieldsToResource(array $fields): string
    {
        $lines = [];
        foreach ($fields as $f) {
            $name = $f->name;
            $lines.append("'{$name}' => $this->{$name},");
        }
        return implode("\n            ", $lines);
    }
}
