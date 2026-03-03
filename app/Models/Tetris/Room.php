<?php

namespace App\Models\Tetris;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'tetris_room';
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    public $guarded = [];
}
