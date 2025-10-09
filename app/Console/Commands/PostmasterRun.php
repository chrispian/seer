<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PostmasterRun extends Command
{
    protected $signature = 'postmaster:run {--queue=default}';

    protected $description = 'Run Postmaster queue worker for parcel processing';

    public function handle(): int
    {
        $queue = $this->option('queue');

        $this->info('Starting Postmaster queue worker...');
        $this->info("Queue: {$queue}");
        $this->info('Listening for: postmaster jobs');
        $this->newLine();

        $exitCode = Artisan::call('queue:work', [
            '--queue' => "postmaster,{$queue}",
            '--tries' => 3,
            '--timeout' => 300,
            '--sleep' => 3,
            '--max-jobs' => 1000,
            '--stop-when-empty' => false,
        ], $this->output);

        if ($exitCode === 0) {
            $this->info('Postmaster worker stopped gracefully');
        } else {
            $this->error('Postmaster worker stopped with errors');
        }

        return $exitCode;
    }
}
