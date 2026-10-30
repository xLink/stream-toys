<?php

namespace App\Events\Tetris;

class BaseRoom
{
    public string $roomStr = '';
    public string $spectatorStr = '';

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $room
    ) {
        $this->roomStr = implode('.', ['App', 'TetrisMP', $room]);
        $this->spectatorStr = implode('.', ['App', 'TetrisMPSpectator', $room]);
    }

}
