<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete(); // slots live and die with the team
            $table->foreignId('player_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_substitute')->default(false);
            $table->boolean('locked')->default(false); // mirrors Team::isLocked(), kept in sync by RosterService::syncLocks()
            $table->timestamps();

            $table->unique(['team_id', 'player_id']);
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_slots');
    }
};
