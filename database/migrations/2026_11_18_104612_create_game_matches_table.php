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
        Schema::create('game_matches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
    $table->foreignId('round_id')->constrained('rounds')->cascadeOnDelete();
    $table->unsignedSmallInteger('match_number');
    $table->string('bracket')->default('winners');
    $table->unsignedSmallInteger('position')->default(0);

    $table->unsignedBigInteger('entry1_id')->nullable()->index();
    $table->unsignedBigInteger('entry2_id')->nullable()->index();
    $table->boolean('slot1_empty')->default(false); // no entrant will ever arrive
    $table->boolean('slot2_empty')->default(false);

    $table->unsignedBigInteger('winner_entry_id')->nullable();
    $table->unsignedBigInteger('loser_entry_id')->nullable();
    $table->unsignedSmallInteger('score1')->nullable(); // used later by F8
    $table->unsignedSmallInteger('score2')->nullable();
    $table->string('status')->default('pending'); // pending | ready | bye | void | completed
    $table->boolean('is_bye')->default(false);

    $table->unsignedBigInteger('next_match_id')->nullable();
    $table->unsignedTinyInteger('next_slot')->nullable();
    $table->unsignedBigInteger('loser_next_match_id')->nullable();
    $table->unsignedTinyInteger('loser_next_slot')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
