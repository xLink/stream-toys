<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $pokemon = config('pokemon');

        foreach ($pokemon as $key => $poke) {
            \App\Models\Pokemon::create([
                'id' => $key,
                'name' => $poke,
            ]);
        }
    }
}
