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
        Schema::create('wsop_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gipsyteam_user_id')->constrained('gipsyteam_user')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->integer('total_score')->default(0);
            $table->timestamps();

            $table->unique(['gipsyteam_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wsop_teams');
    }
};
