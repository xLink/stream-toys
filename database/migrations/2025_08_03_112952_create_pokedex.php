<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('pokemon', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('pokedex', function (Blueprint $table) {
            $table->uuid();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('pokedex_pokemon', function (Blueprint $table) {
            $table->uuid('pokedex_id');
            $table->integer('pokemon_id');

            $table->primary(['pokedex_id', 'pokemon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokedex_pokemon');
        Schema::dropIfExists('pokedex');
        Schema::dropIfExists('pokemon');
    }
};
