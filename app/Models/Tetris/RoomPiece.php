<?php

namespace App\Models\Tetris;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RoomPiece extends Model
{
    protected $table = 'tetris_room_pieces';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $guarded = [];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
