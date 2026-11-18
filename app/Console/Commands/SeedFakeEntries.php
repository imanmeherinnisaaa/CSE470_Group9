<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Console\Command;

class SeedFakeEntries extends Command
{
    protected $signature = 'tourneyforge:fake-entries {tournament : Tournament ID} {count=8 : Number of entries}';
    protected $description = "Replace a tournament's entries with N fake confirmed players (for testing brackets)";

    public function handle(): int
    {
        $tournament = Tournament::findOrFail($this->argument('tournament'));
        $tournament->entries()->delete();

        for ($i = 1; $i <= (int) $this->argument('count'); $i++) {
            $user = User::firstOrCreate(
                ['email' => "player{$i}@test.local"],
                ['name' => "Player {$i}", 'password' => bcrypt('password')]
            );
            Entry::forceCreate([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'seed' => $i,
                'status' => Entry::CONFIRMED,
            ]);
        }

        $this->info("Created {$this->argument('count')} entries for \"{$tournament->name}\".");

        return self::SUCCESS;
    }
}