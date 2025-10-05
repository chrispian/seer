<?php

namespace App\Console\Commands\AI;

use App\Models\AICredential;
use Illuminate\Console\Command;

class CredentialsList extends Command
{
    protected $signature = 'ai:credentials:list
                            {--show-inactive : Include inactive credentials}';

    protected $description = 'List stored AI provider credentials';

    public function handle(): int
    {
        $showInactive = $this->option('show-inactive');

        $query = AICredential::query();

        if (! $showInactive) {
            $query->where('is_active', true);
        }

        $credentials = $query->orderBy('provider')->orderBy('credential_type')->get();

        if ($credentials->isEmpty()) {
            $this->info('No AI provider credentials found.');
            $this->info('Use "php artisan ai:credentials:set" to add credentials.');

            return self::SUCCESS;
        }

        $headers = ['Provider', 'Type', 'Status', 'Storage', 'Created', 'Expires'];
        $rows = [];

        foreach ($credentials as $credential) {
            $status = $this->getCredentialStatus($credential);
            $created = $credential->created_at->format('Y-m-d H:i');
            $expires = $credential->expires_at ? $credential->expires_at->format('Y-m-d H:i') : 'Never';

            // Get storage backend from metadata or default to 'database'
            $storageBackend = $credential->metadata['storage_backend'] ?? 'database';

            $rows[] = [
                $credential->provider,
                $credential->credential_type,
                $status,
                $storageBackend,
                $created,
                $expires,
            ];
        }

        $this->table($headers, $rows);

        // Show additional info
        $this->newLine();
        $activeCount = $credentials->where('is_active', true)->count();
        $expiredCount = $credentials->filter(fn ($c) => $c->isExpired())->count();

        $this->info("Total credentials: {$credentials->count()}");
        $this->info("Active: {$activeCount}");
        if ($expiredCount > 0) {
            $this->warn("Expired: {$expiredCount}");
        }

        return self::SUCCESS;
    }

    protected function getCredentialStatus(AICredential $credential): string
    {
        if (! $credential->is_active) {
            return '<fg=red>Inactive</>';
        }

        if ($credential->isExpired()) {
            return '<fg=yellow>Expired</>';
        }

        return '<fg=green>Active</>';
    }
}
