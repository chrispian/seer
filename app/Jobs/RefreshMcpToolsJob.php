<?php

namespace App\Jobs;

use App\Models\ToolDefinition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RefreshMcpToolsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $server = null
    ) {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        Log::info('Starting async MCP tools refresh', [
            'server' => $this->server ?? 'all',
        ]);

        try {
            $command = 'tools:sync-mcp';
            $params = [];
            
            if ($this->server) {
                $params['--server'] = $this->server;
            }

            Artisan::call($command, $params);
            
            $output = Artisan::output();

            Log::info('MCP tools refresh completed', [
                'server' => $this->server ?? 'all',
                'output' => trim($output),
            ]);

            // Update last sync timestamp
            $query = ToolDefinition::mcp();
            if ($this->server) {
                $query->byServer($this->server);
            }
            $query->update(['synced_at' => now()]);

        } catch (\Exception $e) {
            Log::error('MCP tools refresh failed', [
                'server' => $this->server ?? 'all',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function tags(): array
    {
        return ['mcp-sync', 'tools', $this->server ?? 'all'];
    }
}
