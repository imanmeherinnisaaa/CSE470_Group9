<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // role is owned by F1 (Authoy's migration); this guard keeps the two from clashing.
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('player');
            }
            if (! Schema::hasColumn('users', 'rating')) {
                $table->unsignedSmallInteger('rating')->default(1000);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'rating')) {
                $table->dropColumn('rating');
            }
        });
    }
}