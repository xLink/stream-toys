<?php

namespace App\Models\Tetris;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'tetris_room';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    public $guarded = [];

    protected $casts = [
        'dexes' => 'array',
        'extraOptions' => 'array',
    ];

    public function players()
    {
        return $this->hasMany(RoomPlayer::class, 'room_id', 'id');
    }

    public function pieces()
    {
        return $this->hasMany(RoomPiece::class, 'room_id', 'id');
    }

    public function toStateArray(?string $userId = null): array
    {
        $extra = $this->extraOptions ?? [];
        $isSharedMode = $this->mode === 'coop';

        $state = [
            'name'                => $this->name,
            'seed'                => $this->seed,
            'mode'                => $this->mode,
            'pokedex'             => $this->dexes ?? [],
            'perRow'              => $extra['perRow'] ?? 10,
            'tetriminosToGenerate'=> $extra['tetriminosToGenerate'] ?? 3,
            'sort'                => $extra['sort'] ?? 'random',
            'selectionType'       => $this->selection_type,
            'perPlayer'           => $extra['perPlayer'] ?? false,
            'history'             => $this->pieces()
                ->where('section', 'history')
                ->whereHas('user')
                ->with('user')
                ->oldest()
                ->get()
                ->map(fn ($p) => [
                    'type'     => $p->type,
                    'rotation' => $p->rotation,
                    'x'        => $p->x,
                    'y'        => $p->y,
                    'username' => $p->user->username,
                ])
                ->values()
                ->toArray(),
        ];

        // coop: shared tracked cells visible to all
        // blackout/vs: only include tracked cells when a user context is provided
        if ($isSharedMode || $userId !== null) {
            $trackedQuery = $this->pieces()->where('section', 'tracked');
            if (!$isSharedMode) {
                $trackedQuery->where('user_id', $userId);
            }
            $state['trackedCells'] = $trackedQuery
                ->get()
                ->map(fn ($p) => ['x' => $p->x, 'y' => $p->y])
                ->values()
                ->toArray();
        }

        return $state;
    }

    public function toPlayersArray(): array
    {
        return $this->players()
            ->whereHas('user')
            ->with('user')
            ->get()
            ->map(fn ($player) => [
                'username' => $player->user->username,
                'color'    => $player->color,
                'owner'    => $player->user_id === $this->user_id,
            ])
            ->values()
            ->toArray();
    }
}
