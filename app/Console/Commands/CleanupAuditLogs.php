<?php

namespace App\Console\Commands;

use App\Models\CommandAuditLog;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class CleanupAuditLogs extends Command
{
    protected $signature = 'audit:cleanup {--days= : Number of days to retain (default from config)} {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old audit logs based on retention policy';

    public function handle(): int
    {
        $retentionDays = $this->option('days') ?? config('audit.retention_days', 90);
        $dryRun = $this->option('dry-run');
        $cutoffDate = now()->subDays($retentionDays);

        $this->info("Cleaning up audit logs older than {$retentionDays} days (before {$cutoffDate->format('Y-m-d H:i:s')})");

        $commandLogsCount = CommandAuditLog::where('created_at', '<', $cutoffDate)->count();
        $activityLogsCount = Activity::where('created_at', '<', $cutoffDate)->count();

        $this->info("Found {$commandLogsCount} command audit logs to delete");
        $this->info("Found {$activityLogsCount} activity logs to delete");

        if ($dryRun) {
            $this->warn('DRY RUN - No records will be deleted');
            return self::SUCCESS;
        }

        if ($commandLogsCount > 0 || $activityLogsCount > 0) {
            if (! $this->confirm('Do you want to proceed with deletion?', true)) {
                $this->info('Cleanup cancelled');
                return self::SUCCESS;
            }

            $deletedCommandLogs = CommandAuditLog::where('created_at', '<', $cutoffDate)->delete();
            $deletedActivityLogs = Activity::where('created_at', '<', $cutoffDate)->delete();

            $this->info("Deleted {$deletedCommandLogs} command audit logs");
            $this->info("Deleted {$deletedActivityLogs} activity logs");
            $this->info('Cleanup completed successfully');
        } else {
            $this->info('No logs to clean up');
        }

        return self::SUCCESS;
    }
}
