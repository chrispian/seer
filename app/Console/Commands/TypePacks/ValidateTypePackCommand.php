<?php

namespace App\Console\Commands\TypePacks;

use App\Services\TypeSystem\TypePackValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidateTypePackCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'frag:type:validate {slug : Type pack slug} {sample? : Path to sample JSON file}';

    /**
     * The console command description.
     */
    protected $description = 'Test type pack validation against sample data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');
        $samplePath = $this->argument('sample');

        $validator = app(TypePackValidator::class);

        // If no sample provided, prompt for interactive input
        if (! $samplePath) {
            return $this->interactiveValidation($validator, $slug);
        }

        // Validate sample file
        return $this->validateSampleFile($validator, $slug, $samplePath);
    }

    /**
     * Interactive validation mode
     */
    protected function interactiveValidation(TypePackValidator $validator, string $slug): int
    {
        $this->info("Interactive validation for type pack '{$slug}'");
        $this->line("Enter JSON state data (or 'quit' to exit):");

        while (true) {
            $input = $this->ask('JSON');

            if (strtolower($input) === 'quit') {
                break;
            }

            try {
                $data = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
                $this->validateData($validator, $slug, $data);
            } catch (\JsonException $e) {
                $this->error("Invalid JSON: {$e->getMessage()}");
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Validate sample file
     */
    protected function validateSampleFile(TypePackValidator $validator, string $slug, string $samplePath): int
    {
        if (! File::exists($samplePath)) {
            $this->error("Sample file not found: {$samplePath}");

            return self::FAILURE;
        }

        try {
            $content = File::get($samplePath);
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $this->info("Validating sample file: {$samplePath}");

            return $this->validateData($validator, $slug, $data) ? self::SUCCESS : self::FAILURE;

        } catch (\JsonException $e) {
            $this->error("Invalid JSON in sample file: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Validate data and display results
     */
    protected function validateData(TypePackValidator $validator, string $slug, array $data): bool
    {
        try {
            $validatedData = $validator->validateFragmentState($data, $slug);

            $this->info('✅ Validation successful!');
            $this->line('Input data:');
            $this->line(json_encode($data, JSON_PRETTY_PRINT));

            if ($validatedData !== $data) {
                $this->line('Normalized data:');
                $this->line(json_encode($validatedData, JSON_PRETTY_PRINT));
            }

            return true;

        } catch (\Exception $e) {
            $this->error("❌ Validation failed: {$e->getMessage()}");

            // Get detailed validation errors
            $errors = $validator->getValidationErrors($data, $slug);
            if (! empty($errors)) {
                $this->line('Validation errors:');
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->line("  • {$field}: {$error}");
                    }
                }
            }

            return false;
        }
    }
}
