<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->foreignId('station_id')
                ->nullable()
                ->after('tournament_id')
                ->constrained('stations')
                ->cascadeOnDelete();
        });

        Schema::table('game_matches', function (Blueprint $table) {
            $table->foreignId('time_slot_id')
                ->nullable()
                ->after('round_id')
                ->constrained('time_slots')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('time_slot_id');
        });

        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_id');
        });
    }
};
