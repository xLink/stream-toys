<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models;
use App\Services\PokedexService;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pokedexData = (new PokedexService())->catchPokemon('linky-stream', 25);
        dd($pokedexData);


        $pokemon = Models\Pokemon::query()
            ->get()
            ->map(function ($pokemon) {
                return [
                    'id' => $pokemon->id,
                    'name' => $pokemon->name,
                    'image' => implode('/', ['images', 'sprites', $pokemon->id.'.png']),
                    'is_owned' => false,
                ];
            })
            ->keyBy('id')
            ->toArray()
        ;

        Models\Pokedex::query()
            ->with('pokemon.exists')
            ->where('name', 'linky-stream')
            ->first()
            ->pokemon
            ->each(function ($pokedexEntry) use (&$pokemon) {
                if (isset($pokemon[$pokedexEntry->id])) {
                    $pokemon[$pokedexEntry->id]['is_owned'] = true;
                }
            });
        ;

    }
}
