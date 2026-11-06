<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tetris\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaywrightSeeder extends Seeder
{
    public const PLAYER1_ID = '11111111-1111-1111-1111-111111111111';
    public const PLAYER2_ID = '22222222-2222-2222-2222-222222222222';
    public const SPECTATOR_ID = '33333333-3333-3333-3333-333333333333';
    public const TETRIS_ROOM_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
    public const BATTLESHIPS_ROOM_ID = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
    public const BATTLESHIPS_SETUP_ROOM_ID = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
    public const BATTLESHIPS_MULTI_ROOM_ID = 'dddddddd-dddd-dddd-dddd-dddddddddddd';

    public function run(): void
    {
        $player1 = User::create([
            'id' => self::PLAYER1_ID,
            'auth_id' => '100000001',
            'username' => 'testplayer1',
            'email' => 'player1@test.com',
        ]);

        $player2 = User::create([
            'id' => self::PLAYER2_ID,
            'auth_id' => '100000002',
            'username' => 'testplayer2',
            'email' => 'player2@test.com',
        ]);

        User::create([
            'id' => self::SPECTATOR_ID,
            'auth_id' => '100000003',
            'username' => 'testspectator',
            'email' => 'spectator@test.com',
        ]);

        // Seed pokemon (Kanto = 1-151)
        for ($i = 1; $i <= 151; $i++) {
            DB::table('pokemon')->insert(['id' => $i, 'name' => "Pokemon{$i}"]);
        }

        Room::create([
            'id' => self::TETRIS_ROOM_ID,
            'name' => 'Test Tetris Room',
            'user_id' => $player1->id,
            'mode' => 'coop',
            'seed' => 12345,
            'dexes' => ['kanto'],
            'selection_type' => 'single',
            'extraOptions' => ['perRow' => 5, 'sort' => 'random'],
        ]);

        DB::table('tetris_room_players')->insert([
            ['room_id' => self::TETRIS_ROOM_ID, 'user_id' => $player1->id, 'color' => '#93c5fd', 'ready' => false, 'created_at' => now(), 'updated_at' => now()],
            ['room_id' => self::TETRIS_ROOM_ID, 'user_id' => $player2->id, 'color' => '#fca5a5', 'ready' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Room::create([
            'id' => self::BATTLESHIPS_ROOM_ID,
            'name' => 'Test Battleships Room',
            'user_id' => $player1->id,
            'mode' => 'battleships',
            'seed' => 54321,
            'dexes' => ['kanto'],
            'selection_type' => 'single',
            'phase' => 'lobby',
            'extraOptions' => ['perRow' => 5, 'sort' => 'random'],
        ]);

        DB::table('tetris_room_players')->insert([
            ['room_id' => self::BATTLESHIPS_ROOM_ID, 'user_id' => $player1->id, 'color' => '#93c5fd', 'ready' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Battleships room already in setup phase (for ship placement tests)
        Room::create([
            'id' => self::BATTLESHIPS_SETUP_ROOM_ID,
            'name' => 'Test Setup Room',
            'user_id' => $player1->id,
            'mode' => 'battleships',
            'seed' => 99999,
            'dexes' => ['kanto'],
            'selection_type' => 'single',
            'phase' => 'setup',
            'extraOptions' => ['perRow' => 5, 'sort' => 'random'],
        ]);

        DB::table('tetris_room_players')->insert([
            ['room_id' => self::BATTLESHIPS_SETUP_ROOM_ID, 'user_id' => $player1->id, 'color' => '#93c5fd', 'ready' => false, 'created_at' => now(), 'updated_at' => now()],
            ['room_id' => self::BATTLESHIPS_SETUP_ROOM_ID, 'user_id' => $player2->id, 'color' => '#ef4444', 'ready' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Battleships room with multiple copies of ships (for multi-ship tests)
        Room::create([
            'id' => self::BATTLESHIPS_MULTI_ROOM_ID,
            'name' => 'Test Multi Ships Room',
            'user_id' => $player1->id,
            'mode' => 'battleships',
            'seed' => 77777,
            'dexes' => ['kanto'],
            'selection_type' => 'single',
            'phase' => 'setup',
            'extraOptions' => ['perRow' => 10, 'sort' => 'random', 'shipCounts' => ['destroyer' => 3, 'submarine' => 2]],
        ]);

        DB::table('tetris_room_players')->insert([
            ['room_id' => self::BATTLESHIPS_MULTI_ROOM_ID, 'user_id' => $player1->id, 'color' => '#93c5fd', 'ready' => false, 'created_at' => now(), 'updated_at' => now()],
            ['room_id' => self::BATTLESHIPS_MULTI_ROOM_ID, 'user_id' => $player2->id, 'color' => '#ef4444', 'ready' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
