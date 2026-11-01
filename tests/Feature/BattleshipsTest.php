<?php

use App\Models\User;
use App\Models\Tetris\Room;
use App\Models\Tetris\RoomPlayer;
use App\Models\Tetris\RoomPiece;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
});

function makeBSUser(array $attributes = []): User
{
    $user = new User();
    $user->id = $attributes['id'] ?? Str::uuid();
    $user->auth_id = $attributes['auth_id'] ?? (string) rand(100000000, 999999999);
    $user->username = $attributes['username'] ?? 'player' . rand(1000, 9999);
    $user->email = $attributes['email'] ?? 'player' . rand(1000, 9999) . '@example.com';
    $user->save();

    return $user;
}

function makeBSRoom(User $owner, array $overrides = []): Room
{
    $room = Room::create(array_merge([
        'id' => Str::uuid(),
        'name' => 'Test Battleships Room',
        'user_id' => $owner->id,
        'mode' => 'battleships',
        'seed' => 12345,
        'dexes' => ['kanto'],
        'selection_type' => 'single',
        'phase' => 'lobby',
        'extraOptions' => [
            'perRow' => 10,
        ],
    ], $overrides));

    $room->players()->create([
        'user_id' => $owner->id,
        'color' => '#93c5fd',
    ]);

    return $room;
}

function placeAllShips(Room $room, string $userId, int $startY = 0): void
{
    $ships = [
        'carrier'    => 5,
        'battleship' => 4,
        'cruiser'    => 3,
        'submarine'  => 3,
        'destroyer'  => 2,
    ];

    $y = $startY;
    foreach ($ships as $type => $size) {
        for ($x = 0; $x < $size; $x++) {
            RoomPiece::create([
                'id'       => Str::uuid(),
                'room_id'  => $room->id,
                'user_id'  => $userId,
                'type'     => $type,
                'rotation' => 0,
                'x'        => $x,
                'y'        => $y,
                'section'  => 'ship',
            ]);
        }
        $y++;
    }
}

function setupBattleRoom(): array
{
    $owner = makeBSUser(['username' => 'team_red']);
    $opponent = makeBSUser(['username' => 'team_blue']);
    $room = makeBSRoom($owner);
    $room->players()->create(['user_id' => $opponent->id, 'color' => '#0ea5e9']);
    $room->phase = 'battle';
    $room->turn_color = '#93c5fd';
    $room->save();

    placeAllShips($room, $owner->id, 0);
    placeAllShips($room, $opponent->id, 10);

    return [$owner, $opponent, $room];
}

// --- Index ---

test('battleships index page loads', function () {
    $response = $this->get('/battleships');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Battleships/Index', false)
        ->has('optionsObjects')
    );
});

// --- Room loading ---

test('room page loads for existing room', function () {
    $user = makeBSUser();
    $room = makeBSRoom($user);

    $response = $this->actingAs($user)->get('/battleships/' . $room->id);

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Battleships/Room', false)
        ->has('pokedexData')
        ->where('uuid', (string) $room->id)
        ->has('state')
        ->has('players')
    );
});

test('room page 404s for non-existent uuid', function () {
    $response = $this->get('/battleships/' . Str::uuid());

    $response->assertStatus(404);
});

// --- Room creation ---

