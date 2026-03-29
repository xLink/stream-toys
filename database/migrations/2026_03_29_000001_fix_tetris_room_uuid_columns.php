<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix tetris_room.user_id: was unsignedInteger, users.id is UUID
        Schema::table('tetris_room', function (Blueprint $table) {
            $table->string('user_id')->nullable()->change();
        });

        // Recreate tetris_room_players with correct UUID-typed columns
        Schema::dropIfExists('tetris_room_players');
        Schema::create('tetris_room_players', function (Blueprint $table) {
            $table->uuid('room_id');
            $table->uuid('user_id');
            $table->string('color');
            $table->timestamps();

            $table->primary(['room_id', 'user_id']);
            $table->foreign('room_id')->references('id')->on('tetris_room')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Recreate tetris_room_pieces with correct UUID-typed columns
        Schema::dropIfExists('tetris_room_pieces');
        Schema::create('tetris_room_pieces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('room_id');
            $table->uuid('user_id');
            $table->string('type');
            $table->integer('rotation');
            $table->integer('x');
            $table->integer('y');
            $table->string('section')->default('history');
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('tetris_room')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tetris_room_pieces');
        Schema::dropIfExists('tetris_room_players');

        Schema::table('tetris_room', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable()->change();
        });
    }
};
