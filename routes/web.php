<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

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

Route::group(['prefix' => 'whos-that-pokemon'], function() {
    Route::get('/', [Controllers\WhosThatPokemonController::class, 'getIndex'])->name('whos-that-pokemon.index');
    Route::get('/{generation}', [Controllers\WhosThatPokemonController::class, 'getIndex'])->name('whos-that-pokemon.index');
});
