<?php

namespace App\Events\Tetris;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateTrackedCells extends BaseRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public array $trackedCells
    ) {
        parent::__construct($room);
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel($this->roomStr),
            new Channel($this->spectatorStr),
        ];
    }
}
