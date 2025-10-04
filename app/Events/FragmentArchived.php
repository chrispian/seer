<?php

namespace App\Events;

use App\Models\Fragment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FragmentArchived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Fragment $fragment,
        public int $userId,
        public ?string $reason = null
    ) {}
}
