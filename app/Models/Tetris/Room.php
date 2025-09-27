<?php

namespace App\Models\Tetris;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'tetris_rooms';
    protected $primaryKey = 'uuid';
    public $timestamps = true;

    
}
