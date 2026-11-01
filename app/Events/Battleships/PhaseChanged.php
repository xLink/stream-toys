<?php

namespace App\Events\Battleships;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhaseChanged extends BaseRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public string $phase,
        public ?string $turnColor = null,
    ) {
        parent::__construct($room);
    }

    public function broadcastWith(): array
    {
        return [
            'phase' => $this->phase,
            'turnColor' => $this->turnColor,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel($this->roomStr),
        ];
    }
}
