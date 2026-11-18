<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')
                ->constrained('game_matches')
                ->cascadeOnDelete();
            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->unsignedInteger('score1');
            $table->unsignedInteger('score2');
            $table->timestamps();

            $table->unique(['game_match_id', 'reporter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_reports');
    }
};
