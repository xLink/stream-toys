<?php

namespace App\Events\Battleships;

class BaseRoom
{
    public string $roomStr = '';
    public string $spectatorStr = '';

    public function __construct(
        public string $room
    ) {
        $this->roomStr = implode('.', ['App', 'Battleships', $room]);
        $this->spectatorStr = implode('.', ['App', 'BattleshipsSpectator', $room]);
    }
}
