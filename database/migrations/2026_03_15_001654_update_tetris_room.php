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
        Schema::dropIfExists('tetris_room');
        Schema::create('tetris_room', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedInteger('user_id')->references('id')->on('users');
            $table->string('mode')->default('coop');
            $table->integer('seed');
            $table->json('dexes');
            $table->string('selection_type')->default('single');
            $table->timestamps();
        });

        Schema::create('tetris_room_players', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->references('id')->on('tetris_room')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('color');
            $table->timestamps();

            $table->primary(['room_id', 'user_id']);
        });

        Schema::create('tetris_room_pieces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('room_id')->references('id')->on('tetris_room')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('type');
            $table->integer('rotation');
            $table->integer('x');
            $table->integer('y');
            $table->string('section')->default('history');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tetris_room_pieces');
        Schema::dropIfExists('tetris_room_players');
        Schema::table('tetris_room', function (Blueprint $table) {
            $table->dropColumn(['name', 'user_id', 'mode', 'seed', 'dexes', 'selection_type', 'created_at', 'updated_at']);
        });
    }
};
