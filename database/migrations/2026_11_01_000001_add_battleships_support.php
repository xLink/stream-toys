<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tetris_room', function (Blueprint $table) {
            $table->string('phase')->nullable()->after('selection_type');
            $table->string('turn_color')->nullable()->after('phase');
        });

        Schema::table('tetris_room_players', function (Blueprint $table) {
            $table->boolean('ready')->default(false)->after('color');
        });

        Schema::table('tetris_room_pieces', function (Blueprint $table) {
            $table->boolean('hit')->default(false)->after('section');
        });
    }

    public function down(): void
    {
        Schema::table('tetris_room', function (Blueprint $table) {
            $table->dropColumn(['phase', 'turn_color']);
        });

        Schema::table('tetris_room_players', function (Blueprint $table) {
            $table->dropColumn('ready');
        });

        Schema::table('tetris_room_pieces', function (Blueprint $table) {
            $table->dropColumn('hit');
        });
    }
};
