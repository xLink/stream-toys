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
        Schema::create('hp_bars', function (Blueprint $table) {
            $table->uuid();
            $table->string('name')->unique();
            $table->integer('current_hp')->default(100);
            $table->integer('max_hp')->default(100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hp_bars');
    }
};
