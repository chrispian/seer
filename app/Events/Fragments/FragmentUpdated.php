<?php

namespace App\Events\Fragments;

use App\Models\Fragment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FragmentUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Fragment $fragment
    ) {}
}