<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Commands\CommandRegistry;

class FragCommandCache extends Command
{
    protected $signature = 'frag:command:cache';
    protected $description = 'Rebuild Command Pack registry cache';

    public function handle(): int
    {
        $list = app(CommandRegistry::class)->rebuild();
        $this->info('Cached command packs: '.implode(', ', $list));
        return self::SUCCESS;
    }
}
