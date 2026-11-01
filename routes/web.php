<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function() {
    Route::get('/discord', [Controllers\AuthController::class, 'login'])->name('auth.discord');
    Route::get('/discord/callback', [Controllers\AuthController::class, 'callback'])->name('auth.discord.callback');
    Route::get('/logout', [Controllers\AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/login-success', [Controllers\AuthController::class, 'loginSuccess'])->name('auth.login-success');
    Route::get('/{uid}', [Controllers\AuthController::class, 'getUser'])->name('auth.user')->whereUuid('uid');
});

Route::group(['prefix' => 'pokedex'], function($router) {

    Route::group(['prefix' => '{pokedex}'], function() {
        Route::get('/', [Controllers\PokedexController::class, 'getIndex'])->name('pokedex.index');
        Route::get('/catch/{pokemonId}', [Controllers\PokedexController::class, 'catchPokemon'])->name('pokedex.catch')
            ->where('pokemonId', '[0-9]+');
        Route::get('/stream-label', [Controllers\PokedexController::class, 'getPokedexNumber'])->name('pokedex.stream-label');
    })->where('pokedex', '[a-zA-Z-]+');
});

Route::group(['prefix' => 'tetris'], function() {
    Route::get('/', [Controllers\TetrisController::class, 'getIndex'])->name('tetris.index');
});

Route::group(['prefix' => 'tetris-mp'], function() {
    Route::get('/', [Controllers\TetrisMPController::class, 'getIndex'])->name('tetris-mp.index');
    Route::post('/', [Controllers\TetrisMPController::class, 'postCreateRoom']);

    Route::get('/{room}/spectate', [Controllers\TetrisMPController::class, 'spectateRoom'])->name('tetris-mp.spectate')->whereUuid('room');

    Route::group(['prefix' => '{room}'], function() {
        Route::get('/', [Controllers\TetrisMPController::class, 'loadMPRoom'])->name('tetris-mp.room');

        Route::post('/join', [Controllers\TetrisMPController::class, 'postJoinRoom'])->name('tetris-mp.room-join');
        Route::post('/save', [Controllers\TetrisMPController::class, 'postSaveRoom'])->name('tetris-mp.room-save');
        Route::post('/add-new-cell', [Controllers\TetrisMPController::class, 'postAddNewCell'])->name('tetris-mp.room-add-new-cell');
        Route::post('/remove-last-cell', [Controllers\TetrisMPController::class, 'postRemoveLastCell'])->name('tetris-mp.room-remove-last-cell');
        Route::post('/save-tracked', [Controllers\TetrisMPController::class, 'postSaveTrackedCells'])->name('tetris-mp.room-save-tracked');
        Route::post('/update-player', [Controllers\TetrisMPController::class, 'postUpdatePlayer'])->name('tetris-mp.room-update-player');
        Route::get('/regenerate-board', [Controllers\TetrisMPController::class, 'postRegenerateBoard'])->name('tetris-mp.room-regenerate');
        // Route::post('/online-users', [Controllers\TetrisMPController::class, 'postOnlineUsers']);
    })->whereUuid('room');
});

Route::group(['prefix' => 'battleships'], function() {
    Route::get('/', [Controllers\BattleshipsController::class, 'getIndex'])->name('battleships.index');
    Route::post('/', [Controllers\BattleshipsController::class, 'postCreateRoom']);

    Route::group(['prefix' => '{room}'], function() {
        Route::get('/', [Controllers\BattleshipsController::class, 'loadRoom'])->name('battleships.room');
        Route::post('/join', [Controllers\BattleshipsController::class, 'postJoinRoom'])->name('battleships.join');
        Route::post('/save', [Controllers\BattleshipsController::class, 'postSaveRoom'])->name('battleships.save');
        Route::post('/update-player', [Controllers\BattleshipsController::class, 'postUpdatePlayer'])->name('battleships.update-player');
        Route::post('/start-game', [Controllers\BattleshipsController::class, 'postStartGame'])->name('battleships.start-game');
        Route::post('/place-ship', [Controllers\BattleshipsController::class, 'postPlaceShip'])->name('battleships.place-ship');
        Route::post('/remove-ship', [Controllers\BattleshipsController::class, 'postRemoveShip'])->name('battleships.remove-ship');
        Route::post('/ready', [Controllers\BattleshipsController::class, 'postReady'])->name('battleships.ready');
        Route::post('/fire', [Controllers\BattleshipsController::class, 'postFireShot'])->name('battleships.fire');
        Route::post('/leave', [Controllers\BattleshipsController::class, 'postLeaveRoom'])->name('battleships.leave');
        Route::post('/kick', [Controllers\BattleshipsController::class, 'postKickPlayer'])->name('battleships.kick');
        Route::get('/spectate', [Controllers\BattleshipsController::class, 'spectateRoom'])->name('battleships.spectate');
    })->whereUuid('room');
});

Route::group(['prefix' => 'whos-that-pokemon'], function() {
    Route::get('/', [Controllers\WhosThatPokemonController::class, 'getIndex'])->name('whos-that-pokemon.index');
    Route::get('/{generation}', [Controllers\WhosThatPokemonController::class, 'getIndex'])->name('whos-that-pokemon.index');
});

Route::post('/broadcasting/auth/hp-bar', function (\Illuminate\Http\Request $request) {
    $channelName = $request->input('channel_name', '');
    $bare = str_replace('private-', '', $channelName);
    if (!str_starts_with($bare, 'App.HPBar.')) {
        abort(403);
    }
    $pusher = new \Pusher\Pusher(
        config('broadcasting.connections.reverb.key'),
        config('broadcasting.connections.reverb.secret'),
        config('broadcasting.connections.reverb.app_id'),
    );
    return response()->json(
        json_decode($pusher->authorizeChannel($channelName, $request->input('socket_id')), true)
    );
})->name('hp-bar.broadcast-auth');

Route::group(['prefix' => 'hp-bar'], function() {
    Route::get('/', [Controllers\HPBarController::class, 'getIndex'])->name('hp-bar.index');
    Route::get('/setup', [Controllers\HPBarController::class, 'getSetup'])->name('hp-bar.setup');
    Route::post('/setup', [Controllers\HPBarController::class, 'postSetup']);

    Route::group(['prefix' => '{bar}'], function() {
        Route::get('/', [Controllers\HPBarController::class, 'getIndex'])->name('hp-bar.index');
        Route::any('/update', [Controllers\HPBarController::class, 'update'])->name('hp-bar.update');
        Route::post('/options', [Controllers\HPBarController::class, 'postOptions'])->name('hp-bar.options');
    })->where('bar', '[a-zA-Z0-9-_]+');
});