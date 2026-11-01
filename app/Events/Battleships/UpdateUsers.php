<?php

namespace App\Events\Battleships;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateUsers extends BaseRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public array $users,
    ) {
        parent::__construct($room);
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel($this->roomStr),
        ];
    }
}
