<?php

namespace App\Events\Battleships;

use App\Models\Tetris\Room;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettingsUpdated extends BaseRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public Room $objRoom,
    ) {
        parent::__construct($room);
    }

    public function broadcastWith(): array
    {
        return [
            'state' => $this->objRoom->toBattleshipsState(),
            'players' => $this->objRoom->toPlayersArray(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel($this->roomStr),
        ];
    }
}
