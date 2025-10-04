<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ToolInvocationsCommand extends Command
{
    protected $signature = 'frag:tools:invocations {--tool= : Filter by tool slug} {--limit=10 : Number of records to show} {--status= : Filter by status (ok|error)}';

    protected $description = 'Show recent tool invocations and their results';

    public function handle(): int
    {
        $tool = $this->option('tool');
        $limit = (int) $this->option('limit');
        $status = $this->option('status');

        $query = DB::table('tool_invocations')
            ->orderBy('created_at', 'desc');

        if ($tool) {
            $query->where('tool_slug', $tool);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $invocations = $query->limit($limit)->get();

        if ($invocations->isEmpty()) {
            $this->info('No tool invocations found');

            return self::SUCCESS;
        }

        $this->info("Recent Tool Invocations (limit: {$limit}):");
        $this->newLine();

        $headers = ['ID', 'Tool', 'Status', 'Duration', 'User', 'Command', 'Created'];
        $rows = [];

        foreach ($invocations as $invocation) {
            $rows[] = [
                substr($invocation->id, 0, 8).'...',
                $invocation->tool_slug,
                $invocation->status === 'ok' ? '✅ OK' : '❌ Error',
                $invocation->duration_ms ? round($invocation->duration_ms, 1).'ms' : '-',
                $invocation->user_id ?? '-',
                $invocation->command_slug ?? '-',
                $invocation->created_at,
            ];
        }

        $this->table($headers, $rows);

        // Show summary statistics
        $this->newLine();
        $stats = DB::table('tool_invocations')
            ->selectRaw('tool_slug, status, COUNT(*) as count, AVG(duration_ms) as avg_duration')
            ->groupBy('tool_slug', 'status')
            ->get();

        if (! $stats->isEmpty()) {
            $this->info('Summary Statistics:');
            $statsHeaders = ['Tool', 'Status', 'Count', 'Avg Duration'];
            $statsRows = [];

            foreach ($stats as $stat) {
                $statsRows[] = [
                    $stat->tool_slug,
                    $stat->status,
                    $stat->count,
                    $stat->avg_duration ? round($stat->avg_duration, 1).'ms' : '-',
                ];
            }

            $this->table($statsHeaders, $statsRows);
        }

        return self::SUCCESS;
    }
}
