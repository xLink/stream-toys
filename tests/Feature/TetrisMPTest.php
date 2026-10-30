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

function makeMPUser(array $attributes = []): User
{
    $user = new User();
    $user->id = $attributes['id'] ?? Str::uuid();
    $user->auth_id = $attributes['auth_id'] ?? (string) rand(100000000, 999999999);
    $user->username = $attributes['username'] ?? 'player' . rand(1000, 9999);
    $user->email = $attributes['email'] ?? 'player' . rand(1000, 9999) . '@example.com';
    $user->save();

    return $user;
}

function makeMPRoom(User $owner, array $overrides = []): Room
{
    return Room::create(array_merge([
        'id' => Str::uuid(),
        'name' => 'Test Room',
        'user_id' => $owner->id,
        'mode' => 'coop',
        'seed' => 12345,
        'dexes' => ['kanto'],
        'selection_type' => 'tetris',
        'extraOptions' => [
            'tetriminosToGenerate' => 3,
            'perRow' => 10,
        ],
    ], $overrides));
}

// --- Index ---

test('tetris-mp index page loads', function () {
    $response = $this->get('/tetris-mp');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Pages/TetrisMP', false)
        ->has('optionsObjects')
    );
});

// --- Room creation ---

test('authenticated user can create a room', function () {
    $user = makeMPUser();

    $response = $this->actingAs($user)->post('/tetris-mp', [
        'name' => 'My Room',
        'seed' => 42,
        'mode' => 'coop',
        'username' => $user->username,
        'color' => '#ff0000',
        'pokedex' => ['kanto'],
        'tetriminosToGenerate' => 3,
        'perRow' => 10,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['room-uuid']);

    $this->assertDatabaseHas('tetris_room', [
        'name' => 'My Room',
        'mode' => 'coop',
        'seed' => 42,
    ]);
});

test('room creation validates required fields', function () {
    $user = makeMPUser();

    $response = $this->actingAs($user)->postJson('/tetris-mp', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'mode', 'username', 'color', 'pokedex', 'tetriminosToGenerate', 'perRow']);
});

