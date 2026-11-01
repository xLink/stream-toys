<?php

namespace App\Http\Controllers;

use App\Events\Battleships;
use App\Events\Tetris;
use Inertia\Inertia;
use Inertia\Response as iResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\PokedexService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Tetris\Room;
use App\Models\Tetris\RoomPiece;
use Illuminate\Support\Str;

class BattleshipsController extends Controller
{
    protected function getExpandedShipDefs(Room $room): array
    {
        $baseDefs = $this->options['shipDefs'];
        $counts = ($room->extraOptions ?? [])['shipCounts'] ?? [];
        $expanded = [];

        foreach ($baseDefs as $key => $def) {
            $count = (int) ($counts[$key] ?? 1);
            if ($count === 1) {
                $expanded[$key] = $def;
            } else {
                for ($i = 1; $i <= $count; $i++) {
                    $expanded["{$key}_{$i}"] = array_merge($def, ['baseType' => $key]);
                }
            }
        }

        return $expanded;
    }

    protected array $options = [
        'colors' => [
            '#93c5fd' => '1',
            '#0ea5e9' => '2',
            '#f97316' => '3',
            '#eab308' => '4',
            '#22c55e' => '5',
            '#ef4444' => '6',
            '#a855f7' => '7',
            '#6b7280' => '8',
        ],
        'pokedexes' => [
            'kanto'   => 'Kanto (Gen 1)',
            'johto'   => 'Johto (Gen 2)',
            'hoenn'   => 'Hoenn (Gen 3)',
            'sinnoh'  => 'Sinnoh (Gen 4)',
            'unova'   => 'Unova (Gen 5)',
            'kalos'   => 'Kalos (Gen 6)',
            'alola'   => 'Alola (Gen 7)',
            'galar'   => 'Galar (Gen 8)',
            'paldea'  => 'Paldea (Gen 9)',
        ],
        'sort' => [
            'random'  => 'Random',
            'byId'    => 'By ID',
            'byName'  => 'By Name',
        ],
        'boolean' => [
            'true'    => 'True',
            'false'   => 'False',
        ],
        'shipDefs' => [
            'carrier'    => ['size' => 5, 'label' => 'Carrier'],
            'battleship' => ['size' => 4, 'label' => 'Battleship'],
            'cruiser'    => ['size' => 3, 'label' => 'Cruiser'],
            'submarine'  => ['size' => 3, 'label' => 'Submarine'],
            'destroyer'  => ['size' => 2, 'label' => 'Destroyer'],
        ],
    ];

    public function getIndex(): iResponse
    {
        return Inertia::render('Battleships/Index', [
            'optionsObjects' => $this->options,
        ]);
    }

