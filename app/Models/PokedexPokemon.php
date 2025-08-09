<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PokedexPokemon extends Model
{
    
    protected $table = 'pokedex_pokemon';
    protected $primaryKey = ['pokedex_id', 'pokemon_id'];
    public $timestamps = false;
    protected $fillable = ['pokedex_id', 'pokemon_id'];

    public function pokemon()
    {
        return $this->belongsTo(Pokemon::class, 'pokemon_id');
    }

    public function pokedex()
    {
        return $this->belongsTo(Pokedex::class, 'pokedex_id');
    }
}
