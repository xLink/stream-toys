<?php

namespace App\Events\Battleships;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerReady extends BaseRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public string $username,
        public bool $ready,
    ) {
        parent::__construct($room);
    }

    public function broadcastWith(): array
    {
        return [
            'username' => $this->username,
            'ready' => $this->ready,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel($this->roomStr),
        ];
    }
}
