<?php

namespace App\Models\Tetris;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Room extends Model
{
    protected $table = 'tetris_room';
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    public $guarded = [];

    protected function players(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true) ?? [],
            set: fn ($value) => json_encode($value),
        );
    }

    protected function state(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $json = json_decode($value, true) ?? [];
                return [
                    ...$json,
                    'pokedex' => $this->jsonOrArray($json['pokedex'] ?? []),
                    'trackedCells' => $this->jsonOrArray($json['trackedCells'] ?? []),
                    'history' => $this->jsonOrArray($json['history'] ?? []),
                ];
            },
            set: fn ($value) => json_encode($value),
        );
    }


    public function jsonOrArray($value) {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
