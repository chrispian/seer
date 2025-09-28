<?php

namespace App\Jobs;

use App\Actions\EnrichFragmentWithAI;
use App\Models\Fragment;
use Illuminate\Contracts\Queue\ShouldQueue;

class EnrichFragment implements ShouldQueue
{
    public function __construct(public Fragment $fragment) {}

    public function handle()
    {
        app(EnrichFragmentWithAI::class)($this->fragment);
    }
}
