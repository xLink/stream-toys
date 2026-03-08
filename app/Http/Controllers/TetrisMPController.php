<?php

namespace App\Http\Controllers;

use App\Events\Tetris;
use Inertia\Inertia;
use Inertia\Response as iResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\PokedexService;
use Illuminate\Support\Facades\Validator;
use App\Models\Tetris\Room;
use Illuminate\Support\Str;

class TetrisMPController extends Controller
{

    protected array $options = [
        'colors' => [
            'red'     => 'Red',
            'blue'    => 'Blue',
            'green'   => 'Green',
            'yellow'  => 'Yellow',
            'purple'  => 'Purple',
            'orange'  => 'Orange',
            'cyan'    => 'Cyan',
            'magenta' => 'Magenta',
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
            // 'mode' => 'required|string|in:' . implode(',', array_keys($this->options['sort'])),
            'username' => 'required|string|max:255',
            'color' => 'required|string|in:' . implode(',', array_keys($this->options['colors'])),
            'pokedex' => 'required|array',
            'pokedex.*' => 'string|in:' . implode(',', array_keys($this->options['pokedexes'])),
            'tetriminosToGenerate' => 'required|integer|min:1|max:7',
            'perRow' => 'required|integer|min:1|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Create a new room with the validated data
        $room = Room::create([
            'uuid' => Str::uuid(),
            'players' => json_encode([
                [
                    'username' => $data['username'],
                    'color' => $data['color'],
                    'owner' => true,
                ]
            ]),
            'state' => json_encode([
                'name' => $data['name'],
                'seed' => $data['seed'] ?? Str::random(10),
                // 'mode' => $data['mode'],
                'pokedex' => $data['pokedex'],
                'tetriminosToGenerate' => $data['tetriminosToGenerate'],
                'perRow' => $data['perRow'],
                'board' => [],
                'history' => [],
            ]),
        ]);

        return response()->json([
            'room-uuid' => $room->uuid,
        ], 200);
    }

    public function loadMPRoom(Room $room): iResponse
    {
        $pokedexData = app(PokedexService::class)
            ->getPokemonByMultiDex($room['state']['pokedex'])
        ;

        return Inertia::render('Pages/TetrisMP/MPIndex', [
            'pokedexData' => $pokedexData,
            'optionsObjects' => $this->options,
            'uuid' => $room->uuid,
            'players' => $room->players,
            'state' => $room->state,
            'debug' => []
        ]);
    }

    public function postJoinRoom(Room $room, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'color' => 'required|string|in:' . implode(',', array_keys($this->options['colors'])),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $players = $room->players;

        logger('$players', $players);
        // Check if the player is already in the room
        foreach ($players as $player) {
            if ($player['username'] === $data['username']) {
                return response()->json([
                    'message' => 'Player already in room',
                    'players' => $players,
                ], 401);
            }
        }

        $players[] = [
            'username' => $data['username'],
            'color' => $data['color'],
            'owner' => false,
        ];

        $room->players = $players;
        $room->save();

        broadcast(
            new Tetris\OnlineUser(
                $room->uuid, 
                [
                    'username' => $data['username'], 
                    'color' => $data['color']
                ]
            )
        )->toOthers();

        return response()->json([
            'message' => 'Joined room successfully',
            'players' => $players,
        ], 200);
    }

    public function postSaveRoom(Room $room, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'seed' => 'required|string',
            'pokedex' => 'required|string',
            'perRow' => 'required|string',
            'tetriminosToGenerate' => 'required|integer',
            'sort' => 'required|string',
            'selectionType' => 'required|string',
            'perPlayer' => 'required|string|in:true,false',
            'trackedCells' => 'required|string',
            'history' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $state = $request->all();
        $room->state = $state;
        $room->save();

        broadcast(
            new Tetris\UpdateBoard($room->uuid, $room)
        )->toOthers();
    }

    public function postAddNewCell(Room $room, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:'. implode(',', $this->options['defaultTetriminos']),
            'x' => 'required|integer',
            'y' => 'required|integer',
            'rotation' => 'required|integer',
            'username' => 'required|string|in:'. implode(',', array_column($room->players, 'username')),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $cell = $validator->validated();

        $state = $room->state;
        $state['history'][] = $cell;
        $room->state = $state;
        $room->save();
        
        broadcast(
            new Tetris\NewCell($room->uuid, $cell)
        )->toOthers();
    }

    public function postRemoveLastCell(Room $room)
    {
        $state = $room->state;
        array_pop($state['history']);
        $room->state = $state;
        $room->save();
        
        broadcast(
            new Tetris\UpdateHistory($room->uuid, $room->state['history'])
        )->toOthers();

        return response()->json([
            'message' => 'Last cell removed successfully',
        ], 200);
    }

    public function postRegenerateBoard(Room $room): JsonResponse
    {
        // $room['state']['board'] = $data['board'];
        // $room->save();

        broadcast(
            new Tetris\UpdateBoard($room->uuid, $room)
        )->toOthers();

        return response()->json([
            'message' => 'Board regenerated successfully',
        ], 200);
    }
}