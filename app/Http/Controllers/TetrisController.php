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
use App\Models\Tetris\Room;
use Illuminate\Support\Str;

class TetrisController extends Controller
{

    public function getIndex(): iResponse
    {
        $pokedexData = app(PokedexService::class)->getData('_tetris');

        // Logic to show the Tetris game board
        return Inertia::render('Pages/Tetris', [
            'pokedexData' => $pokedexData,
            'room' => '_personal',
        ]);
    }

    public function getPokemonData(string $pokedex): JsonResponse
    {
        $pokedexData = app(PokedexService::class)->getData($pokedex);

        return response()->json($pokedexData);
    }

    public function getTestBoard(string $shape = null): iResponse
    {
        $pokedexData = app(PokedexService::class)->getPokemonByDex('kanto');
        $availableShapes = ['triangle', 'diamond', 'donut', 'crazy'];

        if ($shape && !in_array($shape, $availableShapes)) {
            abort(404, "Unknown shape: {$shape}");
        }

        return Inertia::render('Tetris/TestBoard', [
            'pokedexData' => $pokedexData,
            'shape' => $shape,
            'shapes' => $availableShapes,
            'perRow' => 15,
            'seed' => 42,
        ]);
    }
}