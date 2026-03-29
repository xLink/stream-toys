<?php

namespace App\Events\Tetris;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Models\Tetris\Room;

class UpdateBoard extends BaseRoom implements ShouldBroadcastNow 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public string $room,
        public Room $objRoom,
        public bool $clearAll = false,
    )
    {
        parent::__construct($room);
    }

    public function broadcastWith(): array
    {
        $this->objRoom->load('players.user', 'pieces.user');

        $state = $this->objRoom->toStateArray();

        if ($this->clearAll) {
            $state['trackedCells'] = [];
        }

        return [
            'objRoom' => [
                'state'   => $state,
                'players' => $this->objRoom->toPlayersArray(),
            ],
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel($this->roomStr);
    }
}
