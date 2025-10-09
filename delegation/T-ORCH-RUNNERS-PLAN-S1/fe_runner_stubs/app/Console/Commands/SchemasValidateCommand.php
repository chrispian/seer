<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SchemasValidateCommand extends Command
{
    protected $signature = 'schemas:validate {path=docs/schemas/examples}';

    protected $description = 'Validate JSON files against FE runner schemas v0';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));
        // TODO: Implement validation using justinrainbow/json-schema or opis/json-schema.
        $this->info("Validating JSON in: {$path}");
        $this->warn('Validator not yet implemented (stub).');

        return self::SUCCESS;
    }
}
