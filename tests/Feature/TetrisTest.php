<?php

use App\Models\User;
use Illuminate\Support\Str;

function makeUser(array $attributes = []): User
{
    $user = new User();
    $user->id = $attributes['id'] ?? Str::uuid();
    $user->auth_id = $attributes['auth_id'] ?? (string) rand(100000000, 999999999);
    $user->username = $attributes['username'] ?? 'testuser';
    $user->email = $attributes['email'] ?? 'test@example.com';
    $user->save();

    return $user;
}

test('tetris index page loads', function () {
    $response = $this->get('/tetris');

    $response->assertStatus(200);
});

test('tetris index returns inertia page with pokedex data', function () {
    $response = $this->get('/tetris');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Pages/Tetris', false)
        ->has('pokedexData')
    );
});
