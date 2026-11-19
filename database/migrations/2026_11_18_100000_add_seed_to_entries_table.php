<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** F3 (Seeding Engine) writes each entrant's seed here. F2 owns the entries table, so the column lives with it. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('entries', 'seed')) {
            Schema::table('entries', function (Blueprint $table) {
                $table->unsignedSmallInteger('seed')->nullable()->after('rating_snapshot'); // 1 = top seed
                $table->index(['tournament_id', 'seed']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('entries', 'seed')) {
            Schema::table('entries', function (Blueprint $table) {
                $table->dropIndex(['tournament_id', 'seed']);
                $table->dropColumn('seed');
            });
        }
    }
};