test('authenticated user can create a room', function () {
    $user = makeBSUser();

    $response = $this->actingAs($user)->postJson('/battleships', [
        'name' => 'My BS Room',
        'seed' => 42,
        'color' => '#93c5fd',
        'pokedex' => ['kanto'],
        'perRow' => 10,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['room-uuid']);

    $this->assertDatabaseHas('tetris_room', [
        'name' => 'My BS Room',
        'mode' => 'battleships',
        'phase' => 'lobby',
    ]);
});

test('room creation validates required fields', function () {
    $user = makeBSUser();

    $response = $this->actingAs($user)->postJson('/battleships', []);

    $response->assertStatus(422);
});

test('room creation rejects invalid pokedex', function () {
    $user = makeBSUser();

    $response = $this->actingAs($user)->postJson('/battleships', [
        'name' => 'Bad Dex Room',
        'seed' => 1,
        'color' => '#93c5fd',
        'pokedex' => ['fakegen'],
        'perRow' => 10,
    ]);

    $response->assertStatus(422);
});

// --- Join room ---

test('authenticated user can join a room', function () {
    $owner = makeBSUser(['username' => 'bs_owner']);
    $room = makeBSRoom($owner);
    $joiner = makeBSUser(['username' => 'bs_joiner']);

    $response = $this->actingAs($joiner)->postJson('/battleships/' . $room->id . '/join', [
        'color' => '#0ea5e9',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['message', 'players']);

    $this->assertDatabaseHas('tetris_room_players', [
        'room_id' => $room->id,
        'user_id' => $joiner->id,
        'color' => '#0ea5e9',
    ]);
});

test('unauthenticated user cannot join a room', function () {
    $owner = makeBSUser();
    $room = makeBSRoom($owner);

    $response = $this->postJson('/battleships/' . $room->id . '/join', [
        'color' => '#0ea5e9',
    ]);

    $response->assertStatus(401);
});

test('player cannot join same room twice', function () {
    $owner = makeBSUser();
    $room = makeBSRoom($owner);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/join', [
        'color' => '#0ea5e9',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Player already in room']);
});

test('cannot join after game started', function () {
    $owner = makeBSUser();
    $room = makeBSRoom($owner);
    $room->phase = 'setup';
    $room->save();

    $joiner = makeBSUser(['username' => 'latejoin']);

    $response = $this->actingAs($joiner)->postJson('/battleships/' . $room->id . '/join', [
        'color' => '#0ea5e9',
    ]);

    $response->assertStatus(403);
});

// --- Start game ---

test('owner can start game with 2+ team colors', function () {
    $owner = makeBSUser(['username' => 'starter']);
    $room = makeBSRoom($owner);
    $opponent = makeBSUser(['username' => 'opponent']);
    $room->players()->create(['user_id' => $opponent->id, 'color' => '#0ea5e9']);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/start-game');

    $response->assertStatus(200);
    $response->assertJson(['phase' => 'setup']);

    $room->refresh();
    expect($room->phase)->toBe('setup');
});

test('non-owner cannot start game', function () {
    $owner = makeBSUser(['username' => 'theowner']);
    $room = makeBSRoom($owner);
    $other = makeBSUser(['username' => 'notowner']);
    $room->players()->create(['user_id' => $other->id, 'color' => '#0ea5e9']);

    $response = $this->actingAs($other)->postJson('/battleships/' . $room->id . '/start-game');

    $response->assertStatus(403);
});

test('cannot start with fewer than 2 team colors', function () {
    $owner = makeBSUser(['username' => 'solo']);
    $room = makeBSRoom($owner);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/start-game');

    $response->assertStatus(422);
});

// --- Ship placement ---

test('player can place a ship', function () {
    $owner = makeBSUser(['username' => 'shipplacer']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/place-ship', [
        'shipType' => 'destroyer',
        'cells' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0]],
        'orientation' => 0,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('tetris_room_pieces', [
        'room_id' => $room->id,
        'user_id' => $owner->id,
        'type' => 'destroyer',
        'section' => 'ship',
        'x' => 0,
        'y' => 0,
    ]);
});

test('cannot place ship with wrong cell count', function () {
    $owner = makeBSUser(['username' => 'wrongcount']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/place-ship', [
        'shipType' => 'carrier',
        'cells' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0], ['x' => 2, 'y' => 0]],
        'orientation' => 0,
    ]);

    $response->assertStatus(422);
});

test('cannot place same ship type twice', function () {
    $owner = makeBSUser(['username' => 'dupeplacer']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 0, 'y' => 0, 'section' => 'ship',
    ]);
    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 1, 'y' => 0, 'section' => 'ship',
    ]);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/place-ship', [
        'shipType' => 'destroyer',
        'cells' => [['x' => 0, 'y' => 5], ['x' => 1, 'y' => 5]],
        'orientation' => 0,
    ]);

    $response->assertStatus(422);
});

