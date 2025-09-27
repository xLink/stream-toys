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

    public function getIndex(string|null $room = null): iResponse
    {
        $pokedexData = app(PokedexService::class)->getData('_tetris');

        // Logic to show the Tetris game board
        return Inertia::render('Pages/Tetris', [
            'pokedexData' => $pokedexData,
            'room' => $room ?? '_personal',
        ]);
    }

    public function getPokemonData(string $pokedex): JsonResponse
    {
        $pokedexData = app(PokedexService::class)->getData($pokedex);

        return response()->json($pokedexData);
    }

    public function createNewRoom(Request $request) {
        $room = Room::create([
            'uuid' => Str::uuid()->toString(),
            'players' => json_encode([$request->get('user')])
        ]);

        return response()->json($room);
    }

    public function createRoom(string $room, string $user) {

    }

    public function registerUserToRoom(string $room, string $user) {

    }
    
}
