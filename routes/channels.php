<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.TetrisMP.{uuid}', function ($room, $uuid) {
    logger( 'Broadcasting App.TetrisMP.{uuid} channel', func_get_args());
    return ['username' => $room->username, 'color' => $room->color];
});

// Broadcast::channel('App.Tetris.{room}', fn() => 'true');