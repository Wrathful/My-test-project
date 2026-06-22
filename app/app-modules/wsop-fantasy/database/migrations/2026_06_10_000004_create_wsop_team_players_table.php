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
            Schema::create('wsop_team_players', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained('wsop_teams')->onDelete('cascade');
                $table->foreignId('player_id')->constrained('wsop_players')->onDelete('cascade');
                $table->boolean('is_captain')->default(false);
                $table->timestamps();

                $table->unique(['team_id', 'player_id']);
                $table->index(['team_id', 'is_captain']);
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wsop_team_players');
    }
};
