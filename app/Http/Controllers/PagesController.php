<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class PagesController extends Controller
{

    public function getIndex(Request $request): Response
    {
        return Inertia::render('Pokedex', []);
    }

    
}
