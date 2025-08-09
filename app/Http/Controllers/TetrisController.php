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

class TetrisController extends Controller
{

    public function getIndex(): iResponse
    {
        $pokedexData = app(PokedexService::class)->getData('_tetris');
        // Logic to show the Tetris game board
        return Inertia::render('Pages/Tetris', [
            'pokedexData' => $pokedexData,
        ]);
    }
}
