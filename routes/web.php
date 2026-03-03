<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function() {
    Route::get('/discord', [Controllers\AuthController::class, 'login'])->name('auth.discord');
    Route::get('/discord/callback', [Controllers\AuthController::class, 'callback'])->name('auth.discord.callback');
    Route::get('/logout', [Controllers\AuthController::class, 'logout'])->name('auth.logout');

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

    Route::group(['prefix' => '{room}'], function() {
        Route::get('/', [Controllers\TetrisMPController::class, 'loadMPRoom'])->name('tetris-mp.room');

        Route::post('/join', [Controllers\TetrisMPController::class, 'postJoinRoom'])->name('tetris-mp.room-join');
        // Route::post('/online-users', [Controllers\TetrisMPController::class, 'postOnlineUsers']);
    })->whereUuid('room');
});

Route::group(['prefix' => 'whos-that-pokemon'], function() {
    Route::get('/', [Controllers\WhosThatPokemonController::class, 'getIndex'])->name('whos-that-pokemon.index');
    Route::get('/{generation}', [Controllers\WhosThatPokemonController::class, 'getIndex'])->name('whos-that-pokemon.index');
});

Route::group(['prefix' => 'hp-bar'], function() {
    Route::get('/', [Controllers\HPBarController::class, 'getIndex'])->name('hp-bar.index');

    Route::group(['prefix' => '{bar}'], function() {
        Route::get('/', [Controllers\HPBarController::class, 'getIndex'])->name('hp-bar.index');
        Route::any('/update', [Controllers\HPBarController::class, 'update'])->name('hp-bar.update');
    })->where('bar', '[a-zA-Z0-9-_]+');
});