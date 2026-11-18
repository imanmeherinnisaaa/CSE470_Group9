<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();

            // Exactly one of these is set: user_id for solo entries, team_id for team entries.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // No FK constraint here so migration order between F2 and F13 (teams) never matters.
            $table->unsignedBigInteger('team_id')->nullable()->index();

            // confirmed | waitlisted | withdrawn | disqualified (disqualified is set by F12)
            $table->string('status', 15)->default('confirmed');
            $table->string('status_reason')->nullable();
            $table->unsignedSmallInteger('rating_snapshot')->nullable(); // rating used for the eligibility check

            // entered_at decides waitlist order (first come, first served)
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamp('promoted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            // One row per entrant per tournament. Re-entering after withdrawal re-activates the same row.
            $table->unique(['tournament_id', 'user_id']);
            $table->unique(['tournament_id', 'team_id']);
            $table->index(['tournament_id', 'status', 'entered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
