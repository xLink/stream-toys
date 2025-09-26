<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HPBars extends Model
{
    protected $table = 'hp_bars';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $fillable = ['uuid', 'name', 'current_hp', 'max_hp'];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i:s',
        'updated_at' => 'datetime:d-m-Y H:i:s',
    ];
}