test('cannot place overlapping ships', function () {
    $owner = makeBSUser(['username' => 'overlapper']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 0, 'y' => 0, 'section' => 'ship',
    ]);
    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 1, 'y' => 0, 'section' => 'ship',
    ]);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/place-ship', [
        'shipType' => 'cruiser',
        'cells' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0], ['x' => 2, 'y' => 0]],
        'orientation' => 0,
    ]);

    $response->assertStatus(422);
});

test('cannot place ship outside setup phase', function () {
    $owner = makeBSUser(['username' => 'wrongphase']);
    $room = makeBSRoom($owner);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/place-ship', [
        'shipType' => 'destroyer',
        'cells' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0]],
        'orientation' => 0,
    ]);

    $response->assertStatus(403);
});

// --- Remove ship ---

test('player can remove a placed ship', function () {
    $owner = makeBSUser(['username' => 'remover']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 0, 'y' => 0, 'section' => 'ship',
    ]);
    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 1, 'y' => 0, 'section' => 'ship',
    ]);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/remove-ship', [
        'shipType' => 'destroyer',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseMissing('tetris_room_pieces', [
        'room_id' => $room->id,
        'user_id' => $owner->id,
        'type' => 'destroyer',
        'section' => 'ship',
    ]);
});

test('removing ship unsets ready status', function () {
    $owner = makeBSUser(['username' => 'readyremover']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    $room->players()->where('user_id', $owner->id)->update(['ready' => true]);

    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 0, 'y' => 0, 'section' => 'ship',
    ]);

    $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/remove-ship', [
        'shipType' => 'destroyer',
    ]);

    $player = $room->players()->where('user_id', $owner->id)->first();
    expect((bool) $player->ready)->toBeFalse();
});

// --- Ready ---

test('player can ready up after placing all ships', function () {
    $owner = makeBSUser(['username' => 'readyplayer']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    placeAllShips($room, $owner->id);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/ready');

    $response->assertStatus(200);
    $response->assertJson(['ready' => true]);
});

test('cannot ready without all ships placed', function () {
    $owner = makeBSUser(['username' => 'notready']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/ready');

    $response->assertStatus(422);
});

test('all players ready transitions to battle phase', function () {
    $owner = makeBSUser(['username' => 'ready1']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);
    $opponent = makeBSUser(['username' => 'ready2']);
    $room->players()->create(['user_id' => $opponent->id, 'color' => '#0ea5e9']);

    placeAllShips($room, $owner->id, 0);
    placeAllShips($room, $opponent->id, 10);

    $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/ready');
    $this->actingAs($opponent)->postJson('/battleships/' . $room->id . '/ready');

    $room->refresh();
    expect($room->phase)->toBe('battle');
    expect($room->turn_color)->not->toBeNull();
});

test('ready toggles', function () {
    $owner = makeBSUser(['username' => 'toggler']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);
    $other = makeBSUser(['username' => 'toggleother']);
    $room->players()->create(['user_id' => $other->id, 'color' => '#0ea5e9']);

    placeAllShips($room, $owner->id);

    $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/ready');
    $player = $room->players()->where('user_id', $owner->id)->first();
    expect((bool) $player->ready)->toBeTrue();

    $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/ready');
    $player = $room->players()->where('user_id', $owner->id)->first();
    expect((bool) $player->ready)->toBeFalse();
});

// --- Fire shot ---

test('player can fire a shot on their turn', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/fire', [
        'x' => 5, 'y' => 5,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['hit', 'turnColor']);
});

test('cannot fire when not your turn', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    $response = $this->actingAs($opponent)->postJson('/battleships/' . $room->id . '/fire', [
        'x' => 0, 'y' => 0,
    ]);

    $response->assertStatus(403);
});

test('cannot fire at already-shot cell', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'shot', 'rotation' => 0, 'x' => 5, 'y' => 5, 'section' => 'shot', 'hit' => false,
    ]);

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/fire', [
        'x' => 5, 'y' => 5,
    ]);

    $response->assertStatus(422);
});

