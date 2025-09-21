<?php

namespace App\Console\Commands\AI;

use App\Models\AICredential;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class CredentialsRemove extends Command
{
    protected $signature = 'ai:credentials:remove
                            {provider? : Provider name}
                            {--type=api_key : Credential type}
                            {--force : Skip confirmation}';

    protected $description = 'Remove AI provider credentials';

    public function handle(): int
    {
        $provider = $this->argument('provider');
        $type = $this->option('type');
        $force = $this->option('force');

        // If no provider specified, show available ones
        if (! $provider) {
            $credentials = AICredential::where('is_active', true)->get();

            if ($credentials->isEmpty()) {
                $this->info('No active credentials found.');

                return self::SUCCESS;
            }

            $options = [];
            foreach ($credentials as $credential) {
                $key = "{$credential->provider}:{$credential->credential_type}";
                $options[$key] = "{$credential->provider} ({$credential->credential_type})";
            }

            $selected = select('Which credentials would you like to remove?', $options);
            [$provider, $type] = explode(':', $selected);
        }

        // Find the credential
        $credential = AICredential::getActiveCredential($provider, $type);

        if (! $credential) {
            $this->error("No active credentials found for {$provider} ({$type})");

            return self::FAILURE;
        }

        // Confirm removal
        if (! $force) {
            $confirmed = confirm(
                "Are you sure you want to remove credentials for {$provider} ({$type})?"
            );

            if (! $confirmed) {
                $this->info('Operation cancelled');

                return self::SUCCESS;
            }
        }

        // Remove the credential
        try {
            $credential->update(['is_active' => false]);
            $this->info("✅ Credentials for {$provider} ({$type}) have been deactivated");

            // Optionally delete entirely
            if (! $force && confirm('Would you like to delete the credential entirely (cannot be undone)?')) {
                $credential->delete();
                $this->info("🗑️ Credentials for {$provider} ({$type}) have been permanently deleted");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to remove credentials: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
