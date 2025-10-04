<?php

namespace App\Console\Commands\Tools;

use App\Services\Tools\ToolRegistry;
use Illuminate\Console\Command;

class ListToolsCommand extends Command
{
    protected $signature = 'frag:tools:list';

    protected $description = 'List all available and configured tools';

    public function __construct(protected ToolRegistry $registry)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Available Tools:');
        $this->newLine();

        $tools = $this->registry->all();
        $allowed = $this->registry->getAllowed();

        $headers = ['Slug', 'Status', 'Capabilities', 'Enabled'];
        $rows = [];

        foreach ($tools as $slug => $tool) {
            $isAllowed = isset($allowed[$slug]);
            $isEnabled = $tool->isEnabled();

            $status = match (true) {
                $isAllowed && $isEnabled => '✅ Available',
                $isAllowed && ! $isEnabled => '⚠️  Configured but disabled',
                ! $isAllowed && $isEnabled => '🔒 Enabled but not allowed',
                default => '❌ Disabled'
            };

            $rows[] = [
                $slug,
                $status,
                implode(', ', $tool->capabilities()),
                $isEnabled ? 'Yes' : 'No',
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Total tools: '.count($tools));
        $this->info('Allowed tools: '.count($allowed));

        if (count($allowed) === 0) {
            $this->warn('No tools are currently allowed. Configure fragments.tools.allowed in config.');
        }

        return self::SUCCESS;
    }
}
