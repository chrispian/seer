<?php

namespace App\Console\Commands;

use App\Services\AvatarService;
use Illuminate\Console\Command;

class CleanupAvatars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatars:cleanup {--days=30 : Number of days old for cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old unused avatar files';

    /**
     * Execute the console command.
     */
    public function handle(AvatarService $avatarService)
    {
        $days = (int) $this->option('days');
        
        $this->info("Cleaning up avatar files older than {$days} days...");
        
        $cleaned = $avatarService->cleanupOldAvatars($days);
        
        $this->info("Cleanup completed. {$cleaned} files removed.");
        
        return 0;
    }
}
