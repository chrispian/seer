<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class DevRefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:refresh {--skip-build : Skip the npm build step}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all caches and rebuild assets for development';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting development refresh...');
        
        // Clear all Laravel caches
        $this->info('Clearing Laravel caches...');
        $this->call('optimize:clear');
        
        // Clear specific UI builder caches
        $this->info('Clearing UI builder caches...');
        Cache::forget('datasource.Agent');
        Cache::forget('datasource.Model');
        Cache::forget('ui-builder.datasources');
        Cache::forget('ui-builder.components');
        Cache::forget('ui-builder.pages');
        Cache::forget('ui-builder.registry');
        
        // Clear Filament caches
        $this->info('Clearing Filament caches...');
        $this->call('filament:optimize-clear');
        
        // Clear view caches
        $this->info('Clearing view caches...');
        $this->call('view:clear');
        
        // Build frontend assets unless skipped
        if (!$this->option('skip-build')) {
            $this->info('Building frontend assets...');
            $result = Process::run('npm run build');
            
            if ($result->successful()) {
                $this->info('✅ Frontend assets built successfully');
            } else {
                $this->error('❌ Frontend build failed:');
                $this->error($result->errorOutput());
                return 1;
            }
        } else {
            $this->warn('⚠️  Skipping frontend build (--skip-build flag used)');
        }
        
        $this->newLine();
        $this->info('✨ Development refresh complete!');
        $this->info('All caches cleared and assets rebuilt.');
        
        return 0;
    }
}