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
        Schema::create('wsop_players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('group_id')->constrained('wsop_groups')->onDelete('cascade');
            $table->integer('cost');
            $table->timestamps();

            $table->index('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wsop_players');
    }
};
