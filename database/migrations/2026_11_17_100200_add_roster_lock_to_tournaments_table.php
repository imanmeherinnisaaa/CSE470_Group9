<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tournaments', 'roster_lock_at')) {
            Schema::table('tournaments', function (Blueprint $table) {
                // Optional. Null means rosters lock only when the tournament leaves the registration phase.
                $table->timestamp('roster_lock_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tournaments', 'roster_lock_at')) {
            Schema::table('tournaments', function (Blueprint $table) {
                $table->dropColumn('roster_lock_at');
            });
        }
    }
};
