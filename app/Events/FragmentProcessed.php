<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FragmentProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $fragmentId;

    public int $childCount;

    public array $children;

    public function __construct(int $fragmentId, int $childCount = 0, array $children = [])
    {
        $this->fragmentId = $fragmentId;
        $this->childCount = $childCount;
        $this->children = $children;
    }

    public function broadcastOn()
    {
        Log::debug('FragmentProcessed::handle');

        return new Channel('lens.chat');
    }

    public function broadcastAs()
    {
        return 'fragment-processed';
    }
}
