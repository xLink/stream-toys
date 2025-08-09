<?php

namespace App\Services;

use App\Models\Pokemon;
use App\Models\Pokedex;
use App\Models\PokedexPokemon;

class PokedexService 
{

    
    public function getData(string $stream): array
    {
        $pokemon = Pokemon::query()
            ->get()
            ->map(function ($pokemon) {
                return [
                    'id' => $pokemon->id,
                    'name' => $pokemon->name,
                    'image' => '/'. implode('/', ['images', 'sprites', $pokemon->id.'.png']),
                    'is_owned' => false,
                ];
            })
            ->keyBy('id')
            ->toArray()
        ;

        Pokedex::query()
            ->with('pokemon.exists')
            ->where('name', $stream)
            ->first()
            ?->pokemon
            ->each(function ($pokedexEntry) use (&$pokemon) {
                if (isset($pokemon[$pokedexEntry->id])) {
                    $pokemon[$pokedexEntry->id]['is_owned'] = true;
                }
            });
        ;

        return $pokemon;
    }

    public function catchPokemon(string $pokedexName, int $pokemonId): bool
    {
        $pokedex = Pokedex::where('name', $pokedexName)->firstOrFail();
        $pokemon = Pokemon::findOrFail($pokemonId);

        if ($pokedex->pokemon()->where('pokemon_id', $pokemon->id)->exists()) {
            return false;
        }

        try {
            PokedexPokemon::insert([
                'pokedex_id' => $pokedex->uuid,
                'pokemon_id' => $pokemon->id,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}