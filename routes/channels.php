<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.TetrisMP.{uuid}', function ($user, $uuid) {
    return [
        'id' => $user->id, 
        'username' => $user->username,
    ];
});

// Broadcast::channel('App.Tetris.{room}', fn() => 'true');