<?php

namespace App\Jobs;

use App\Actions\EnrichFragmentWithLlama;
use App\Models\Fragment;
use Illuminate\Contracts\Queue\ShouldQueue;

class EnrichFragment implements ShouldQueue
{
    public function __construct(public Fragment $fragment) {}

    public function handle()
    {
        app(EnrichFragmentWithLlama::class)($this->fragment);
    }
}
