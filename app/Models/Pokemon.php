<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pokemon extends Model
{
    protected $table = 'pokemon';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['uuid', 'name'];

    public function exists() {
        return $this->hasOneThrough(
            Pokedex::class, 
            PokedexPokemon::class, 
            'pokemon_id', // Foreign key on PokedexPokemon table
            'uuid', // Foreign key on Pokedex table
            'id', // Local key on Pokemon table
            'pokedex_id' // Local key on PokedexPokemon table
        );
    }

}
