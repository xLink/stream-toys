<?php

use App\Http\Controllers\PokedexController;
use App\Http\Controllers\TetrisController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pokedex'], function($router) {

    Route::group(['prefix' => '{pokedex}'], function() {
        Route::get('/', [PokedexController::class, 'getIndex'])->name('pokedex.index');
        Route::get('/catch/{pokemonId}', [PokedexController::class, 'catchPokemon'])->name('pokedex.catch')
            ->where('pokemonId', '[0-9]+');
        Route::get('/stream-label', [PokedexController::class, 'getPokedexNumber'])->name('pokedex.stream-label');
    })->where('pokedex', '[a-zA-Z-]+');


});

Route::group(['prefix' => 'tetris'], function() {
    Route::get('/', [TetrisController::class, 'getIndex'])->name('tetris.index');
    
});
