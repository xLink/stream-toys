<?php

namespace App\Events\Battleships;

class BaseRoom
{
    public string $roomStr = '';

    public function __construct(
        public string $room
    ) {
        $this->roomStr = implode('.', ['App', 'Battleships', $room]);
    }
}
