<?php

namespace App\Http\Controllers;

use App\Events\Tetris;
use Inertia\Inertia;
use Inertia\Response as iResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\PokedexService;
use Illuminate\Support\Facades\Validator;
use App\Models\Tetris\Room;
use App\Models\Tetris\RoomPiece;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TetrisMPController extends Controller
{
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
        'mode' => [
            'coop'     => [
                'label' => 'Co-op',
                'info' => 'Players work together to clear the board.'
            ],
            'blackout' => [
                'label' => 'Blackout',
                'info' => 'Players work against eachother to catch pokemon and claim the most pieces on the board.'
            ],
            'vs'       => [
                'label' => 'Versus',
                'info' => 'Players race to catch pokemon and fill in their own board.'
            ],
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
        'selectionType' => [
            'single' => 'Single Pokemon Selection',
            'tetris' => 'Tetris Piece Selection',
        ],
        'defaultTetriminos' => [
            'i', 'j', 'l', 'o', 's', 'z', 't'
        ],
    ];

    public function getIndex(string|null $room = null): iResponse
    {
        return Inertia::render('Pages/TetrisMP', [
            'optionsObjects' => $this->options,
        ]);
    }

    public function postCreateRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'seed' => 'nullable|integer',
            'mode' => 'required|string|in:' . implode(',', array_keys($this->options['mode'])),
            'username' => 'required|string|max:255',
            'color' => 'required|string|max:50',
            'pokedex' => 'required|array',
            'pokedex.*' => 'string|in:' . implode(',', array_keys($this->options['pokedexes'])),
            'selection_type' => 'nullable|string|in:' . implode(',', array_keys($this->options['selectionType'])),
            'tetriminosToGenerate' => 'required|integer|min:1|max:10',
            'perRow' => 'required|integer|min:1|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $room = Room::create([
            'id' => Str::uuid(),
            'name' => $data['name'],
            'user_id' => auth()->id(),
            'mode' => $data['mode'],
            'seed' => $data['seed'] ?? Str::random(6),
            'dexes' => $data['pokedex'],
            'selection_type' => $data['selection_type'] ?? 'tetris',
            'extraOptions' => [
                'tetriminosToGenerate' => $data['tetriminosToGenerate'],
                'perRow' => $data['perRow'],
            ],
        ]);

        return response()->json([
            'room-uuid' => $room->id,
        ], 200);
    }

    public function loadMPRoom(Room $room): iResponse
    {
        session()->put('tetris-mp-room', $room);

        $pokedexData = app(PokedexService::class)
            ->getPokemonByMultiDex($room->dexes ?? [])
        ;
        return Inertia::render('Pages/TetrisMP/MPIndex', [
            'pokedexData' => $pokedexData,
            'optionsObjects' => $this->options,
            'uuid' => $room->id,
            'players' => $room->toPlayersArray(),
            'state' => $room->toStateArray(auth()->id()),
            'debug' => []
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
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = auth()->id();

        if ($room->players()->where('user_id', $userId)->exists()) {
            return response()->json([
                'message' => 'Player already in room',
                'players' => $room->toPlayersArray(),
            ], 401);
        }

        $room->players()->create([
            'user_id' => $userId,
            'color'   => $request->color,
        ]);

        $players = $room->toPlayersArray();

        broadcast(
            new Tetris\UpdateUsers($room->id, $players)
        )->toOthers();

        return response()->json([
            'message' => 'Joined room successfully',
            'players' => $players,
        ], 200);
    }

    public function postSaveRoom(Room $room, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                 => 'required|string',
            'seed'                 => 'required|string',
            'mode'                 => 'required|string|in:' . implode(',', array_keys($this->options['mode'])),
            'pokedex'              => 'required|string',
            'perRow'               => 'required|string',
            'tetriminosToGenerate' => 'required|integer',
            'sort'                 => 'required|string',
            'selectionType'        => 'required|string',
            'perPlayer'            => 'required|string|in:true,false',
            'trackedCells'         => 'required|string',
            'history'              => 'required|string',
            'clearAll'             => 'nullable|string|in:true,false',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $history      = json_decode($data['history'], true) ?? [];
        $trackedCells = json_decode($data['trackedCells'], true) ?? [];
        $pokedex      = json_decode($data['pokedex'], true) ?? [];

        $room->name           = $data['name'];
        $room->seed           = $data['seed'];
        $room->mode           = $data['mode'];
        $room->dexes          = $pokedex;
        $room->selection_type = $data['selectionType'];
        $room->extraOptions   = [
            'perRow'               => $data['perRow'],
            'tetriminosToGenerate' => $data['tetriminosToGenerate'],
            'sort'                 => $data['sort'],
            'perPlayer'            => $data['perPlayer'] === 'true',
        ];
        $room->save();

        $now = now();

        // Replace history pieces
        $room->pieces()->where('section', 'history')->delete();

        if (!empty($history)) {
            $playersByUsername = $room->players()->whereHas('user')->with('user')->get()
                ->keyBy(fn ($p) => strtolower($p->user->username));

            $pieces = [];
            foreach ($history as $cell) {
                $username = strtolower($cell['username'] ?? '');
                $userId   = $playersByUsername[$username]?->user_id ?? $room->user_id;

                $pieces[] = [
                    'id'         => Str::uuid(),
                    'room_id'    => $room->id,
                    'user_id'    => $userId,
                    'type'       => $cell['type'],
                    'rotation'   => $cell['rotation'],
                    'x'          => $cell['x'],
                    'y'          => $cell['y'],
                    'section'    => 'history',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            RoomPiece::insert($pieces);
        }

        // Replace tracked pieces
        // clearAll (board reset): delete every player's tracked cells
        // coop: shared tracked cells, delete all and re-insert under room owner
        // blackout/vs: per-player, only replace the requesting player's cells
        $clearAll     = $request->input('clearAll') === 'true';
        $isSharedMode = $room->mode === 'coop';
        $trackedQuery = $room->pieces()->where('section', 'tracked');
        if (!$clearAll && !$isSharedMode) {
            $trackedQuery->where('user_id', auth()->id());
        }
        $trackedQuery->delete();

        if (!empty($trackedCells)) {
            $trackedUserId = $isSharedMode ? $room->user_id : auth()->id();
            $pieces = [];
            foreach ($trackedCells as $cell) {
                $pieces[] = [
                    'id'         => Str::uuid(),
                    'room_id'    => $room->id,
                    'user_id'    => $trackedUserId,
                    'type'       => '.',
                    'rotation'   => 0,
                    'x'          => $cell['x'],
                    'y'          => $cell['y'],
                    'section'    => 'tracked',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            RoomPiece::insert($pieces);
        }

        broadcast(
            new Tetris\UpdateBoard($room->id, $room, $clearAll)
        )->toOthers();
    }

    public function postUpdatePlayer(Room $room, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'color'    => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $updated = $room->players()
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(username) = ?', [strtolower($data['username'])]))
            ->update(['color' => $data['color']]);

        if ($updated) {
            $players = $room->toPlayersArray();
            broadcast(new Tetris\UpdateUsers($room->id, $players));
            return response()->json([
                'message' => 'Saved successfully',
                'players' => $players,
            ], 200);
        }

        return response()->json([
            'message' => 'Failed to save',
        ], 500);
    }

    public function postAddNewCell(Room $room, Request $request)
    {
        $playerUsernames = $room->players()->with('user')
            ->get()
            ->pluck('user.username')
            ->filter()
            ->toArray();

        $validator = Validator::make($request->all(), [
            'type'     => 'required|string|in:'. implode(',', array_merge($this->options['defaultTetriminos'], ['.'])),
            'x'        => 'required|integer',
            'y'        => 'required|integer',
            'rotation' => 'required|integer',
            'username' => 'required|string|in:'. implode(',', $playerUsernames),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $cell = $validator->validated();

        $player = $room->players()->with('user')
            ->get()
            ->first(fn ($p) => strtolower($p->user?->username ?? '') === strtolower($cell['username']));

        RoomPiece::create([
            'id'       => Str::uuid(),
            'room_id'  => $room->id,
            'user_id'  => $player?->user_id ?? $room->user_id,
            'type'     => $cell['type'],
            'rotation' => $cell['rotation'],
            'x'        => $cell['x'],
            'y'        => $cell['y'],
            'section'  => 'history',
        ]);

        broadcast(
            new Tetris\NewCell($room->id, $cell)
        )->toOthers();
    }

    public function postRemoveLastCell(Room $room)
    {
        $room->pieces()
            ->where('section', 'history')
            ->latest()
            ->first()
            ?->delete();

        $history = $room->pieces()
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
            ->toArray();

        broadcast(
            new Tetris\UpdateHistory($room->id, $history)
        )->toOthers();

        return response()->json([
            'message' => 'Last cell removed successfully',
        ], 200);
    }

    public function postRegenerateBoard(Room $room): JsonResponse
    {
        broadcast(
            new Tetris\UpdateBoard($room->id, $room)
        )->toOthers();

        return response()->json([
            'message' => 'Board regenerated successfully',
        ], 200);
    }
}
