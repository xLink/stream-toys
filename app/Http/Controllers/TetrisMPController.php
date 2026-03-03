<?php

namespace App\Http\Controllers;

use App\Events\Tetris\OnlineUser;
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

use function Pest\Laravel\json;

class TetrisMPController extends Controller
{

    protected array $options = [
        'colors' => [
            'red' => 'Red',
            'blue' => 'Blue',
            'green' => 'Green',
            'yellow' => 'Yellow',
            'purple' => 'Purple',
            'orange' => 'Orange',
            'cyan' => 'Cyan',
            'magenta' => 'Magenta',
        ],
        'pokedexes' => [
            'gen1' => 'Gen 1',
            'gen2' => 'Gen 2',
            'gen3' => 'Gen 3',
            'gen4' => 'Gen 4',
            'gen5' => 'Gen 5',
            'gen6' => 'Gen 6',
            'gen7' => 'Gen 7',
            'gen8' => 'Gen 8',
            'gen9' => 'Gen 9',
        ],
        'sort' => [
            'random' => 'Random',
            'byId' => 'By ID',
            'byName' => 'By Name',
        ],
        'boolean' => [
            'true' => 'True',
            'false' => 'False',
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
        return Inertia::render('Pages/TetrisMP/Board', [
            'optionsObjects' => $this->options,
            'uuid' => $room->uuid,
            'players' => json_decode($room->players, true),
            'state' => json_decode($room->state, true),
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

        $players = json_decode($room->players, true);

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

        $room->players = json_encode($players);
        $room->save();

        broadcast(
            new OnlineUser(
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
}