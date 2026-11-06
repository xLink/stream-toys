<?php

namespace App\Events\Battleships;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class InteractionSync extends BaseRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public string $username,
        public ?array $hoverCell = null,
        public ?string $searchText = null,
    ) {
        parent::__construct($room);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel($this->spectatorStr),
        ];
    }
}
