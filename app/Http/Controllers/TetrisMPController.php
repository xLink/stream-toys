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

class TetrisMPController extends Controller
{

    public function getIndex(string|null $room = null): iResponse
    {
        


        $pokedexData = app(PokedexService::class)-> getAll();

        // Logic to show the Tetris game board
        return Inertia::render('Pages/TetrisMP', [
            'pokedexData' => $pokedexData,
            'room' => $room ?? '_personal',
        ]);
    }
}