<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F2 needs a few entry-related settings on the tournaments table.
 * max_participants and status are owned by F1 (Tournament Format Configuration).
 * Each column is guarded with hasColumn() so this migration is safe even if F1 already added it.
 */
return new class extends Migration
{
    private array $columns = ['entry_opens_at', 'entry_closes_at', 'min_rating', 'max_rating', 'entry_type', 'team_size'];

    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (! Schema::hasColumn('tournaments', 'entry_opens_at')) {
                $table->timestamp('entry_opens_at')->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'entry_closes_at')) {
                $table->timestamp('entry_closes_at')->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'min_rating')) {
                $table->unsignedSmallInteger('min_rating')->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'max_rating')) {
                $table->unsignedSmallInteger('max_rating')->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'entry_type')) {
                $table->string('entry_type', 10)->default('solo'); // solo | team
            }
            if (! Schema::hasColumn('tournaments', 'team_size')) {
                $table->unsignedTinyInteger('team_size')->nullable(); // starters per team (team tournaments)
            }
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $existing = array_filter($this->columns, fn ($c) => Schema::hasColumn('tournaments', $c));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};