test('room creation rejects invalid mode', function () {
    $user = makeMPUser();

    $response = $this->actingAs($user)->postJson('/tetris-mp', [
        'name' => 'Bad Mode Room',
        'seed' => 1,
        'mode' => 'invalid',
        'username' => $user->username,
        'color' => '#ff0000',
        'pokedex' => ['kanto'],
        'tetriminosToGenerate' => 3,
        'perRow' => 10,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['mode']);
});

test('room creation rejects invalid pokedex', function () {
    $user = makeMPUser();

    $response = $this->actingAs($user)->postJson('/tetris-mp', [
        'name' => 'Bad Dex Room',
        'seed' => 1,
        'mode' => 'coop',
        'username' => $user->username,
        'color' => '#ff0000',
        'pokedex' => ['fakegen'],
        'tetriminosToGenerate' => 3,
        'perRow' => 10,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['pokedex.0']);
});

// --- Room loading ---

test('room page loads for existing room', function () {
    $user = makeMPUser();
    $room = makeMPRoom($user);

    $response = $this->actingAs($user)->get('/tetris-mp/' . $room->id);

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Pages/TetrisMP/MPIndex', false)
        ->has('pokedexData')
        ->where('uuid', (string) $room->id)
        ->has('state')
        ->has('players')
    );
});

test('room page 404s for non-existent uuid', function () {
    $response = $this->get('/tetris-mp/' . Str::uuid());

    $response->assertStatus(404);
});

// --- Join room ---

test('authenticated user can join a room', function () {
    $owner = makeMPUser(['username' => 'owner']);
    $room = makeMPRoom($owner);
    $joiner = makeMPUser(['username' => 'joiner']);

    $response = $this->actingAs($joiner)->postJson('/tetris-mp/' . $room->id . '/join', [
        'color' => '#00ff00',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['message', 'players']);

    $this->assertDatabaseHas('tetris_room_players', [
        'room_id' => $room->id,
        'user_id' => $joiner->id,
        'color' => '#00ff00',
    ]);
});

test('unauthenticated user cannot join a room', function () {
    $owner = makeMPUser();
    $room = makeMPRoom($owner);

    $response = $this->postJson('/tetris-mp/' . $room->id . '/join', [
        'color' => '#00ff00',
    ]);

    $response->assertStatus(401);
});

test('player cannot join same room twice', function () {
    $owner = makeMPUser();
    $room = makeMPRoom($owner);
    $player = makeMPUser(['username' => 'duper']);

    $room->players()->create([
        'user_id' => $player->id,
        'color' => '#ff0000',
    ]);

    $response = $this->actingAs($player)->postJson('/tetris-mp/' . $room->id . '/join', [
        'color' => '#00ff00',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Player already in room']);
});

// --- Add new cell ---

test('player can add a cell to the board', function () {
    $owner = makeMPUser(['username' => 'cellowner']);
    $room = makeMPRoom($owner);

    $room->players()->create([
        'user_id' => $owner->id,
        'color' => '#ff0000',
    ]);

    $response = $this->actingAs($owner)->postJson('/tetris-mp/' . $room->id . '/add-new-cell', [
        'type' => 'i',
        'x' => 0,
        'y' => 0,
        'rotation' => 0,
        'username' => 'cellowner',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('tetris_room_pieces', [
        'room_id' => $room->id,
        'type' => 'i',
        'x' => 0,
        'y' => 0,
        'section' => 'history',
    ]);
});

test('add cell rejects invalid piece type', function () {
    $owner = makeMPUser(['username' => 'badtype']);
    $room = makeMPRoom($owner);

    $room->players()->create([
        'user_id' => $owner->id,
        'color' => '#ff0000',
    ]);

    $response = $this->actingAs($owner)->postJson('/tetris-mp/' . $room->id . '/add-new-cell', [
        'type' => 'x',
        'x' => 0,
        'y' => 0,
        'rotation' => 0,
        'username' => 'badtype',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['type']);
});

test('add cell rejects username not in room', function () {
    $owner = makeMPUser(['username' => 'roomhost']);
    $room = makeMPRoom($owner);

    $room->players()->create([
        'user_id' => $owner->id,
        'color' => '#ff0000',
    ]);

    $response = $this->actingAs($owner)->postJson('/tetris-mp/' . $room->id . '/add-new-cell', [
        'type' => 'i',
        'x' => 0,
        'y' => 0,
        'rotation' => 0,
        'username' => 'notinroom',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['username']);
});

// --- Remove last cell ---

test('remove last cell works', function () {
    $owner = makeMPUser(['username' => 'remover']);
    $room = makeMPRoom($owner);

    RoomPiece::create([
        'id' => Str::uuid(),
        'room_id' => $room->id,
        'user_id' => $owner->id,
        'type' => 't',
        'rotation' => 0,
        'x' => 1,
        'y' => 1,
        'section' => 'history',
    ]);

    $response = $this->actingAs($owner)->postJson('/tetris-mp/' . $room->id . '/remove-last-cell');

    $response->assertStatus(200);

    $this->assertDatabaseMissing('tetris_room_pieces', [
        'room_id' => $room->id,
        'section' => 'history',
    ]);
});

// --- Regenerate board ---

test('regenerate board returns success', function () {
    $owner = makeMPUser();
    $room = makeMPRoom($owner);

    $response = $this->actingAs($owner)->get('/tetris-mp/' . $room->id . '/regenerate-board');

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Board regenerated successfully']);
});

// --- Update player ---

test('player can update their color', function () {
    $owner = makeMPUser(['username' => 'colorchanger']);
    $room = makeMPRoom($owner);

    $room->players()->create([
        'user_id' => $owner->id,
        'color' => '#ff0000',
    ]);

    $response = $this->actingAs($owner)->postJson('/tetris-mp/' . $room->id . '/update-player', [
        'username' => 'colorchanger',
        'color' => '#0000ff',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('tetris_room_players', [
        'room_id' => $room->id,
        'user_id' => $owner->id,
        'color' => '#0000ff',
    ]);
});

// --- Room state ---

test('room state includes history and tracked cells', function () {
    $owner = makeMPUser(['username' => 'statecheck']);
    $room = makeMPRoom($owner);

    $room->players()->create([
        'user_id' => $owner->id,
        'color' => '#ff0000',
    ]);

    RoomPiece::create([
        'id' => Str::uuid(),
        'room_id' => $room->id,
        'user_id' => $owner->id,
        'type' => 'o',
        'rotation' => 0,
        'x' => 2,
        'y' => 3,
        'section' => 'history',
    ]);

    RoomPiece::create([
        'id' => Str::uuid(),
        'room_id' => $room->id,
        'user_id' => $owner->id,
        'type' => '.',
        'rotation' => 0,
        'x' => 5,
        'y' => 5,
        'section' => 'tracked',
    ]);

    $state = $room->toStateArray($owner->id);

    expect($state['history'])->toHaveCount(1);
    expect($state['history'][0])->toMatchArray([
        'type' => 'o',
        'x' => 2,
        'y' => 3,
        'username' => 'statecheck',
    ]);
    expect($state['trackedCells'])->toHaveCount(1);
    expect($state['trackedCells'][0])->toMatchArray(['x' => 5, 'y' => 5]);
});

test('room players array lists all players', function () {
    $owner = makeMPUser(['username' => 'theowner']);
    $other = makeMPUser(['username' => 'theother']);
    $room = makeMPRoom($owner);

    $room->players()->create(['user_id' => $owner->id, 'color' => '#ff0000']);
    $room->players()->create(['user_id' => $other->id, 'color' => '#00ff00']);

    $players = $room->toPlayersArray();

    expect($players)->toHaveCount(2);

    $usernames = collect($players)->pluck('username')->sort()->values()->toArray();
    expect($usernames)->toBe(['theother', 'theowner']);

    $ownerEntry = collect($players)->firstWhere('username', 'theowner');
    expect($ownerEntry['color'])->toBe('#ff0000');
    expect($ownerEntry)->toHaveKey('owner');
});

// --- Game modes ---

test('all three game modes are accepted', function () {
    $user = makeMPUser();

    foreach (['coop', 'blackout', 'vs'] as $mode) {
        $response = $this->actingAs($user)->postJson('/tetris-mp', [
            'name' => "Room $mode",
            'seed' => 1,
            'mode' => $mode,
            'username' => $user->username,
            'color' => '#ff0000',
            'pokedex' => ['kanto'],
            'tetriminosToGenerate' => 3,
            'perRow' => 10,
        ]);

        $response->assertStatus(200);
    }
});

test('all valid piece types are accepted', function () {
    $owner = makeMPUser(['username' => 'piecetester']);
    $room = makeMPRoom($owner);

    $room->players()->create([
        'user_id' => $owner->id,
        'color' => '#ff0000',
    ]);

    foreach (['i', 'j', 'l', 'o', 's', 'z', 't', '.'] as $type) {
        $response = $this->actingAs($owner)->postJson('/tetris-mp/' . $room->id . '/add-new-cell', [
            'type' => $type,
            'x' => 0,
            'y' => 0,
            'rotation' => 0,
            'username' => 'piecetester',
        ]);

        $response->assertStatus(200);
    }
});
