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
    Schema::table('tournaments', function (Blueprint $table) {
        $table->string('game')->default('General');
        $table->string('format')->default('single_elimination');
        $table->unsignedSmallInteger('min_participants')->default(4);
        $table->unsignedSmallInteger('max_participants')->default(16);
        $table->unsignedSmallInteger('swiss_rounds')->nullable();
        $table->unsignedTinyInteger('best_of')->default(1);
        $table->text('rules')->nullable();
        $table->dateTime('registration_opens_at')->nullable();
        $table->dateTime('registration_closes_at')->nullable();
        $table->dateTime('starts_at')->nullable();
        $table->dateTime('ends_at')->nullable();
        $table->string('status')->default('draft');
        $table->unsignedBigInteger('organizer_id')->nullable()->index();
        $table->dateTime('bracket_generated_at')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::table('tournaments', function (Blueprint $table) {
        $table->dropColumn([
            'game', 'format', 'min_participants', 'max_participants', 'swiss_rounds',
            'best_of', 'rules', 'registration_opens_at', 'registration_closes_at',
            'starts_at', 'ends_at', 'status', 'organizer_id', 'bracket_generated_at',
        ]);
    });
}
};