test('hitting enemy ship returns hit true', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    $enemyShip = $room->pieces()->where('user_id', $opponent->id)->where('section', 'ship')->first();

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/fire', [
        'x' => $enemyShip->x, 'y' => $enemyShip->y,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['hit' => true]);
});

test('missing returns hit false and advances turn', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/fire', [
        'x' => 9, 'y' => 9,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['hit' => false]);

    $data = $response->json();
    expect($data['turnColor'])->toBe('#0ea5e9');
});

test('hitting keeps same turn', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    $enemyShip = $room->pieces()->where('user_id', $opponent->id)->where('section', 'ship')->first();

    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/fire', [
        'x' => $enemyShip->x, 'y' => $enemyShip->y,
    ]);

    $data = $response->json();
    expect($data['turnColor'])->toBe('#93c5fd');
});

test('sinking a ship returns sunkShip', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    $destroyerCells = $room->pieces()
        ->where('user_id', $opponent->id)
        ->where('section', 'ship')
        ->where('type', 'destroyer')
        ->get();

    foreach ($destroyerCells as $i => $cell) {
        $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/fire', [
            'x' => $cell->x, 'y' => $cell->y,
        ]);
    }

    $data = $response->json();
    expect($data['sunkShip'])->toBe('destroyer');
});

test('sinking all enemy ships wins the game', function () {
    $owner = makeBSUser(['username' => 'winner']);
    $opponent = makeBSUser(['username' => 'loser']);
    $room = makeBSRoom($owner);
    $room->players()->create(['user_id' => $opponent->id, 'color' => '#0ea5e9']);
    $room->phase = 'battle';
    $room->turn_color = '#93c5fd';
    $room->save();

    placeAllShips($room, $owner->id, 0);

    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $opponent->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 0, 'y' => 20, 'section' => 'ship',
    ]);
    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $opponent->id,
        'type' => 'destroyer', 'rotation' => 0, 'x' => 1, 'y' => 20, 'section' => 'ship',
    ]);

    $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/fire', ['x' => 0, 'y' => 20]);
    $response = $this->actingAs($owner)->postJson('/battleships/' . $room->id . '/fire', ['x' => 1, 'y' => 20]);

    $data = $response->json();
    expect($data['winner'])->toBe('#93c5fd');
    expect($data['phase'])->toBe('finished');
});

// --- State ---

test('battleships state includes own ships but not enemy ships', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    $state = $room->toBattleshipsState($owner->id);

    expect($state['myShips'])->not->toBeEmpty();

    $myShipTypes = collect($state['myShips'])->pluck('shipType')->toArray();
    expect($myShipTypes)->toContain('carrier');

    $stateAsOpponent = $room->toBattleshipsState($opponent->id);
    $opponentShipTypes = collect($stateAsOpponent['myShips'])->pluck('shipType')->toArray();
    expect($opponentShipTypes)->toContain('carrier');

    expect($state['myShips'])->not->toBe($stateAsOpponent['myShips']);
});

test('battleships state includes all shots', function () {
    [$owner, $opponent, $room] = setupBattleRoom();

    RoomPiece::create([
        'id' => Str::uuid(), 'room_id' => $room->id, 'user_id' => $owner->id,
        'type' => 'shot', 'rotation' => 0, 'x' => 3, 'y' => 3, 'section' => 'shot', 'hit' => false,
    ]);

    $state = $room->toBattleshipsState($owner->id);
    expect($state['shots'])->toHaveCount(1);
    expect($state['shots'][0]['x'])->toBe(3);
});

test('battleships state includes ready players', function () {
    $owner = makeBSUser(['username' => 'readycheck']);
    $room = makeBSRoom($owner, ['phase' => 'setup']);

    $state = $room->toBattleshipsState($owner->id);
    expect($state['readyPlayers'])->toHaveKey('readycheck');
    expect($state['readyPlayers']['readycheck'])->toBeFalse();
});