    public function postCreateRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'seed'    => 'nullable|integer',
            'color'   => 'required|string|max:50',
            'pokedex' => 'required|array',
            'pokedex.*' => 'string|in:' . implode(',', array_keys($this->options['pokedexes'])),
            'perRow'  => 'required|integer|min:1|max:40',
            'shipCounts' => 'nullable|array',
            'shipCounts.*' => 'integer|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $room = Room::create([
            'id'             => Str::uuid(),
            'name'           => $data['name'],
            'user_id'        => auth()->id(),
            'mode'           => 'battleships',
            'seed'           => $data['seed'] ?? rand(100000, 999999),
            'dexes'          => $data['pokedex'],
            'selection_type' => 'single',
            'phase'          => 'lobby',
            'extraOptions'   => [
                'perRow'     => $data['perRow'],
                'shipCounts' => $data['shipCounts'] ?? [],
            ],
        ]);

        $room->players()->create([
            'user_id' => auth()->id(),
            'color'   => $data['color'],
        ]);

        return response()->json(['room-uuid' => $room->id], 200);
    }

    public function loadRoom(Room $room): iResponse
    {
        $pokedexData = app(PokedexService::class)
            ->getPokemonByMultiDex($room->dexes ?? []);

        return Inertia::render('Battleships/Room', [
            'pokedexData'    => $pokedexData,
            'optionsObjects' => $this->options,
            'uuid'           => $room->id,
            'players'        => $room->toPlayersArray(),
            'state'          => $room->toBattleshipsState(auth()->id()),
        ]);
    }

    public function spectateRoom(Room $room): iResponse
    {
        $pokedexData = app(PokedexService::class)
            ->getPokemonByMultiDex($room->dexes ?? []);

        return Inertia::render('Battleships/Spectate', [
            'pokedexData'    => $pokedexData,
            'optionsObjects' => $this->options,
            'uuid'           => $room->id,
            'players'        => $room->toPlayersArray(),
            'state'          => $room->toBattleshipsState(),
        ]);
    }

    public function postJoinRoom(Room $room, Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Authentication required'], 401);
        }

        $validator = Validator::make($request->all(), [
            'color' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();

        if ($room->players()->where('user_id', $userId)->exists()) {
            return response()->json([
                'message' => 'Player already in room',
                'players' => $room->toPlayersArray(),
            ], 401);
        }

        if ($room->phase !== 'lobby') {
            return response()->json(['message' => 'Game already started'], 403);
        }

        $room->players()->create([
            'user_id' => $userId,
            'color'   => $request->color,
        ]);

        $players = $room->toPlayersArray();

        broadcast(
            new Battleships\UpdateUsers($room->id, $players)
        )->toOthers();

        return response()->json([
            'message' => 'Joined room successfully',
            'players' => $players,
        ], 200);
    }

    public function postLeaveRoom(Room $room): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Authentication required'], 401);
        }

        if ($room->phase !== 'lobby') {
            return response()->json(['message' => 'Cannot leave after game has started'], 403);
        }

        $player = $room->players()->where('user_id', auth()->id())->first();
        if (!$player) {
            return response()->json(['message' => 'Not in this room'], 404);
        }

        if ($room->user_id === auth()->id()) {
            return response()->json(['message' => 'Owner cannot leave the room'], 403);
        }

        $room->players()->where('user_id', auth()->id())->delete();
        $players = $room->toPlayersArray();

        broadcast(new Battleships\UpdateUsers($room->id, $players));

        return response()->json(['message' => 'Left room', 'players' => $players]);
    }

    public function postKickPlayer(Room $room, Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Authentication required'], 401);
        }

        if ($room->user_id !== auth()->id()) {
            return response()->json(['message' => 'Only the owner can kick players'], 403);
        }

        if ($room->phase !== 'lobby') {
            return response()->json(['message' => 'Cannot kick after game has started'], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $player = $room->players()
            ->whereHas('user', fn ($q) => $q->where('username', $request->username))
            ->first();

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        if ($player->user_id === auth()->id()) {
            return response()->json(['message' => 'Cannot kick yourself'], 403);
        }

        $room->players()->where('user_id', $player->user_id)->delete();
        $players = $room->toPlayersArray();

        broadcast(new Battleships\UpdateUsers($room->id, $players));

        return response()->json(['message' => 'Player kicked', 'players' => $players]);
    }

    public function postUpdatePlayer(Room $room, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'color'    => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $updated = $room->players()
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(username) = ?', [strtolower($data['username'])]))
            ->update(['color' => $data['color']]);

        if ($updated) {
            $players = $room->toPlayersArray();
            broadcast(new Battleships\UpdateUsers($room->id, $players));
            return response()->json([
                'message' => 'Saved successfully',
                'players' => $players,
            ], 200);
        }

        return response()->json(['message' => 'Failed to save'], 500);
    }

    public function postSaveRoom(Room $room, Request $request): JsonResponse
    {
        if ($room->phase !== 'lobby') {
            return response()->json(['message' => 'Cannot change settings after game started'], 403);
        }

        if ((string) $room->user_id !== (string) auth()->id()) {
            return response()->json(['message' => 'Only the room owner can change settings'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'seed'    => 'required|string',
            'pokedex' => 'required|string',
            'perRow'  => 'required|string',
            'sort'    => 'nullable|string',
            'shipCounts' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $pokedex = json_decode($data['pokedex'], true) ?? [];

        $room->name  = $data['name'];
        $room->seed  = $data['seed'];
        $room->dexes = $pokedex;
        $room->extraOptions = [
            'perRow'             => $data['perRow'],
            'sort'               => $data['sort'] ?? 'random',
            'showGridCoords'     => $request->input('showGridCoords', 'true'),
            'cellSize'           => (int) $request->input('cellSize', 60),
            'cellSpacing'        => (int) $request->input('cellSpacing', 1),
            'boardStatsPosition' => $request->input('boardStatsPosition', 'top'),
            'shuffleBlanks'      => $request->input('shuffleBlanks', 'false') === 'true',
            'blankRows'          => $request->input('blankRows', 'false') === 'true',
            'shipCounts'         => json_decode($request->input('shipCounts', '{}'), true) ?: [],
        ];
        $room->save();

        $pokedexData = app(PokedexService::class)
            ->getPokemonByMultiDex($room->dexes ?? []);

        broadcast(
            new Battleships\SettingsUpdated($room->id, $room)
        )->toOthers();

        return response()->json([
            'message'     => 'Room settings saved',
            'state'       => $room->toBattleshipsState(auth()->id()),
            'pokedexData' => $pokedexData,
        ]);
    }

    public function postStartGame(Room $room): JsonResponse
    {
        if ((string) $room->user_id !== (string) auth()->id()) {
            return response()->json(['message' => 'Only the room owner can start the game'], 403);
        }

        if ($room->phase !== 'lobby') {
            return response()->json(['message' => 'Game already started'], 403);
        }

        $teamColors = $this->getTeamColors($room);
        if (count($teamColors) < 2) {
            return response()->json(['message' => 'Need at least 2 different team colors to start'], 422);
        }

        $room->phase = 'setup';
        $room->save();

        broadcast(
            new Battleships\PhaseChanged($room->id, 'setup')
        )->toOthers();

        return response()->json(['message' => 'Game started', 'phase' => 'setup']);
    }

    public function postPlaceShip(Room $room, Request $request): JsonResponse
    {
        if ($room->phase !== 'setup') {
            return response()->json(['message' => 'Not in setup phase'], 403);
        }

        $expandedDefs = $this->getExpandedShipDefs($room);

        $validator = Validator::make($request->all(), [
            'shipType'    => 'required|string|in:' . implode(',', array_keys($expandedDefs)),
            'cells'       => 'required|array',
            'cells.*.x'   => 'required|integer|min:0',
            'cells.*.y'   => 'required|integer|min:0',
            'orientation' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $shipType = $data['shipType'];
        $cells = $data['cells'];
        $expectedSize = $expandedDefs[$shipType]['size'];

        if (count($cells) !== $expectedSize) {
            return response()->json(['message' => "Ship {$shipType} requires exactly {$expectedSize} cells"], 422);
        }

        $userId = auth()->id();

        $alreadyPlaced = $room->pieces()
            ->where('user_id', $userId)
            ->where('section', 'ship')
            ->where('type', $shipType)
            ->exists();

        if ($alreadyPlaced) {
            return response()->json(['message' => "Ship {$shipType} already placed"], 422);
        }

        $existingShipCells = $room->pieces()
            ->where('user_id', $userId)
            ->where('section', 'ship')
            ->get()
            ->map(fn ($p) => $p->x . ',' . $p->y)
            ->toArray();

        foreach ($cells as $cell) {
            $key = $cell['x'] . ',' . $cell['y'];
            if (in_array($key, $existingShipCells)) {
                return response()->json(['message' => 'Ship overlaps with an existing ship'], 422);
            }
        }

        $now = now();
        $pieces = [];
        foreach ($cells as $cell) {
            $pieces[] = [
                'id'       => Str::uuid(),
                'room_id'  => $room->id,
                'user_id'  => $userId,
                'type'     => $shipType,
                'rotation' => $data['orientation'],
                'x'        => $cell['x'],
                'y'        => $cell['y'],
                'section'  => 'ship',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        RoomPiece::insert($pieces);

        $shipCount = $room->pieces()
            ->where('user_id', $userId)
            ->where('section', 'ship')
            ->distinct('type')
            ->count('type');

        broadcast(
            new Battleships\ShipsPlaced($room->id, auth()->user()->username, $shipCount)
        )->toOthers();

        return response()->json(['message' => 'Ship placed', 'shipCount' => $shipCount]);
    }

    public function postRemoveShip(Room $room, Request $request): JsonResponse
    {
        if ($room->phase !== 'setup') {
            return response()->json(['message' => 'Not in setup phase'], 403);
        }

        $expandedDefs = $this->getExpandedShipDefs($room);

        $validator = Validator::make($request->all(), [
            'shipType' => 'required|string|in:' . implode(',', array_keys($expandedDefs)),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();

        $room->pieces()
            ->where('user_id', $userId)
            ->where('section', 'ship')
            ->where('type', $request->shipType)
            ->delete();

        $room->players()
            ->where('user_id', $userId)
            ->update(['ready' => false]);

        $shipCount = $room->pieces()
            ->where('user_id', $userId)
            ->where('section', 'ship')
            ->distinct('type')
            ->count('type');

        broadcast(
            new Battleships\ShipsPlaced($room->id, auth()->user()->username, $shipCount)
        )->toOthers();

        return response()->json(['message' => 'Ship removed', 'shipCount' => $shipCount]);
    }

    public function postReady(Room $room): JsonResponse
    {
        if ($room->phase !== 'setup') {
            return response()->json(['message' => 'Not in setup phase'], 403);
        }

        $userId = auth()->id();
        $player = $room->players()->where('user_id', $userId)->first();

        if (!$player) {
            return response()->json(['message' => 'Player not in room'], 403);
        }

        $shipCount = $room->pieces()
            ->where('user_id', $userId)
            ->where('section', 'ship')
            ->distinct('type')
            ->count('type');

        $totalShips = count($this->getExpandedShipDefs($room));

        if ($shipCount < $totalShips) {
            return response()->json(['message' => "Place all {$totalShips} ships before readying up"], 422);
        }

        $newReady = !$player->ready;
        $room->players()
            ->where('user_id', $userId)
            ->update(['ready' => $newReady]);
        $player->ready = $newReady;

        broadcast(
            new Battleships\PlayerReady($room->id, auth()->user()->username, $player->ready)
        )->toOthers();

        $allReady = !$room->players()->where('ready', false)->exists();

        if ($allReady) {
            $teamColors = $this->getTeamColors($room);
            $room->phase = 'battle';
            $room->turn_color = $teamColors[0];
            $room->save();

            broadcast(
                new Battleships\PhaseChanged($room->id, 'battle', $teamColors[0])
            )->toOthers();
        }

        return response()->json([
            'message' => 'Ready status updated',
            'ready'   => $player->ready,
            'phase'   => $room->phase,
        ]);
    }

    public function postFireShot(Room $room, Request $request): JsonResponse
    {
        if ($room->phase !== 'battle') {
            return response()->json(['message' => 'Not in battle phase'], 403);
        }

        $validator = Validator::make($request->all(), [
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();
        $player = $room->players()->where('user_id', $userId)->first();

        if (!$player) {
            return response()->json(['message' => 'Player not in room'], 403);
        }

        if ($player->color !== $room->turn_color) {
            return response()->json(['message' => 'Not your turn'], 403);
        }

        $x = $request->integer('x');
        $y = $request->integer('y');

        return DB::transaction(function () use ($room, $player, $x, $y) {
            $room->lockForUpdate();

            if ($room->turn_color !== $player->color) {
                return response()->json(['message' => 'Not your turn'], 403);
            }

            $teamPlayerIds = $room->players()
                ->where('color', $player->color)
                ->pluck('user_id')
                ->toArray();

            $alreadyShot = $room->pieces()
                ->where('section', 'shot')
                ->where('x', $x)
                ->where('y', $y)
                ->whereIn('user_id', $teamPlayerIds)
                ->exists();

            if ($alreadyShot) {
                return response()->json(['message' => 'Cell already shot'], 422);
            }

            $enemyColors = $this->getTeamColors($room);
            $enemyColors = array_values(array_filter($enemyColors, fn ($c) => $c !== $player->color));

            $enemyPlayerIds = $room->players()
                ->whereIn('color', $enemyColors)
                ->pluck('user_id')
                ->toArray();

            $hitShipCell = $room->pieces()
                ->where('section', 'ship')
                ->whereIn('user_id', $enemyPlayerIds)
                ->where('x', $x)
                ->where('y', $y)
                ->first();

            $hit = $hitShipCell !== null;

            RoomPiece::create([
                'id'       => Str::uuid(),
                'room_id'  => $room->id,
                'user_id'  => $player->user_id,
                'type'     => 'shot',
                'rotation' => 0,
                'x'        => $x,
                'y'        => $y,
                'section'  => 'shot',
                'hit'      => $hit,
            ]);

            $room->touch();

            $sunkShip = null;
            $sunkShipCells = null;
            $winner = null;

            if ($hit) {
                $shipType = $hitShipCell->type;
                $shipOwnerId = $hitShipCell->user_id;

                $totalShipCells = $room->pieces()
                    ->where('section', 'ship')
                    ->where('user_id', $shipOwnerId)
                    ->where('type', $shipType)
                    ->count();

                $hitShipCells = $room->pieces()
                    ->where('section', 'ship')
                    ->where('user_id', $shipOwnerId)
                    ->where('type', $shipType)
                    ->get();

                $hitCount = 0;
                foreach ($hitShipCells as $sc) {
                    $shotOnCell = $room->pieces()
                        ->where('section', 'shot')
                        ->where('hit', true)
                        ->where('x', $sc->x)
                        ->where('y', $sc->y)
                        ->exists();
                    if ($shotOnCell) {
                        $hitCount++;
                    }
                }

                if ($hitCount >= $totalShipCells) {
                    $sunkShip = $shipType;
                    $sunkShipCells = $hitShipCells->map(fn ($c) => ['x' => $c->x, 'y' => $c->y])->values()->toArray();
                }

                $allEnemyShipsSunk = $this->checkAllEnemyShipsSunk($room, $player->color);
                if ($allEnemyShipsSunk) {
                    $winner = $player->color;
                    $room->phase = 'finished';
                    $extra = $room->extraOptions ?? [];
                    $extra['winner'] = $winner;
                    $room->extraOptions = $extra;
                    $room->save();
                }
            }

            if (!$hit && !$winner) {
                $room->turn_color = $this->getNextTurnColor($room, $player->color);
                $room->save();
            }

            broadcast(
                new Battleships\ShotFired(
                    $room->id,
                    ['x' => $x, 'y' => $y],
                    $player->color,
                    $hit,
                    $sunkShip,
                    $room->turn_color,
                    $sunkShipCells
                )
            )->toOthers();

            if ($winner) {
                broadcast(
                    new Battleships\GameOver($room->id, $winner)
                )->toOthers();
            }

            return response()->json([
                'hit'       => $hit,
                'sunkShip'  => $sunkShip,
                'sunkShipCells' => $sunkShipCells,
                'turnColor' => $room->turn_color,
                'winner'    => $winner,
                'phase'     => $room->phase,
            ]);
        });
    }

    private function getTeamColors(Room $room): array
    {
        $colors = $room->players()
            ->pluck('color')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return $colors;
    }

    private function getNextTurnColor(Room $room, string $currentColor): string
    {
        $colors = $this->getTeamColors($room);
        $index = array_search($currentColor, $colors);

        if ($index === false) {
            return $colors[0] ?? $currentColor;
        }

        $nextIndex = ($index + 1) % count($colors);
        return $colors[$nextIndex];
    }

    private function checkAllEnemyShipsSunk(Room $room, string $myColor): bool
    {
        $enemyColors = array_values(array_filter(
            $this->getTeamColors($room),
            fn ($c) => $c !== $myColor
        ));

        $enemyPlayerIds = $room->players()
            ->whereIn('color', $enemyColors)
            ->pluck('user_id')
            ->toArray();

        $enemyShipCells = $room->pieces()
            ->where('section', 'ship')
            ->whereIn('user_id', $enemyPlayerIds)
            ->get();

        if ($enemyShipCells->isEmpty()) {
            return false;
        }

        foreach ($enemyShipCells as $sc) {
            $isHit = $room->pieces()
                ->where('section', 'shot')
                ->where('hit', true)
                ->where('x', $sc->x)
                ->where('y', $sc->y)
                ->exists();

            if (!$isHit) {
                return false;
            }
        }

        return true;
    }
}
