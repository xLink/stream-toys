<?php

namespace App\Events\Battleships;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipsPlaced extends BaseRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public string $username,
        public int $count,
    ) {
        parent::__construct($room);
    }

    public function broadcastWith(): array
    {
        return [
            'username' => $this->username,
            'count' => $this->count,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel($this->roomStr),
        ];
    }
}
