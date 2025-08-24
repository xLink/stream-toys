<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\PokedexService;

class WhosThatPokemonController extends Controller
{

    public function getIndex($generation = null): Response
    {
        $pokedexData = app(PokedexService::class)->getPokemonByDex($generation);

        return Inertia::render('Pages/WhosThatPokemon', [
            'generation' => $generation,
            'pokedexData' => $pokedexData,
            'byGen' => [
                'kanto' => count(app(PokedexService::class)->getPokemonByDex('kanto')),
                'johto' => count(app(PokedexService::class)->getPokemonByDex('johto')),
                'hoenn' => count(app(PokedexService::class)->getPokemonByDex('hoenn')),
                'sinnoh' => count(app(PokedexService::class)->getPokemonByDex('sinnoh')),
                'unova' => count(app(PokedexService::class)->getPokemonByDex('unova')),
                'kalos' => count(app(PokedexService::class)->getPokemonByDex('kalos')),
                'alola' => count(app(PokedexService::class)->getPokemonByDex('alola')),
                'galar' => count(app(PokedexService::class)->getPokemonByDex('galar')),
                'paldea' => count(app(PokedexService::class)->getPokemonByDex('paldea')),
            ]
        ]);
    }
}
