<?php

namespace App\Events\HPBar;

class BaseRoom
{
    public string $roomStr = '';

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $room
    ) {
        $this->roomStr = implode('.', ['App', 'HPBar', $room]);
    }

}
