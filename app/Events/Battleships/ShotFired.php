<?php

namespace App\Events\Battleships;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShotFired extends BaseRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public array $cell,
        public string $shooterColor,
        public bool $hit,
        public ?string $sunkShip = null,
        public ?string $turnColor = null,
        public ?array $sunkShipCells = null,
    ) {
        parent::__construct($room);
    }

    public function broadcastWith(): array
    {
        return [
            'cell' => $this->cell,
            'shooterColor' => $this->shooterColor,
            'hit' => $this->hit,
            'sunkShip' => $this->sunkShip,
            'sunkShipCells' => $this->sunkShipCells,
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
