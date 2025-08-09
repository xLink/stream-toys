<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pokedex extends Model
{
    protected $table = 'pokedex';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $fillable = ['uuid', 'name'];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i:s',
        'updated_at' => 'datetime:d-m-Y H:i:s',
    ];

    public function pokemon()
    {
        return $this->hasManyThrough(
            Pokemon::class, 
            PokedexPokemon::class, 
            'pokedex_id', 
            'id', 
            'uuid', 
            'pokemon_id'
        );
    }
}
