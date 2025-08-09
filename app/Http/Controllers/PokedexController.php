<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response as iResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\PokedexService;
use Illuminate\Support\Facades\Validator;

class PokedexController extends Controller
{

    public function getIndex(string $pokedex): iResponse
    {
        $pokedexData = app(PokedexService::class)->getData($pokedex);

        return Inertia::render('Pages/Pokedex', [
            'pokedexData' => $pokedexData,
        ]);
    }

    public function catchPokemon(string $pokedex, int $pokemonId): RedirectResponse
    {
        $validated = Validator::make(
                [
                    'pokedex' => $pokedex,
                    'pokemonId' => $pokemonId,
                ], 
                [
                    'pokedex' => 'required|string|exists:pokedex,name',
                    'pokemonId' => 'required|integer|exists:pokemon,id',
                ]
            )
            ->validate()
        ;


        $caught = app(PokedexService::class)
            ->catchPokemon($validated['pokedex'], $validated['pokemonId']);

        if (!$caught) {
            return redirect()
                ->to(route('pokedex.index', ['pokedex' => $validated['pokedex']]))
                ->with('error', 'Failed to catch the Pokémon or it already exists in the Pokedex.');
        }   

        return redirect()
            ->to(route('pokedex.index', ['pokedex' => $validated['pokedex']]))
            ->with('success', 'Pokémon caught successfully!');
    }

    public function getPokedexNumber(string $pokedex): JsonResponse
    {
        $pokedexData = collect(
            app(PokedexService::class)->getData($pokedex)
        );

        $maxCount = $pokedexData->count();
        $hasCount = $pokedexData->filter(fn($item) => $item['is_owned'])->count();

        return response()->json([
            'maxCount' => $maxCount,
            'hasCount' => $hasCount,
        ]);
    }
}
