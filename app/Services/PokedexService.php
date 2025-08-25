<?php

namespace App\Services;

use App\Models\Pokemon;
use App\Models\Pokedex;
use App\Models\PokedexPokemon;

class PokedexService 
{
    public $dexById = [
      'kanto'   => [0,   151],
      'johto'   => [151, 251],
      'hoenn'   => [251, 386],
      'sinnoh'  => [386, 493],
      'unova'   => [493, 649],
      'kalos'   => [649, 721],
      'alola'   => [721, 809],
      'galar'   => [809, 905],
      'paldea'  => [905, 1025],
    ];

    public function getData(string $stream, string $generation = 'all'): array
    {
        $pokemon = $this->getPokemonByDex($generation === 'all' ? null : $generation);

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

    public function getPokemonByDex(?string $generation): array
    {
        return Pokemon::query()
            ->when(!in_array($generation, ['all', null]), function ($query) use ($generation) {
                $query->whereBetween('id', $this->dexById[$generation]);
            })
            ->get()
            ->map(function ($pokemon) {
                return [
                    'id' => $pokemon->id,
                    'name' => $pokemon->name,
                    'image' => '/'. implode('/', ['images', 'sprites', 'home', $pokemon->id.'.png']),
                    'is_owned' => false,
                ];
            })
            ->keyBy('id')
            ->toArray();
    }
}