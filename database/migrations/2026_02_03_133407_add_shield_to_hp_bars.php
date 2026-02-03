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
        Schema::table('hp_bars', function (Blueprint $table) {
            $table->integer('shield')->default(0)->after('max_hp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hp_bars', function (Blueprint $table) {
            $table->dropColumn('shield');
        });
    }
};
