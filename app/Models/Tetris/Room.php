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
            'showGridCoords'      => $extra['showGridCoords'] ?? 'true',
            'cellSize'            => $extra['cellSize'] ?? 60,
            'cellSpacing'         => $extra['cellSpacing'] ?? 1,
            'boardStatsPosition'  => $extra['boardStatsPosition'] ?? 'top',
            'shuffleBlanks'       => $extra['shuffleBlanks'] ?? false,
            'blankRows'           => $extra['blankRows'] ?? false,
            'colorBlanks'         => $extra['colorBlanks'] ?? false,
            'uniquePieces'        => $extra['uniquePieces'] ?? false,
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

    public function toBattleshipsState(?string $userId = null): array
    {
        $extra = $this->extraOptions ?? [];

        $state = [
            'name'               => $this->name,
            'seed'               => $this->seed,
            'mode'               => $this->mode,
            'phase'              => $this->phase ?? 'lobby',
            'turnColor'          => $this->turn_color,
            'pokedex'            => $this->dexes ?? [],
            'perRow'             => $extra['perRow'] ?? 10,
            'sort'               => $extra['sort'] ?? 'random',
            'showGridCoords'     => $extra['showGridCoords'] ?? 'true',
            'cellSize'           => $extra['cellSize'] ?? 60,
            'cellSpacing'        => $extra['cellSpacing'] ?? 1,
            'boardStatsPosition' => $extra['boardStatsPosition'] ?? 'bottom',
            'shuffleBlanks'      => $extra['shuffleBlanks'] ?? false,
            'blankRows'          => $extra['blankRows'] ?? false,
            'shipCounts'         => $extra['shipCounts'] ?? null,
            'winner'             => $extra['winner'] ?? null,
        ];

        if ($userId !== null) {
            $myColor = $this->players()->where('user_id', $userId)->value('color');
            $teamPlayerIds = $myColor
                ? $this->players()->where('color', $myColor)->pluck('user_id')->toArray()
                : [$userId];

            $state['myShips'] = $this->pieces()
                ->where('section', 'ship')
                ->whereIn('user_id', $teamPlayerIds)
                ->get()
                ->groupBy('type')
                ->map(fn ($cells, $type) => [
                    'shipType'    => $type,
                    'orientation' => $cells->first()->rotation,
                    'cells'       => $cells->map(fn ($c) => ['x' => $c->x, 'y' => $c->y])->values()->toArray(),
                ])
                ->values()
                ->toArray();
        }

        $state['shots'] = $this->pieces()
            ->where('section', 'shot')
            ->whereHas('user')
            ->with('user')
            ->get()
            ->map(fn ($p) => [
                'x'            => $p->x,
                'y'            => $p->y,
                'hit'          => (bool) $p->hit,
                'shooterColor' => $this->players()->where('user_id', $p->user_id)->value('color'),
            ])
            ->values()
            ->toArray();

        $allShips = $this->pieces()->where('section', 'ship')->get()
            ->groupBy(fn ($p) => $p->user_id . ':' . $p->type);
        $hitShots = $this->pieces()->where('section', 'shot')->where('hit', true)->get();
        $hitSet = $hitShots->map(fn ($s) => $s->x . ',' . $s->y)->flip();

        $sunkCells = [];
        foreach ($allShips as $key => $cells) {
            $allHit = $cells->every(fn ($c) => $hitSet->has($c->x . ',' . $c->y));
            if ($allHit) {
                foreach ($cells as $c) {
                    $sunkCells[] = $c->x . ',' . $c->y;
                }
            }
        }
        $state['sunkCells'] = $sunkCells;

        $state['readyPlayers'] = $this->players()
            ->whereHas('user')
            ->with('user')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->user->username => (bool) $p->ready])
            ->toArray();

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
