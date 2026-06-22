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
        Schema::create('wsop_poy_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('wsop_players')->onDelete('cascade');
            $table->integer('score');
            $table->timestamp('scored_at');
            $table->timestamps();

            $table->index(['player_id', 'scored_at']);
            $table->index('scored_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wsop_poy_scores');
    }
};
